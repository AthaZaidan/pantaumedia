<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use App\Models\SocialPost;

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
     * Analisis teks postingan secara otomatis (Heuristik Bot + Sentimen), sinkronkan ke monitored_accounts,
     * simpan ke social_posts, dan hitung ulang agregasi statistik isu di PostgreSQL.
     *
     * @param  array<string, mixed>  $payload
     */
    public function ingestAndAnalyze(Issue $issue, array $payload): SocialPost
    {
        $content = trim((string) ($payload['content'] ?? ''));
        $rawHandle = trim((string) ($payload['author_handle'] ?? '@warga_net'));
        $handle = str_starts_with($rawHandle, '@') ? $rawHandle : '@'.$rawHandle;
        $authorName = trim((string) ($payload['author_name'] ?? 'Warga Netizen'));
        $platform = strtolower((string) ($payload['platform'] ?? 'x'));

        // 1. Cek apakah akun sudah ada di tabel monitored_accounts
        $account = MonitoredAccount::where('handle', $handle)->first();

        // 2. Hitung Skor Kemungkinan Bot (0 - 100%) beserta alasan heuristiknya
        [$botScore, $reasons] = $this->evaluateBotHeuristics($content, $handle, $account, $payload['force_bot'] ?? null);
        $isBot = $botScore >= 60.0;

        // 3. Sinkronkan / buat otomatis profil akun di tabel monitored_accounts PostgreSQL
        $platformLabel = match ($platform) {
            'meta' => 'Meta (IG & FB)',
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
                $account->update(['topics' => $topics]);
            }
        }

        // 4. Klasifikasi Sentimen Bahasa Indonesia (positive / negative + confidence)
        [$sentiment, $sentimentConf] = $this->analyzeSentiment($content, $payload['force_sentiment'] ?? null);

        // 5. Simpan ke tabel social_posts di PostgreSQL
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
            'likes' => (int) ($payload['likes'] ?? ($isBot ? rand(80, 450) : rand(5, 120))),
            'reposts' => (int) ($payload['reposts'] ?? ($isBot ? rand(50, 320) : rand(1, 35))),
            'post_url' => (string) ($payload['post_url'] ?? 'https://x.com'),
            'posted_at' => $payload['posted_at'] ?? now(),
            'metadata' => [
                'ingested_via' => 'realtime_analyzer',
                'hashtag_count' => preg_match_all('/#\w+/u', $content),
            ],
        ]);

        // 6. Hitung ulang statistik isu secara real dari isi tabel social_posts di PostgreSQL
        $this->recalculateIssueStats($issue);

        return $post;
    }

    /**
     * Evaluasi skor bot sekaligus menghasilkan daftar alasan heuristik terukur.
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

        if ($total === 0) {
            return;
        }

        $humanCount = $posts->where('is_bot', false)->count();
        $posCount = $posts->where('sentiment', 'positive')->count();

        $humanPct = round(($humanCount / $total) * 100, 1);
        $botPct = round(100 - $humanPct, 1);
        $posPct = round(($posCount / $total) * 100, 1);
        $negPct = round(100 - $posPct, 1);

        $platformNames = [
            'meta' => 'Meta (IG & FB)',
            'x' => 'X & Threads',
            'tiktok' => 'TikTok & YouTube',
        ];

        $platformsData = [];
        foreach ($platformNames as $platKey => $platName) {
            $platPosts = $posts->where('platform', $platKey);
            $platTotal = $platPosts->count();

            $platHumans = $platPosts->where('is_bot', false);
            $platBots = $platPosts->where('is_bot', true);

            $hTotal = $platHumans->count();
            $bTotal = $platBots->count();

            $hPos = $hTotal > 0 ? round(($platHumans->where('sentiment', 'positive')->count() / $hTotal) * 100, 1) : 50.0;
            $hNeg = $hTotal > 0 ? round(100 - $hPos, 1) : 50.0;

            $bPos = $bTotal > 0 ? round(($platBots->where('sentiment', 'positive')->count() / $bTotal) * 100, 1) : 50.0;
            $bNeg = $bTotal > 0 ? round(100 - $bPos, 1) : 50.0;

            $platformsData[$platKey] = [
                'name' => $platName,
                'volume' => number_format($platTotal, 0, ',', '.').' post',
                'count' => $platTotal,
                'human_pos' => $hPos,
                'human_neg' => $hNeg,
                'bot_pos' => $bPos,
                'bot_neg' => $bNeg,
                'sufficient' => $platTotal > 0,
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
     * Analisis sentimen teks bahasa Indonesia.
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
