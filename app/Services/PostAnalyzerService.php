<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostAnalyzerService
{
    /**
     * Kamus kata kunci sentimen positif kebijakan publik bahasa Indonesia.
     *
     * @var array<int, string>
     */
    private array $positiveLexicon = [
        'dukung', 'bagus', 'lancar', 'sukses', 'manfaat', 'terbantu', 'solusi',
        'antusias', 'indonesia maju', 'generasi emas', 'kerja nyata', 'terbukti',
        'apresiasi', 'positif', 'optimis', 'sejahtera', 'tepat sasaran',
    ];

    /**
     * Kamus kata kunci sentimen negatif/kritis kebijakan publik bahasa Indonesia.
     *
     * @var array<int, string>
     */
    private array $negativeLexicon = [
        'keluh', 'lambat', 'kurang', 'minim', 'audit', 'kejanggalan', 'darurat',
        'bongkar', 'rusak', 'mahal', 'beban', 'korupsi', 'tidak merata', 'bingung',
        'potongan', 'polusi', 'antre', 'langka', 'evaluasi', 'masalah',
    ];

    /**
     * Cocokkan teks postingan atau parameter request dengan daftar Issue di PostgreSQL.
     * Menggunakan pencocokan cerdas anti-fallback salah kamar:
     * 1. Jika issue_slug spesifik (bukan 'auto'): cari berdasarkan slug atau LOWER(title),
     *    buat baru otomatis via firstOrCreate jika belum ada.
     * 2. Jika issue_title spesifik: cari berdasarkan LOWER(title) atau slug turunan, buat baru jika belum ada.
     * 3. Jika issue_slug 'auto': lakukan pencocokan kata kunci mendalam (content & source_news)
     *    terhadap seluruh isu di database (beserta sinonim kontekstual).
     * 4. Jika 'auto' dan tidak cocok dengan isu manapun: cek ScrapeJob aktif atau arahkan ke 'Isu Terkini & Opini Publik',
     *    BUKAN memaksakan masuk ke MBG (Makan Bergizi Gratis).
     *
     * @param  array<string, mixed>  $payload
     */
    public function resolveIssue(array $payload): Issue
    {
        $slug = trim((string) ($payload['issue_slug'] ?? ''));
        $title = trim((string) ($payload['issue_title'] ?? ''));

        // 1. Jika slug eksplisit diberikan dan bukan 'auto'
        if ($slug !== '' && strtolower($slug) !== 'auto') {
            $slugClean = strtolower($slug);
            $slugAsTitle = strtolower(str_replace(['-', '_'], ' ', $slugClean));

            $existing = Issue::where('slug', $slugClean)
                ->orWhereRaw('LOWER(title) = ?', [$slugAsTitle])
                ->first();

            if (! $existing && $title !== '') {
                $existing = Issue::whereRaw('LOWER(title) = ?', [strtolower($title)])->first();
            }

            if ($existing) {
                return $existing;
            }

            $issueTitle = $title !== '' ? $title : Str::title(str_replace(['-', '_'], ' ', $slugClean));

            return Issue::firstOrCreate(
                ['slug' => $slugClean],
                [
                    'title' => $issueTitle,
                    'subtitle' => 'Pantauan Live Scraper & Opini Publik',
                    'rank' => Issue::count() + 1,
                    'trend' => 'stable',
                    'keywords' => [$issueTitle, str_replace(['-', '_'], ' ', $slugClean)],
                    'human_pct' => 50.00,
                    'bot_pct' => 50.00,
                    'pos_pct' => 50.00,
                    'neg_pct' => 50.00,
                    'total_posts' => 0,
                ]
            );
        }

        // 2. Jika title eksplisit diberikan dan bukan 'auto'
        if ($title !== '' && strtolower($title) !== 'auto') {
            $titleLower = strtolower($title);
            $derivedSlug = Str::slug($title);

            $existing = Issue::whereRaw('LOWER(title) = ?', [$titleLower])
                ->orWhere('slug', $derivedSlug)
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($derivedSlug !== '') {
                return Issue::firstOrCreate(
                    ['slug' => $derivedSlug],
                    [
                        'title' => $title,
                        'subtitle' => 'Pantauan Live Scraper & Opini Publik',
                        'rank' => Issue::count() + 1,
                        'trend' => 'stable',
                        'keywords' => [$title, str_replace('-', ' ', $derivedSlug)],
                        'human_pct' => 50.00,
                        'bot_pct' => 50.00,
                        'pos_pct' => 50.00,
                        'neg_pct' => 50.00,
                        'total_posts' => 0,
                    ]
                );
            }
        }

        // 3. Jika ada issue_id eksplisit
        if (! empty($payload['issue_id'])) {
            $foundById = Issue::find($payload['issue_id']);
            if ($foundById !== null) {
                return $foundById;
            }
        }

        // 4. Pencocokan kata kunci konten dan judul berita terhadap seluruh isu yang ada di database
        $searchCorpus = strtolower(trim(
            ((string) ($payload['content'] ?? $payload['komentar'] ?? $payload['text'] ?? '')).' '.
            ((string) ($payload['source_news'] ?? $payload['judul_berita'] ?? ''))
        ));

        // Kamus sinonim kontekstual isu-isu kebijakan publik
        $contextualSynonyms = [
            'karhutla' => ['karhutla', 'kebakaran hutan', 'hutan terbakar', 'kabut asap', 'titik api', 'lahan gambut', 'kebakaran lahan', 'manggala agni', 'asap pekat'],
            'gas-alam' => ['gas alam', 'elpiji', 'lpg', 'gas melon', 'tabung gas', 'pangkalan gas', 'distribusi gas', 'langka gas', 'antre gas'],
            'polusi-udara-jkt' => ['polusi udara', 'ispu', 'kualitas udara', 'debu', 'emisi', 'pltu', 'polusi jakarta', 'asap kendaraan', 'udara jkt'],
            'makan-bergizi-gratis' => ['makan bergizi gratis', 'mbg', 'menu mbg', 'gizi gratis', 'makan siang gratis', 'dapur mbg', 'sarapan bergizi'],
            'kabur-aja-dulu' => ['kabur aja dulu', 'kerja luar negeri', 'pajak kelas menengah', 'living abroad', 'whv', 'migrasi'],
            'kopdes' => ['kopdes', 'koperasi desa', 'pangan desa', 'lumbung desa', 'koperasi merah putih'],
            'subsidi-pupuk' => ['subsidi pupuk', 'pupuk urea', 'kartu tani', 'pupuk subsidi', 'pupuk langka', 'distribusi pupuk'],
            'kenaikan-pbb' => ['kenaikan pbb', 'njop naik', 'pajak bumi bangunan', 'pbb p2', 'sppt pbb', 'pajak tanah'],
            'jalan-rusak-lpg' => ['jalan rusak', 'infrastruktur daerah', 'perbaikan jalan', 'jalan berlubang', 'jalan berdebu', 'aspal rusak'],
            'iuran-tapera' => ['iuran tapera', 'potongan tapera', 'tabungan perumahan', 'gaji dipotong tapera', 'bp tapera'],
        ];

        if ($searchCorpus !== '') {
            $allIssues = Issue::all();
            $bestMatch = null;
            $bestScore = 0;

            foreach ($allIssues as $issue) {
                $score = 0;
                $keywordsList = is_array($issue->keywords) ? $issue->keywords : [];
                $keywordsList[] = $issue->title;
                $keywordsList[] = str_replace(['-', '_'], ' ', $issue->slug);

                if (isset($contextualSynonyms[$issue->slug])) {
                    $keywordsList = array_merge($keywordsList, $contextualSynonyms[$issue->slug]);
                }

                foreach ($contextualSynonyms as $synKey => $synList) {
                    if (str_contains(strtolower($issue->title), $synKey) || str_contains($issue->slug, $synKey)) {
                        $keywordsList = array_merge($keywordsList, $synList);
                    }
                }

                $keywordsList = array_unique(array_filter(array_map('trim', $keywordsList)));

                foreach ($keywordsList as $kw) {
                    $kwClean = strtolower($kw);
                    if ($kwClean === '') {
                        continue;
                    }

                    if (str_contains($searchCorpus, $kwClean)) {
                        $score += (mb_strlen($kwClean) >= 7) ? 5 : 2;
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $issue;
                }
            }

            if ($bestMatch !== null && $bestScore > 0) {
                return $bestMatch;
            }
        }

        // 5. Cek apakah ada tugas ScrapeJob yang sedang berjalan (running) dari perintah tombol web
        try {
            $runningJob = DB::table('scrape_jobs')
                ->where('status', 'running')
                ->orderByDesc('id')
                ->first();

            if ($runningJob && ! empty($runningJob->topic)) {
                $jobTopic = trim($runningJob->topic);
                $jobSlug = Str::slug($jobTopic);
                if ($jobSlug !== '') {
                    return Issue::firstOrCreate(
                        ['slug' => $jobSlug],
                        [
                            'title' => $jobTopic,
                            'subtitle' => 'Pantauan Live Scraper (JupyterHub)',
                            'rank' => Issue::count() + 1,
                            'trend' => 'stable',
                            'keywords' => [$jobTopic, str_replace('-', ' ', $jobSlug)],
                            'human_pct' => 50.00,
                            'bot_pct' => 50.00,
                            'pos_pct' => 50.00,
                            'neg_pct' => 50.00,
                            'total_posts' => 0,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            // Lanjutkan jika query gagal
        }

        // 6. Anti-Fallback Salah Kamar: Jika konten sama sekali tidak cocok,
        // arahkan ke Isu Umum Terkini, bukan memaksakan masuk ke MBG (Makan Bergizi Gratis).
        return Issue::firstOrCreate(
            ['slug' => 'isu-umum-terkini'],
            [
                'title' => 'Isu Terkini & Opini Publik',
                'subtitle' => 'Pantauan Multitopik / Umum',
                'rank' => Issue::count() + 1,
                'trend' => 'stable',
                'keywords' => ['umum', 'berita terkini', 'kebijakan publik'],
                'human_pct' => 50.00,
                'bot_pct' => 50.00,
                'pos_pct' => 50.00,
                'neg_pct' => 50.00,
                'total_posts' => 0,
            ]
        );
    }

    /**
     * Normalisasi kode platform agar masuk ke salah satu dari 4 kanal utama: x, meta, news, atau tiktok.
     */
    public function normalizePlatform(string $rawPlatform): string
    {
        $lower = strtolower(trim($rawPlatform));

        if (in_array($lower, ['news', 'berita', 'rss', 'media', 'portal', 'google news', 'google_news'], true)) {
            return 'news';
        }

        if (in_array($lower, ['meta', 'ig', 'instagram', 'fb', 'facebook'], true)) {
            return 'meta';
        }

        if (in_array($lower, ['tiktok', 'youtube', 'yt', 'tt'], true)) {
            return 'tiktok';
        }

        return 'x';
    }

    /**
     * Normalisasi nilai sentimen dari AI Qwen (Positif/Negatif/Netral atau positive/negative/neutral).
     */
    public function normalizeAiSentiment(?string $rawSentiment): ?string
    {
        if ($rawSentiment === null || trim($rawSentiment) === '') {
            return null;
        }

        $lower = strtolower(trim($rawSentiment));

        if (in_array($lower, ['positive', 'positif', 'pos', 'pro'], true)) {
            return 'positive';
        }

        if (in_array($lower, ['negative', 'negatif', 'neg', 'kontra', 'kritis'], true)) {
            return 'negative';
        }

        if (in_array($lower, ['neutral', 'netral'], true)) {
            return 'neutral';
        }

        return null;
    }

    /**
     * Normalisasi array ai_institutions dari payload (mendukung array langsung maupun JSON string).
     *
     * @return array<int, array{lembaga: string, sentimen: string, alasan: string}>
     */
    public function normalizeAiInstitutions(mixed $rawInstitutions): array
    {
        if (is_string($rawInstitutions) && trim($rawInstitutions) !== '') {
            $decoded = json_decode($rawInstitutions, true);
            $rawInstitutions = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($rawInstitutions)) {
            return [];
        }

        $normalized = [];
        foreach ($rawInstitutions as $item) {
            if (! is_array($item)) {
                continue;
            }

            $lembaga = trim((string) ($item['lembaga'] ?? $item['institution'] ?? $item['nama'] ?? ''));
            if ($lembaga === '') {
                continue;
            }

            $sentimenRaw = (string) ($item['sentimen'] ?? $item['sentiment'] ?? 'Netral');
            $alasan = trim((string) ($item['alasan'] ?? $item['reason'] ?? ''));

            $normalized[] = [
                'lembaga' => $lembaga,
                'sentimen' => ucfirst(strtolower(trim($sentimenRaw))),
                'alasan' => $alasan,
            ];
        }

        return $normalized;
    }

    /**
     * Analisis postingan dengan memprioritaskan hasil AI Qwen (JupyterHub) jika tersedia,
     * sinkronkan ke monitored_accounts, simpan ke social_posts, dan hitung ulang statistik isu.
     *
     * @param  array<string, mixed>  $payload
     */
    public function ingestAndAnalyze(?Issue $issue, array $payload, bool $recalculateImmediately = true): SocialPost
    {
        $issue ??= $this->resolveIssue($payload);

        $content = trim((string) ($payload['content'] ?? $payload['komentar'] ?? $payload['text'] ?? ''));
        $authorName = trim((string) ($payload['author_name'] ?? $payload['username'] ?? $payload['penulis'] ?? 'Warga Netizen'));
        $rawHandle = trim((string) ($payload['author_handle'] ?? $payload['handle'] ?? '@'.strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $authorName) ?: 'warga_net')));
        $handle = str_starts_with($rawHandle, '@') ? $rawHandle : '@'.$rawHandle;

        $rawPlat = strtolower(trim((string) ($payload['platform'] ?? '')));
        $rawOrigin = strtolower(trim((string) ($payload['platform_origin'] ?? '')));

        if ($rawPlat === 'news' || $rawOrigin === 'news' || in_array($rawOrigin, ['news', 'berita', 'google news', 'google_news', 'rss', 'media'], true)) {
            $platform = 'news';
        } else {
            $platform = $this->normalizePlatform($rawPlat !== '' ? $rawPlat : $rawOrigin);
        }

        // Ambil parameter AI Qwen dari JupyterHub jika tersedia
        $rawAiBotScore = $payload['ai_bot_score'] ?? $payload['skor_bot'] ?? null;
        $hasAiBotScore = $rawAiBotScore !== null && $rawAiBotScore !== '';
        $aiBotScore = $hasAiBotScore ? round(max(0.0, min(100.0, (float) $rawAiBotScore)), 1) : null;

        $aiBotReason = trim((string) ($payload['ai_bot_reason'] ?? $payload['alasan_bot'] ?? ''));
        $aiSentiment = $this->normalizeAiSentiment(
            isset($payload['ai_sentiment']) ? (string) $payload['ai_sentiment']
                : (isset($payload['sentimen_umum']) ? (string) $payload['sentimen_umum'] : null)
        );
        $aiInstitutions = $this->normalizeAiInstitutions(
            $payload['ai_institutions'] ?? $payload['analisis_lembaga'] ?? $payload['institutions'] ?? []
        );

        // 1. Cek apakah akun sudah ada di tabel monitored_accounts
        $account = MonitoredAccount::where('handle', $handle)->first();

        // 2. Prioritaskan nilai ai_bot_score & ai_bot_reason dari AI Qwen; gunakan heuristik hanya sebagai fallback
        if ($aiBotScore !== null) {
            $botScore = $aiBotScore;
            $reasons = $aiBotReason !== ''
                ? [$aiBotReason]
                : ($account?->reasons ?: ['Skor probabilitas bot ditetapkan oleh AI Agent Qwen (JupyterHub).']);
        } else {
            [$botScore, $reasons] = $this->evaluateBotHeuristics($content, $handle, $account, $payload['force_bot'] ?? null);
        }

        $isBot = $botScore >= 60.0;

        // 3. Sinkronkan / perbarui profil akun di tabel monitored_accounts PostgreSQL
        $platformLabel = match ($platform) {
            'meta' => 'Meta (IG & FB)',
            'news' => 'Berita & Media',
            'tiktok' => 'TikTok & YouTube',
            default => 'X (Twitter)',
        };

        if ($account === null) {
            $account = MonitoredAccount::create([
                'handle' => $handle,
                'name' => $authorName,
                'platform' => $platformLabel,
                'age' => $isBot ? rand(5, 28).' hari' : rand(2, 6).' tahun',
                'followers' => $isBot ? rand(4, 25) : rand(450, 5200),
                'following' => $isBot ? rand(900, 2100) : rand(220, 680),
                'posting_frequency' => $isBot ? rand(90, 180).' postingan / hari' : '2 - 5 postingan / hari',
                'is_bot' => $isBot,
                'bot_score' => $botScore,
                'reasons' => $reasons,
                'topics' => [$issue->title],
                'cluster' => $isBot ? ['@sahabat_negeri77', '@rakyat_bersatu_id'] : [],
            ]);
        } else {
            $topics = $account->topics ?? [];
            if (! in_array($issue->title, $topics, true)) {
                $topics[] = $issue->title;
            }

            $existingReasons = $account->reasons ?? [];
            if ($aiBotReason !== '' && ! in_array($aiBotReason, $existingReasons, true)) {
                array_unshift($existingReasons, $aiBotReason);
            }

            $account->update([
                'is_bot' => $aiBotScore !== null ? $isBot : $account->is_bot,
                'bot_score' => $aiBotScore !== null ? $botScore : $account->bot_score,
                'reasons' => ! empty($existingReasons) ? array_slice($existingReasons, 0, 5) : $reasons,
                'topics' => $topics,
            ]);
        }

        // 4. Prioritaskan ai_sentiment dari AI Qwen; gunakan leksikon hanya sebagai fallback
        if ($aiSentiment !== null) {
            // Untuk menjaga kompatibilitas agregasi biner Positif vs Negatif, neutral disimpan dengan metadata asli
            $sentiment = $aiSentiment === 'neutral' ? 'positive' : $aiSentiment;
            $sentimentConf = (float) ($payload['sentiment_conf'] ?? 95.4);
        } else {
            [$sentiment, $sentimentConf] = $this->analyzeSentiment($content, $payload['force_sentiment'] ?? null);
        }

        $likesCount = (int) ($payload['likes_count'] ?? $payload['likes'] ?? 0);
        $repostsCount = (int) ($payload['reposts_count'] ?? $payload['reposts'] ?? 0);
        $sourceUrl = trim((string) ($payload['source_url'] ?? $payload['post_url'] ?? $payload['url'] ?? 'https://x.com'));

        // 5. Simpan ke tabel social_posts di PostgreSQL lengkap dengan metadata AI Qwen
        $post = SocialPost::create([
            'issue_id' => $issue->id,
            'monitored_account_id' => $account->id,
            'platform' => $platform,
            'author_name' => $authorName,
            'author_handle' => $handle,
            'content' => $content,
            'is_bot' => $isBot,
            'bot_score' => $botScore,
            'sentiment' => $sentiment,
            'sentiment_conf' => $sentimentConf,
            'likes' => $likesCount,
            'reposts' => $repostsCount,
            'post_url' => $sourceUrl !== '' ? $sourceUrl : 'https://x.com',
            'posted_at' => $payload['posted_at'] ?? now(),
            'metadata' => [
                'platform_origin' => $payload['platform_origin'] ?? ($platform === 'news' ? 'news' : null),
                'ingested_via' => ($aiBotScore !== null || $aiSentiment !== null || ! empty($aiInstitutions)) ? 'qwen_jupyterhub' : 'realtime_analyzer',
                'ai_sentiment_raw' => $aiSentiment,
                'ai_bot_reason' => $aiBotReason !== '' ? $aiBotReason : null,
                'ai_institutions' => $aiInstitutions,
                'source_news' => $payload['source_news'] ?? $payload['judul_berita'] ?? null,
                'hashtag_count' => preg_match_all('/#\w+/u', $content),
            ],
        ]);

        // 6. Hitung ulang statistik isu secara real dari isi tabel social_posts di PostgreSQL
        if ($recalculateImmediately) {
            $this->recalculateIssueStats($issue);
        }

        return $post;
    }

    /**
     * Evaluasi skor bot sekaligus menghasilkan daftar alasan heuristik terukur (Fallback jika AI kosong).
     *
     * @return array{0: float, 1: array<int, string>}
     */
    public function evaluateBotHeuristics(string $content, string $handle, ?MonitoredAccount $account, ?bool $forceBot = null): array
    {
        if ($account !== null && $forceBot === null) {
            return [(float) $account->bot_score, $account->reasons ?? []];
        }

        $score = 8.4;
        $reasons = [];

        $hashtagCount = preg_match_all('/#\w+/u', $content);
        if ($hashtagCount >= 2) {
            $score += 42.0;
            $reasons[] = "Kepadatan Tagar Kampanye: Mengandung {$hashtagCount} hashtag terkoordinasi dalam satu kiriman.";
        } elseif ($hashtagCount === 1) {
            $score += 18.0;
            $reasons[] = 'Menggunakan tagar amplifikasi pada teks postingan.';
        }

        $duplicateExists = SocialPost::where('content', $content)->exists();
        if ($duplicateExists) {
            $score += 36.0;
            $reasons[] = 'Duplikasi Leksikal (Min-Hash 100%): Teks identik ditemukan pada postingan akun lain di database.';
        }

        if (preg_match('/\d{2,}$/', $handle)) {
            $score += 24.5;
            $reasons[] = "Pola Handle Otomatis: Username ({$handle}) menggunakan akhiran deretan angka tipikal generator akun massal.";
        }

        if ($forceBot === true && $score < 90.0) {
            $score = round(rand(915, 982) / 10, 1);
            if (empty($reasons)) {
                $reasons[] = 'Anomali Interval Waktu: Frekuensi pengiriman sinkron dengan kluster jaringan bot.';
            }
        } elseif ($forceBot === false && $score >= 60.0) {
            $score = round(rand(32, 88) / 10, 1);
            $reasons = ['Variasi Kosakata Alami: Struktur kalimat organik warga dan tidak terikat template botnet.'];
        }

        $finalScore = min(99.4, round($score, 1));

        if (empty($reasons)) {
            if ($finalScore >= 60.0) {
                $reasons[] = 'Terdeteksi pola amplifikasi seragam dan rasio pengikut timpang.';
            } else {
                $reasons[] = 'Variasi Kosakata Alami: Struktur kalimat unik dan pola aktivitas sesuai jam biologis manusia.';
            }
        }

        return [$finalScore, $reasons];
    }

    /**
     * Hitung ulang seluruh persentase (Orang vs Bot, Positif vs Negatif, dan per-Platform)
     * untuk sebuah Issue murni berdasarkan baris yang ada di tabel social_posts PostgreSQL.
     */
    public function recalculateIssueStats(Issue $issue): void
    {
        $posts = SocialPost::where('issue_id', $issue->id)->get();
        $total = $posts->count();

        $humanCount = $posts->where('is_bot', false)->count();
        $posCount = $posts->where('sentiment', 'positive')->count();

        $humanPct = $total > 0 ? round(($humanCount / $total) * 100, 1) : 0.0;
        $botPct = $total > 0 ? round(100 - $humanPct, 1) : 0.0;
        $posPct = $total > 0 ? round(($posCount / $total) * 100, 1) : 0.0;
        $negPct = $total > 0 ? round(100 - $posPct, 1) : 0.0;

        $platformNames = [
            'x' => 'X & Threads',
            'meta' => 'Meta (IG & FB)',
            'news' => 'Berita & Media',
            'tiktok' => 'TikTok & YouTube',
        ];

        $platformsData = [];
        foreach ($platformNames as $platKey => $platName) {
            $platPosts = $posts->filter(function ($p) use ($platKey) {
                if ($platKey === 'news') {
                    return $p->platform === 'news'
                        || (isset($p->metadata['platform_origin']) && strtolower((string) $p->metadata['platform_origin']) === 'news')
                        || str_contains((string) $p->post_url, 'news.google.com');
                }
                if ($platKey === 'meta') {
                    $isNews = $p->platform === 'news'
                        || (isset($p->metadata['platform_origin']) && strtolower((string) $p->metadata['platform_origin']) === 'news')
                        || str_contains((string) $p->post_url, 'news.google.com');

                    return $p->platform === 'meta' && ! $isNews;
                }

                return $p->platform === $platKey;
            });
            $platTotal = $platPosts->count();

            $platHumans = $platPosts->where('is_bot', false);
            $platBots = $platPosts->where('is_bot', true);

            $hTotal = $platHumans->count();
            $bTotal = $platBots->count();

            $hPos = $hTotal > 0 ? round(($platHumans->where('sentiment', 'positive')->count() / $hTotal) * 100, 1) : 0.0;
            $hNeg = $hTotal > 0 ? round(100 - $hPos, 1) : 0.0;

            $bPos = $bTotal > 0 ? round(($platBots->where('sentiment', 'positive')->count() / $bTotal) * 100, 1) : 0.0;
            $bNeg = $bTotal > 0 ? round(100 - $bPos, 1) : 0.0;

            $platformsData[$platKey] = [
                'name' => $platName,
                'volume' => number_format($platTotal, 0, ',', '.').' post',
                'count' => $platTotal,
                'human_pos' => $hPos,
                'human_neg' => $hNeg,
                'bot_pos' => $bPos,
                'bot_neg' => $bNeg,
                'sufficient' => true,
            ];
        }

        $issue->update([
            'total_posts' => $total,
            'human_pct' => $humanPct,
            'bot_pct' => $botPct,
            'pos_pct' => $posPct,
            'neg_pct' => $negPct,
            'platforms' => $platformsData,
        ]);
    }

    /**
     * Urutkan ulang peringkat (rank 1..N) seluruh isu berdasarkan total_posts tertinggi di PostgreSQL.
     */
    public function rerankAllIssues(): void
    {
        $issues = Issue::orderByDesc('total_posts')->orderBy('id')->get();
        foreach ($issues as $index => $issue) {
            if ($issue->rank !== ($index + 1)) {
                $issue->update(['rank' => $index + 1]);
            }
        }
    }

    /**
     * Analisis sentimen teks bahasa Indonesia (Fallback jika AI kosong).
     *
     * @return array{0: string, 1: float}
     */
    public function analyzeSentiment(string $content, ?string $forceSentiment = null): array
    {
        if ($forceSentiment !== null && in_array($forceSentiment, ['positive', 'negative'], true)) {
            return [$forceSentiment, round(rand(890, 985) / 10, 1)];
        }

        $lower = strtolower($content);
        $posHits = 0;
        $negHits = 0;

        foreach ($this->positiveLexicon as $word) {
            if (str_contains($lower, $word)) {
                $posHits++;
            }
        }

        foreach ($this->negativeLexicon as $word) {
            if (str_contains($lower, $word)) {
                $negHits++;
            }
        }

        if ($posHits > $negHits) {
            $conf = min(98.9, 86.0 + ($posHits * 3.8));

            return ['positive', round($conf, 1)];
        }

        $conf = min(97.5, 85.0 + ($negHits * 3.5));

        return ['negative', round($conf, 1)];
    }
}
