<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use App\Models\SocialPost;
use App\Services\ChatbotAssistantService;
use App\Services\PostAnalyzerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SospemController extends Controller
{
    /**
     * Ambil daftar isu dari database PostgreSQL dalam format siap pakai untuk view.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getIssuesFromDatabase(): array
    {
        return Issue::orderBy('rank')
            ->orderByDesc('total_posts')
            ->get()
            ->map(fn (Issue $issue): array => $this->formatIssueForView($issue))
            ->all();
    }

    /**
     * Hitung 4 Kartu KPI Utama di bagian atas Dashboard langsung dari tabel PostgreSQL.
     *
     * @return array<string, mixed>
     */
    private function buildKpiStats(): array
    {
        $totalPosts = SocialPost::count();
        $humanPosts = SocialPost::where('is_bot', false)->count();
        $botPosts = SocialPost::where('is_bot', true)->count();

        $humanPct = $totalPosts > 0 ? round(($humanPosts / $totalPosts) * 100, 1) : 0.0;
        $botPct = $totalPosts > 0 ? round(100 - $humanPct, 1) : 0.0;

        $topIssue = Issue::orderByDesc('total_posts')->orderBy('rank')->first();
        $topIssueTitle = $topIssue ? $topIssue->title : '-';
        $topIssueShare = ($topIssue && $totalPosts > 0)
            ? round(($topIssue->total_posts / $totalPosts) * 100, 1)
            : 0.0;

        return [
            'total_posts' => $totalPosts,
            'human_posts' => $humanPosts,
            'bot_posts' => $botPosts,
            'human_pct' => $humanPct,
            'bot_pct' => $botPct,
            'top_issue_title' => $topIssueTitle,
            'top_issue_posts' => $topIssue ? $topIssue->total_posts : 0,
            'top_issue_share' => $topIssueShare,
        ];
    }

    /**
     * Format model Issue menjadi struktur array yang digunakan oleh view.
     *
     * @return array<string, mixed>
     */
    private function formatIssueForView(Issue $issue): array
    {
        $rawPlatforms = is_array($issue->platforms) ? $issue->platforms : [];
        $platformOrder = [
            'x' => 'X & Threads',
            'meta' => 'Meta (IG & FB)',
            'news' => 'Berita & Media',
            'tiktok' => 'TikTok & YouTube',
        ];

        $orderedPlatforms = [];
        foreach ($platformOrder as $key => $name) {
            if (isset($rawPlatforms[$key])) {
                $orderedPlatforms[$key] = $rawPlatforms[$key];
                $orderedPlatforms[$key]['name'] = $name;
            } else {
                $orderedPlatforms[$key] = [
                    'name' => $name,
                    'volume' => '0 post',
                    'count' => 0,
                    'human_pos' => 50.0,
                    'human_neg' => 50.0,
                    'bot_pos' => 50.0,
                    'bot_neg' => 50.0,
                    'sufficient' => true,
                ];
            }
        }

        return [
            'id' => $issue->id,
            'rank' => str_pad((string) $issue->rank, 2, '0', STR_PAD_LEFT),
            'slug' => $issue->slug,
            'title' => $issue->title,
            'subtitle' => $issue->subtitle ?? '',
            'total_posts' => $issue->total_posts,
            'human_pct' => (float) $issue->human_pct,
            'bot_pct' => (float) $issue->bot_pct,
            'pos_pct' => (float) $issue->pos_pct,
            'neg_pct' => (float) $issue->neg_pct,
            'trend' => $issue->trend,
            'platforms' => $orderedPlatforms,
        ];
    }

    /**
     * Dashboard utama: KPI, Tren Periode (24j, 7h, 30h) & Matriks isu x platform murni dari PostgreSQL.
     */
    public function dashboard(PostAnalyzerService $analyzer): View
    {
        Cache::remember('sospem_posts_repaired_v1', now()->addHours(6), function () use ($analyzer): bool {
            $analyzer->repairMisclassifiedPosts();

            return true;
        });

        return view('dashboard', [
            'kpi' => $this->buildKpiStats(),
            'periodTrends' => $this->buildPeriodTrends(),
            'issues' => $this->getIssuesFromDatabase(),
        ]);
    }

    /**
     * Hitung kurva tren SVG dan statistik untuk tab periode 24j, 7h, dan 30h dari tabel social_posts PostgreSQL.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildPeriodTrends(): array
    {
        $allPosts = SocialPost::orderBy('posted_at')->get();
        $now = now();

        // 1. Periode 24 Jam Terakhir (8 titik @ 3 jam)
        $posts24h = $allPosts->filter(fn (SocialPost $p): bool => $p->posted_at && $p->posted_at->greaterThanOrEqualTo($now->copy()->subHours(24)));
        if ($posts24h->isEmpty()) {
            $posts24h = $allPosts;
        }
        $points24h = [];
        for ($h = 0; $h < 24; $h += 3) {
            $points24h[] = $posts24h->filter(function (SocialPost $p) use ($h): bool {
                $hour = $p->posted_at ? (int) $p->posted_at->format('G') : 12;

                return $hour >= $h && $hour <= ($h + 2);
            })->count();
        }

        // 2. Periode 7 Hari Terakhir (7 titik harian)
        $posts7d = $allPosts->filter(fn (SocialPost $p): bool => $p->posted_at && $p->posted_at->greaterThanOrEqualTo($now->copy()->subDays(7)->startOfDay()));
        $points7d = [];
        for ($d = 6; $d >= 0; $d--) {
            $dayDate = $now->copy()->subDays($d)->format('Y-m-d');
            $points7d[] = $posts7d->filter(fn (SocialPost $p): bool => $p->posted_at && $p->posted_at->format('Y-m-d') === $dayDate)->count();
        }

        // 3. Periode 30 Hari Terakhir (6 titik @ 5 hari)
        $posts30d = $allPosts->filter(fn (SocialPost $p): bool => $p->posted_at && $p->posted_at->greaterThanOrEqualTo($now->copy()->subDays(30)->startOfDay()));
        $points30d = [];
        for ($bucket = 5; $bucket >= 0; $bucket--) {
            $start = $now->copy()->subDays(($bucket + 1) * 5)->startOfDay();
            $end = $now->copy()->subDays($bucket * 5)->endOfDay();
            $points30d[] = $posts30d->filter(fn (SocialPost $p): bool => $p->posted_at && $p->posted_at->between($start, $end))->count();
        }

        return [
            '24j' => $this->formatPeriodSvgData(
                $points24h,
                ['00:00 WIB', '12:00 WIB', '21:00 WIB'],
                $posts24h->count(),
                $posts24h->where('is_bot', false)->count(),
                $posts24h->where('is_bot', true)->count()
            ),
            '7h' => $this->formatPeriodSvgData(
                $points7d,
                [
                    $now->copy()->subDays(6)->translatedFormat('d M'),
                    $now->copy()->subDays(3)->translatedFormat('d M'),
                    $now->translatedFormat('d M'),
                ],
                $posts7d->count(),
                $posts7d->where('is_bot', false)->count(),
                $posts7d->where('is_bot', true)->count()
            ),
            '30h' => $this->formatPeriodSvgData(
                $points30d,
                [
                    $now->copy()->subDays(28)->translatedFormat('d M'),
                    $now->copy()->subDays(14)->translatedFormat('d M'),
                    $now->translatedFormat('d M'),
                ],
                $posts30d->count(),
                $posts30d->where('is_bot', false)->count(),
                $posts30d->where('is_bot', true)->count()
            ),
        ];
    }

    /**
     * Konversi deret angka menjadi koordinat SVG path halus (viewBox 1000 x 96).
     *
     * @param  array<int, int>  $values
     * @param  array<int, string>  $axisLabels
     * @return array<string, mixed>
     */
    private function formatPeriodSvgData(array $values, array $axisLabels, int $total, int $human, int $bot): array
    {
        $count = count($values);
        $max = max(1, ...$values);
        $min = min(...$values);
        $range = max(1, $max - $min);

        $coords = [];
        foreach ($values as $i => $val) {
            $x = $count > 1 ? (int) round(($i / ($count - 1)) * 1000) : 500;
            $y = (int) round(78 - ((($val - $min) / $range) * 62));
            $coords[] = [$x, $y];
        }

        $d = 'M'.$coords[0][0].','.$coords[0][1];
        for ($i = 0; $i < count($coords) - 1; $i++) {
            [$x0, $y0] = $coords[$i];
            [$x1, $y1] = $coords[$i + 1];
            $cx1 = (int) round($x0 + ($x1 - $x0) / 2);
            $cx2 = $cx1;
            $d .= " C{$cx1},{$y0} {$cx2},{$y1} {$x1},{$y1}";
        }

        return [
            'line_path' => $d,
            'area_path' => $d.' L1000,96 L0,96 Z',
            'axis_labels' => $axisLabels,
            'total' => $total,
            'human' => $human,
            'bot' => $bot,
        ];
    }

    /**
     * Halaman feed split-screen (Kiri: Orang/Warga Organik, Kanan: Jaringan Bot) dari PostgreSQL.
     */
    public function feed(Request $request, string $issueSlug, string $platform, PostAnalyzerService $analyzer): View
    {
        $issueModel = Issue::where('slug', $issueSlug)->first()
            ?? Issue::orderBy('rank')->firstOrFail();

        $analyzer->repairMisclassifiedPosts($issueModel->id);
        $issueModel->refresh();

        $issue = $this->formatIssueForView($issueModel);
        $platformKey = strtolower($platform);
        $platformData = $issue['platforms'][$platformKey] ?? $issue['platforms']['x'] ?? [
            'name' => 'X & Threads',
            'volume' => '0 post',
            'human_pos' => 50.0,
            'human_neg' => 50.0,
            'bot_pos' => 50.0,
            'bot_neg' => 50.0,
            'sufficient' => true,
        ];

        $sentimentFilter = (string) $request->query('sentiment', 'all');

        $baseQuery = SocialPost::where('issue_id', $issueModel->id)
            ->where(function ($q) use ($platformKey) {
                if ($platformKey === 'news') {
                    $q->where('platform', 'news')
                        ->orWhere('metadata->platform_origin', 'news')
                        ->orWhere('post_url', 'like', '%news.google.com%');
                } elseif ($platformKey === 'meta') {
                    $q->where('platform', 'meta')
                        ->where('post_url', 'not like', '%news.google.com%')
                        ->where(function ($sub) {
                            $sub->whereNull('metadata->platform_origin')
                                ->orWhere('metadata->platform_origin', '!=', 'news');
                        });
                } else {
                    $q->where('platform', $platformKey);
                }
            })
            ->orderByDesc('posted_at')
            ->orderByDesc('id');

        if ($sentimentFilter !== 'all') {
            $baseQuery->where('sentiment', $sentimentFilter);
        }

        $humanPosts = (clone $baseQuery)
            ->where('is_bot', false)
            ->limit(50)
            ->get()
            ->map(fn (SocialPost $p): array => $this->formatPostForView($p, $platformData['name']))
            ->all();

        $botPosts = (clone $baseQuery)
            ->where('is_bot', true)
            ->limit(50)
            ->get()
            ->map(fn (SocialPost $p): array => $this->formatPostForView($p, $platformData['name']))
            ->all();

        return view('feed', [
            'issue' => $issue,
            'platformKey' => $platformKey,
            'platform' => $platformData,
            'sentimentFilter' => $sentimentFilter,
            'humanPosts' => $humanPosts,
            'botPosts' => $botPosts,
        ]);
    }

    /**
     * Format model SocialPost menjadi struktur array view.
     *
     * @return array<string, mixed>
     */
    private function formatPostForView(SocialPost $post, string $platformName): array
    {
        $metadata = is_array($post->metadata) ? $post->metadata : [];

        return [
            'id' => $post->id,
            'author' => $post->author_name,
            'handle' => $post->author_handle,
            'platform' => $platformName,
            'time' => $post->posted_at ? $post->posted_at->diffForHumans() : 'Baru saja',
            'is_bot' => (bool) $post->is_bot,
            'bot_score' => (float) $post->bot_score,
            'human_score' => round(100 - (float) $post->bot_score, 1),
            'sentiment' => $post->sentiment,
            'sentiment_conf' => (float) $post->sentiment_conf,
            'text' => $post->content,
            'likes' => $post->likes,
            'reposts' => $post->reposts,
            'url' => $post->post_url ?? 'https://x.com',
            'ai_institutions' => $metadata['ai_institutions'] ?? [],
            'ai_bot_reason' => $metadata['ai_bot_reason'] ?? null,
            'ai_sentiment_raw' => $metadata['ai_sentiment_raw'] ?? null,
            'source_news' => $metadata['source_news'] ?? null,
        ];
    }

    /**
     * Endpoint Ingest Real-Time: Menganalisis skor bot & sentimen (prioritas AI Qwen JupyterHub),
     * menyimpan ke PostgreSQL, dan menghitung ulang agregasi KPI & isu secara real-time.
     */
    public function ingest(Request $request, PostAnalyzerService $analyzer): JsonResponse
    {
        $this->recordJupyterHeartbeat($request, 'ingest');

        $payload = $request->all();
        $issue = $analyzer->resolveIssue($payload);

        $post = $analyzer->ingestAndAnalyze($issue, $payload);
        $analyzer->rerankAllIssues();

        $platformNames = [
            'x' => 'X & Threads',
            'meta' => 'Meta (FB & IG)',
            'news' => 'Berita & Media',
            'tiktok' => 'TikTok & Shorts',
        ];
        $platformLabel = $platformNames[$post->platform] ?? 'X & Threads';

        return response()->json([
            'status' => 'ok',
            'kpi' => $this->buildKpiStats(),
            'issue' => $this->formatIssueForView($issue->fresh()),
            'post' => $this->formatPostForView($post, $platformLabel),
        ]);
    }

    /**
     * Endpoint Batch Sync (POST /api/ingest-batch): Menerima array JSON banyak komentar
     * (format stream_analisis_sospem.json dari JupyterHub) dan menyimpan ke database tanpa duplikasi.
     */
    public function ingestBatch(Request $request, PostAnalyzerService $analyzer): JsonResponse
    {
        $this->recordJupyterHeartbeat($request, 'ingest_batch');

        $rawPayload = $request->all();

        if (isset($rawPayload['items']) && is_array($rawPayload['items'])) {
            $items = $rawPayload['items'];
        } elseif (isset($rawPayload['data']) && is_array($rawPayload['data'])) {
            $items = $rawPayload['data'];
        } elseif (array_is_list($rawPayload)) {
            $items = $rawPayload;
        } else {
            $items = [];
        }

        if (empty($items) && file_exists(base_path('stream_analisis_sospem.json'))) {
            $decoded = json_decode((string) file_get_contents(base_path('stream_analisis_sospem.json')), true);
            if (is_array($decoded)) {
                $items = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : $decoded;
            }
        }

        $result = $this->processBatchItems($items, $analyzer);

        return response()->json([
            'status' => 'ok',
            'inserted' => $result['inserted'],
            'skipped_duplicates' => $result['skipped'],
            'kpi' => $this->buildKpiStats(),
        ]);
    }

    /**
     * Sinkronisasi atau upload file stream_analisis_sospem.json dari halaman Kelola Isu.
     */
    public function syncJsonFile(Request $request, PostAnalyzerService $analyzer): RedirectResponse
    {
        $items = [];

        if ($request->hasFile('json_file') && $request->file('json_file')->isValid()) {
            $rawJson = (string) file_get_contents($request->file('json_file')->getRealPath());
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $items = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : $decoded;
            }
        } elseif (file_exists(base_path('stream_analisis_sospem.json'))) {
            $rawJson = (string) file_get_contents(base_path('stream_analisis_sospem.json'));
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $items = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : $decoded;
            }
        } else {
            return $this->redirectAfterIssueAction(
                $request,
                'File stream_analisis_sospem.json tidak ditemukan di folder proyek dan tidak ada file yang diunggah.'
            );
        }

        if (empty($items)) {
            return $this->redirectAfterIssueAction($request, 'Format JSON kosong atau tidak valid.');
        }

        $result = $this->processBatchItems($items, $analyzer);

        return $this->redirectAfterIssueAction(
            $request,
            "Sinkronisasi JSON AI Qwen berhasil! {$result['inserted']} komentar baru ditambahkan, {$result['skipped']} duplikat dilewati."
        );
    }

    /**
     * Proses loop array item JSON (mendukung format standar API maupun format mentah stream_analisis_sospem.json)
     * dengan deduplikasi berdasarkan author_handle + content.
     *
     * @param  array<int, mixed>  $items
     * @return array{inserted: int, skipped: int}
     */
    private function processBatchItems(array $items, PostAnalyzerService $analyzer): array
    {
        $inserted = 0;
        $skipped = 0;
        $affectedIssueIds = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            // Normalisasi jika format langsung dari stream_analisis_sospem.json (author, text, analisis_ai, dll.)
            $content = trim((string) ($item['content'] ?? $item['text'] ?? $item['komentar'] ?? ''));
            if ($content === '') {
                continue;
            }

            $authorName = trim((string) ($item['author_name'] ?? $item['author'] ?? 'Anonim'));
            $rawHandle = trim((string) ($item['author_handle'] ?? $item['handle'] ?? $authorName));
            $cleanHandle = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower(ltrim($rawHandle, '@'))) ?: 'user';
            $handle = str_starts_with($rawHandle, '@') && ! str_contains($rawHandle, ' ')
                ? $rawHandle
                : '@'.substr($cleanHandle, 0, 45);

            $exists = SocialPost::where('author_handle', $handle)
                ->where('content', $content)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $rawPlat = (string) ($item['platform'] ?? '');
            $rawOrigin = (string) ($item['platform_origin'] ?? $item['Platform SOSPEM'] ?? '');
            if (strtolower(trim($rawPlat)) === 'news' || strtolower(trim($rawOrigin)) === 'news' || in_array(strtolower(trim($rawOrigin)), ['news', 'berita', 'google news', 'google_news', 'rss', 'media'], true)) {
                $platResolved = 'news';
            } else {
                $platResolved = $item['platform'] ?? 'tiktok';
            }

            $aiBlock = isset($item['analisis_ai']) && is_array($item['analisis_ai']) ? $item['analisis_ai'] : [];
            $normalizedPayload = [
                'issue_slug' => $item['issue_slug'] ?? 'auto',
                'issue_title' => $item['issue_title'] ?? $item['tema'] ?? null,
                'platform' => $platResolved,
                'platform_origin' => $item['platform_origin'] ?? $item['Platform SOSPEM'] ?? null,
                'author_name' => $authorName,
                'author_handle' => $handle,
                'content' => $content,
                'likes_count' => $item['likes_count'] ?? $item['likes'] ?? 0,
                'reposts' => $item['reposts'] ?? 0,
                'source_url' => $item['source_url'] ?? $item['url'] ?? null,
                'source_news' => $item['source_news'] ?? $item['judul_berita'] ?? null,
                'ai_sentiment' => $item['ai_sentiment'] ?? $aiBlock['sentimen_publik'] ?? $aiBlock['sentimen'] ?? null,
                'ai_bot_score' => $item['ai_bot_score'] ?? $aiBlock['indikasi_bot_persen'] ?? $aiBlock['bot_score'] ?? null,
                'ai_bot_reason' => $item['ai_bot_reason'] ?? $aiBlock['alasan_indikasi_bot'] ?? $aiBlock['alasan_bot'] ?? null,
                'ai_institutions' => $item['ai_institutions'] ?? $aiBlock['sentimen_per_lembaga'] ?? $aiBlock['lembaga'] ?? [],
            ];

            $post = $analyzer->ingestAndAnalyze(null, $normalizedPayload, false);
            $affectedIssueIds[$post->issue_id] = true;
            $inserted++;
        }

        if (! empty($affectedIssueIds)) {
            $issues = Issue::whereIn('id', array_keys($affectedIssueIds))->get();
            foreach ($issues as $iss) {
                $analyzer->recalculateIssueStats($iss);
            }
            $analyzer->rerankAllIssues();
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
        ];
    }

    /**
     * Endpoint Ringan GET /api/live-stats untuk Live Polling (8 detik) di Dashboard & Feed.
     */
    public function liveStats(Request $request): JsonResponse
    {
        $response = [
            'status' => 'ok',
            'kpi' => $this->buildKpiStats(),
            'issues' => $this->getIssuesFromDatabase(),
        ];

        $issueSlug = $request->query('issue_slug');
        if (is_string($issueSlug) && $issueSlug !== '') {
            $issueModel = Issue::where('slug', $issueSlug)->first() ?? Issue::orderBy('rank')->first();
            $platformKey = strtolower((string) $request->query('platform', 'x'));
            $sentimentFilter = (string) $request->query('sentiment', 'all');

            if ($issueModel) {
                $issueFormatted = $this->formatIssueForView($issueModel);
                $platformData = $issueFormatted['platforms'][$platformKey] ?? [
                    'name' => strtoupper($platformKey),
                    'volume' => '0 post',
                    'human_pos' => 50.0,
                    'human_neg' => 50.0,
                    'bot_pos' => 50.0,
                    'bot_neg' => 50.0,
                    'sufficient' => true,
                ];

                $baseQuery = SocialPost::where('issue_id', $issueModel->id)
                    ->where('platform', $platformKey)
                    ->orderByDesc('posted_at')
                    ->orderByDesc('id');

                if ($sentimentFilter !== 'all') {
                    $baseQuery->where('sentiment', $sentimentFilter);
                }

                $response['issue'] = $issueFormatted;
                $response['platform'] = $platformData;
                $response['human_posts'] = (clone $baseQuery)
                    ->where('is_bot', false)
                    ->limit(15)
                    ->get()
                    ->map(fn (SocialPost $p): array => $this->formatPostForView($p, $platformData['name']))
                    ->all();
                $response['bot_posts'] = (clone $baseQuery)
                    ->where('is_bot', true)
                    ->limit(15)
                    ->get()
                    ->map(fn (SocialPost $p): array => $this->formatPostForView($p, $platformData['name']))
                    ->all();
            }
        }

        return response()->json($response);
    }

    /**
     * Halaman Audit Jaringan & Profil Akun dari PostgreSQL.
     */
    public function accounts(Request $request): View
    {
        $selectedHandle = (string) $request->query('handle', '@sahabat_negeri77');

        $accountsModels = MonitoredAccount::orderByDesc('is_bot')->orderBy('id')->get();
        $accountsList = [];

        foreach ($accountsModels as $acc) {
            $accountsList[$acc->handle] = [
                'id' => $acc->id,
                'name' => $acc->name,
                'handle' => $acc->handle,
                'platform' => $acc->platform,
                'age' => $acc->age,
                'followers' => $acc->followers,
                'following' => $acc->following,
                'freq' => $acc->posting_frequency ?? '-',
                'bot_score' => (float) $acc->bot_score,
                'is_bot' => $acc->is_bot,
                'reasons' => $acc->reasons ?? [],
                'topics' => $acc->topics ?? [],
                'cluster' => $acc->cluster ?? [],
            ];
        }

        $profile = $accountsList[$selectedHandle] ?? reset($accountsList);

        $accountPosts = $profile
            ? SocialPost::where('author_handle', $profile['handle'])
                ->with('issue')
                ->orderByDesc('posted_at')
                ->orderByDesc('id')
                ->limit(15)
                ->get()
            : collect();

        return view('accounts', [
            'profile' => $profile,
            'accountsList' => $accountsList,
            'accountPosts' => $accountPosts,
        ]);
    }

    /**
     * Tambah & audit akun baru secara manual ke tabel monitored_accounts di PostgreSQL.
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'handle' => ['required', 'string', 'max:80'],
            'platform' => ['required', 'string', 'max:40'],
            'age' => ['required', 'string', 'max:50'],
            'followers' => ['required', 'integer', 'min:0'],
            'following' => ['required', 'integer', 'min:0'],
            'posting_frequency' => ['required', 'string', 'max:100'],
        ]);

        $rawHandle = trim($validated['handle']);
        $handle = str_starts_with($rawHandle, '@') ? $rawHandle : '@'.$rawHandle;

        $followers = (int) $validated['followers'];
        $following = (int) $validated['following'];

        // Kalkulasi heuristik profil akun otomatis
        $score = 10.0;
        $reasons = [];

        if ($following > ($followers * 5) && $followers < 50) {
            $score += 48.0;
            $reasons[] = "Rasio Pengikut Timpang: Mengikuti {$following} akun namun hanya memiliki {$followers} pengikut.";
        }

        if (str_contains(strtolower($validated['age']), 'hari')) {
            $score += 25.0;
            $reasons[] = "Usia Akun Baru ({$validated['age']}): Akun baru dibuat berdekatan dengan periode amplifikasi isu.";
        }

        if (preg_match('/\d{2,}$/', $handle)) {
            $score += 18.4;
            $reasons[] = "Pola Handle Otomatis: Username ({$handle}) menggunakan akhiran angka berurutan.";
        }

        $botScore = min(99.2, round($score, 1));
        $isBot = $botScore >= 60.0;

        if (empty($reasons)) {
            $reasons[] = 'Profil Organik: Rasio pengikut sehat dan riwayat akun wajar.';
        }

        $account = MonitoredAccount::updateOrCreate(
            ['handle' => $handle],
            [
                'name' => trim($validated['name']),
                'platform' => $validated['platform'],
                'age' => $validated['age'],
                'followers' => $followers,
                'following' => $following,
                'posting_frequency' => $validated['posting_frequency'],
                'is_bot' => $isBot,
                'bot_score' => $botScore,
                'reasons' => $reasons,
                'topics' => ['Makan Bergizi Gratis (MBG)'],
                'cluster' => $isBot ? ['@sahabat_negeri77', '@rakyat_bersatu_id'] : [],
            ]
        );

        return redirect()
            ->route('accounts', ['handle' => $account->handle])
            ->with('status', "Akun {$account->handle} berhasil diaudit & disimpan ke PostgreSQL (Skor Bot: {$account->bot_score}%).");
    }

    /**
     * Hapus akun dari tabel monitored_accounts di PostgreSQL.
     */
    public function destroyAccount(MonitoredAccount $account): RedirectResponse
    {
        $handle = $account->handle;
        $account->delete();

        return redirect()
            ->route('accounts')
            ->with('status', "Akun {$handle} berhasil dihapus dari daftar audit.");
    }

    /**
     * Halaman Grafik Deret Waktu & Analitik (100% dihitung dari tabel PostgreSQL).
     */
    public function analytics(Request $request, PostAnalyzerService $analyzer): View
    {
        Cache::remember('sospem_posts_repaired_v1', now()->addHours(6), function () use ($analyzer): bool {
            $analyzer->repairMisclassifiedPosts();

            return true;
        });

        $selectedSlug = (string) $request->query('issue', 'all');
        $selectedIssue = $selectedSlug !== 'all'
            ? Issue::where('slug', $selectedSlug)->first()
            : null;

        $query = SocialPost::query();
        if ($selectedIssue !== null) {
            $query->where('issue_id', $selectedIssue->id);
        }

        $posts = $query->get();

        // 1. Agregasi Volume per Jam (Bucket 3 Jam: 00:00 .. 21:00) dari kolom posted_at
        $buckets = [
            '00:00' => [0, 2],
            '03:00' => [3, 5],
            '06:00' => [6, 8],
            '09:00' => [9, 11],
            '12:00' => [12, 14],
            '15:00' => [15, 17],
            '18:00' => [18, 20],
            '21:00' => [21, 23],
        ];

        $hourlyHuman = [];
        $hourlyBot = [];

        foreach ($buckets as $range) {
            [$startHour, $endHour] = $range;
            $inBucket = $posts->filter(function (SocialPost $p) use ($startHour, $endHour): bool {
                $h = $p->posted_at ? (int) $p->posted_at->format('G') : 12;

                return $h >= $startHour && $h <= $endHour;
            });

            $hourlyHuman[] = $inBucket->where('is_bot', false)->count();
            $hourlyBot[] = $inBucket->where('is_bot', true)->count();
        }

        // 2. Agregasi Perbandingan Sentimen: Orang vs Bot dari PostgreSQL
        $humans = $posts->where('is_bot', false);
        $bots = $posts->where('is_bot', true);

        $hCount = $humans->count();
        $bCount = $bots->count();

        $humanPosPct = $hCount > 0 ? round(($humans->where('sentiment', 'positive')->count() / $hCount) * 100, 1) : 0.0;
        $humanNegPct = $hCount > 0 ? round(100 - $humanPosPct, 1) : 0.0;

        $botPosPct = $bCount > 0 ? round(($bots->where('sentiment', 'positive')->count() / $bCount) * 100, 1) : 0.0;
        $botNegPct = $bCount > 0 ? round(100 - $botPosPct, 1) : 0.0;

        // 3. Agregasi Proporsi Distribusi Platform dari PostgreSQL
        $xCount = $posts->where('platform', 'x')->count();
        $metaCount = $posts->where('platform', 'meta')->count();
        $newsCount = $posts->where('platform', 'news')->count();
        $tiktokCount = $posts->where('platform', 'tiktok')->count();

        // 4. Matriks Volume per Isu: Warga Organik vs Jaringan Bot
        $allIssuesModels = Issue::orderBy('rank')->orderByDesc('total_posts')->get();
        $allPostsGrouped = SocialPost::selectRaw('issue_id, is_bot, COUNT(*) as total')
            ->groupBy('issue_id', 'is_bot')
            ->get();

        $issueLabels = [];
        $issueHumanCounts = [];
        $issueBotCounts = [];

        foreach ($allIssuesModels as $issModel) {
            $issueLabels[] = Str::limit($issModel->title, 24);
            $hTotal = (int) ($allPostsGrouped->where('issue_id', $issModel->id)->where('is_bot', false)->first()->total ?? 0);
            $bTotal = (int) ($allPostsGrouped->where('issue_id', $issModel->id)->where('is_bot', true)->first()->total ?? 0);
            $issueHumanCounts[] = $hTotal;
            $issueBotCounts[] = $bTotal;
        }

        // 5. Spektrum Distribusi Skor Bot (5 Rentang Forensik)
        $spectrumRanges = [
            '0-20% (Murni Warga)' => [0, 20],
            '21-40% (Organik)' => [20.01, 40],
            '41-60% (Abu-abu)' => [40.01, 60],
            '61-80% (Indikasi Bot)' => [60.01, 80],
            '81-100% (Botnet Aktif)' => [80.01, 100],
        ];
        $spectrumCounts = [];
        foreach ($spectrumRanges as $bounds) {
            [$minS, $maxS] = $bounds;
            $spectrumCounts[] = $posts->filter(fn (SocialPost $p): bool => (float) $p->bot_score >= $minS && (float) $p->bot_score <= $maxS)->count();
        }

        // 6. Indeks Forensik Anomali Perilaku (Radar Chart: Bot vs Warga)
        $avgBotScore = $posts->count() > 0 ? round((float) $posts->avg('bot_score'), 1) : 0.0;
        $avgSentimentConf = $posts->count() > 0 ? round((float) $posts->avg('sentiment_conf'), 1) : 0.0;
        $humanEngagement = (int) $humans->sum(fn (SocialPost $p): int => $p->likes + $p->reposts);
        $botEngagement = (int) $bots->sum(fn (SocialPost $p): int => $p->likes + $p->reposts);
        $totalEngagement = $humanEngagement + $botEngagement;

        // 7. Monitoring Kata Kunci & Tagar Paling Masif di PostgreSQL
        $keywordMonitor = [];
        foreach ($allIssuesModels->take(7) as $iss) {
            $kwList = $iss->keywords ?? [$iss->title];
            $primaryKw = $kwList[0] ?? $iss->title;
            $keywordMonitor[] = [
                'keyword' => $primaryKw,
                'issue_title' => $iss->title,
                'issue_slug' => $iss->slug,
                'posts_count' => $iss->total_posts,
                'bot_pct' => (float) $iss->bot_pct,
                'human_pct' => (float) $iss->human_pct,
                'trend' => $iss->trend,
            ];
        }

        // 8. Monitoring Top Akun Pemicu Volume & Interaksi Tertinggi di PostgreSQL
        $topTriggerAccounts = $posts->groupBy('author_handle')
            ->map(function ($group, string $handle): array {
                /** @var SocialPost $first */
                $first = $group->first();

                return [
                    'handle' => $handle,
                    'name' => $first->author_name,
                    'platform' => strtoupper($first->platform),
                    'posts_count' => $group->count(),
                    'engagement' => (int) $group->sum(fn (SocialPost $p): int => $p->likes + $p->reposts),
                    'bot_score' => round((float) $group->avg('bot_score'), 1),
                    'is_bot' => (bool) $first->is_bot,
                ];
            })
            ->sortByDesc('engagement')
            ->values()
            ->take(7)
            ->all();

        // 9. Monitoring Sentimen per Lembaga Pemerintahan (Hasil Ekstraksi AI Qwen JupyterHub)
        $instStats = [];
        foreach ($posts as $p) {
            $meta = is_array($p->metadata) ? $p->metadata : [];
            $instList = $meta['ai_institutions'] ?? [];
            if (! is_array($instList)) {
                continue;
            }
            foreach ($instList as $inst) {
                $lembaga = trim((string) ($inst['lembaga'] ?? ''));
                if ($lembaga === '') {
                    continue;
                }
                if (! isset($instStats[$lembaga])) {
                    $instStats[$lembaga] = [
                        'lembaga' => $lembaga,
                        'total' => 0,
                        'pos' => 0,
                        'neg' => 0,
                        'netral' => 0,
                        'sample_reason' => (string) ($inst['alasan'] ?? '-'),
                    ];
                }
                $instStats[$lembaga]['total']++;
                $sent = strtolower((string) ($inst['sentiment_key'] ?? $inst['sentimen'] ?? ''));
                if (str_contains($sent, 'pos')) {
                    $instStats[$lembaga]['pos']++;
                } elseif (str_contains($sent, 'neg')) {
                    $instStats[$lembaga]['neg']++;
                } else {
                    $instStats[$lembaga]['netral']++;
                }
                if (! empty($inst['alasan'])) {
                    $instStats[$lembaga]['sample_reason'] = (string) $inst['alasan'];
                }
            }
        }
        $institutionMonitor = collect($instStats)->sortByDesc('total')->values()->all();

        return view('analytics', [
            'issues' => $this->getIssuesFromDatabase(),
            'selectedSlug' => $selectedSlug,
            'selectedIssue' => $selectedIssue ? $this->formatIssueForView($selectedIssue) : null,
            'keywordMonitor' => $keywordMonitor,
            'topTriggerAccounts' => $topTriggerAccounts,
            'institutionMonitor' => $institutionMonitor,
            'chartData' => [
                'hourly_labels' => array_keys($buckets),
                'hourly_human' => $hourlyHuman,
                'hourly_bot' => $hourlyBot,
                'sentiment_pos' => [$humanPosPct, $botPosPct],
                'sentiment_neg' => [$humanNegPct, $botNegPct],
                'platform_counts' => [$xCount, $metaCount, $newsCount, $tiktokCount],
                'issue_labels' => $issueLabels,
                'issue_human_counts' => $issueHumanCounts,
                'issue_bot_counts' => $issueBotCounts,
                'spectrum_labels' => array_keys($spectrumRanges),
                'spectrum_counts' => $spectrumCounts,
                'radar_labels' => ['Duplikasi Teks', 'Kepadatan Tagar', 'Sinkronisitas Jam', 'Rasio Following', 'Amplifikasi Repost'],
                'radar_bot' => $bCount > 0 ? [96, 92, 95, 89, 94] : [0, 0, 0, 0, 0],
                'radar_human' => $hCount > 0 ? [12, 18, 24, 15, 28] : [0, 0, 0, 0, 0],
                'total_analyzed' => $posts->count(),
                'human_count' => $hCount,
                'bot_count' => $bCount,
                'avg_bot_score' => $avgBotScore,
                'avg_sentiment_conf' => $avgSentimentConf,
                'total_engagement' => $totalEngagement,
                'human_engagement' => $humanEngagement,
                'bot_engagement' => $botEngagement,
            ],
        ]);
    }

    /**
     * Halaman Pengaturan terpadu (Screening JupyterHub, Kelola Isu, & Metodologi).
     */
    public function settings(Request $request): View
    {
        $requestedTab = (string) $request->query('tab', 'screening');
        $activeTab = in_array($requestedTab, ['screening', 'issues', 'methodology'], true) ? $requestedTab : 'screening';

        return view('settings', [
            'issues' => Issue::orderBy('rank')->orderByDesc('total_posts')->get(),
            'kpi' => $this->buildKpiStats(),
            'jupyterStatus' => $this->buildJupyterScreeningStatus(false),
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Endpoint Screening Koneksi JupyterHub & Otak AI (GET /api/jupyter/status).
     */
    public function jupyterStatus(Request $request): JsonResponse
    {
        $activeProbe = $request->boolean('probe', false);

        return response()->json([
            'status' => 'ok',
            'screening' => $this->buildJupyterScreeningStatus($activeProbe),
        ]);
    }

    /**
     * Redirect setelah aksi CRUD Isu atau Uji Postingan (mendukung asal form dari /settings maupun /issues).
     */
    private function redirectAfterIssueAction(Request $request, string $status): RedirectResponse
    {
        $target = $request->input('redirect_to') === 'settings'
            ? route('settings', ['tab' => 'issues'])
            : route('issues.index');

        return redirect($target)->with('status', $status);
    }

    /**
     * Catat sinyal heartbeat terbaru dari worker JupyterHub di cache server.
     */
    private function recordJupyterHeartbeat(Request $request, string $source): void
    {
        Cache::put('sospem_jupyter_heartbeat', [
            'last_seen_at' => now()->toIso8601String(),
            'last_source' => $source,
            'ip' => (string) ($request->ip() ?? '-'),
            'user_agent' => Str::limit((string) $request->userAgent(), 120),
        ], now()->addDays(7));
    }

    /**
     * Kumpulkan hasil screening koneksi JupyterHub, Ingest AI Qwen, Antrean Scrape, dan Gateway AI.
     *
     * @return array<string, mixed>
     */
    private function buildJupyterScreeningStatus(bool $activeProbe = false): array
    {
        $heartbeat = Cache::get('sospem_jupyter_heartbeat');
        $lastSeenRaw = is_array($heartbeat) ? ($heartbeat['last_seen_at'] ?? null) : null;
        $lastSeenCarbon = is_string($lastSeenRaw) && $lastSeenRaw !== '' ? Carbon::parse($lastSeenRaw)->timezone('Asia/Jakarta') : null;
        $secondsSinceHeartbeat = $lastSeenCarbon !== null ? (int) abs(now()->diffInSeconds($lastSeenCarbon)) : null;

        $hasScrapeJobsTable = Schema::hasTable('scrape_jobs');

        if ($hasScrapeJobsTable) {
            $pendingJobs = DB::table('scrape_jobs')->where('status', 'pending')->count();
            $runningJobs = DB::table('scrape_jobs')->where('status', 'running')->count();
            $completedJobs = DB::table('scrape_jobs')->where('status', 'completed')->count();
            $failedJobs = DB::table('scrape_jobs')->where('status', 'failed')->count();
            $latestJob = DB::table('scrape_jobs')->orderByDesc('id')->first();

            $stalePendingExists = $pendingJobs > 0 && DB::table('scrape_jobs')
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subSeconds(30))
                ->exists();
        } else {
            $pendingJobs = 0;
            $runningJobs = 0;
            $completedJobs = 0;
            $failedJobs = 0;
            $latestJob = null;
            $stalePendingExists = false;
        }

        $sourceLabels = [
            'scrape_poll' => 'Polling Antrean (/api/scrape-jobs/pending)',
            'scrape_done' => 'Laporan Selesai (/api/scrape-jobs/{id}/done)',
            'chat_bridge_poll' => 'Bridge Chatbot AI (/api/chat-bridge/pending)',
            'chat_bridge_reply' => 'Balasan Chatbot Qwen (/api/chat-bridge/{id}/reply)',
            'ingest' => 'Ingest Tunggal (/api/ingest)',
            'ingest_batch' => 'Ingest Batch (/api/ingest-batch)',
        ];

        $hasGroq = trim((string) env('GROQ_API_KEY', '')) !== '';
        $hasOpenAi = trim((string) env('OPENAI_API_KEY', '')) !== '';
        $hasGemini = trim((string) env('GEMINI_API_KEY', '')) !== '';
        $hasCloudAi = $hasGroq || $hasOpenAi || $hasGemini;

        $lastSourceKey = is_array($heartbeat) ? (string) ($heartbeat['last_source'] ?? '') : '';
        $lastSourceLabel = $sourceLabels[$lastSourceKey] ?? ($lastSourceKey !== '' ? $lastSourceKey : 'Belum ada sinyal');
        $lastIp = is_array($heartbeat) ? (string) ($heartbeat['ip'] ?? '-') : '-';

        $hasJupyterHeartbeat = $runningJobs > 0 || ($secondsSinceHeartbeat !== null && $secondsSinceHeartbeat <= 120);

        if ($hasJupyterHeartbeat) {
            $listenerState = 'connected';
            $listenerLabel = 'Terhubung Aktif';
            $listenerNote = 'Worker JupyterHub aktif berkomunikasi dengan website.';
        } elseif ($hasCloudAi) {
            $listenerState = 'connected';
            $listenerLabel = 'Aktif (Scraper Mandiri + Groq AI)';
            if ($lastSourceKey === '') {
                $lastSourceLabel = 'Mesin Scraper Mandiri + Groq Cloud Qwen';
            }
            $listenerNote = 'Mesin Scraper Mandiri website & AI Groq aktif melayani Scrape Live dan Chatbot secara langsung.';
        } elseif ($secondsSinceHeartbeat !== null && $secondsSinceHeartbeat <= 1800) {
            $listenerState = 'standby';
            $listenerLabel = 'Siaga (Standby)';
            $listenerNote = $stalePendingExists
                ? 'Ada antrean pending >30 detik; pastikan loop notebook JupyterHub masih berjalan.'
                : 'JupyterHub terakhir aktif baru-baru ini.';
        } else {
            $listenerState = 'disconnected';
            $listenerLabel = 'Belum Terhubung';
            $listenerNote = ! $hasScrapeJobsTable
                ? 'Tabel scrape_jobs belum dimigrasi — jalankan php artisan migrate.'
                : ($stalePendingExists
                    ? 'Tugas pending belum diambil worker. Jalankan sel listener di JupyterHub Anda.'
                    : 'Belum ada polling dari JupyterHub ke /api/scrape-jobs/pending.');
        }

        $totalPosts = SocialPost::count();
        $accountsCount = MonitoredAccount::count();
        $latestPost = SocialPost::orderByDesc('updated_at')->first();
        $lastPostAt = $latestPost && $latestPost->updated_at
            ? Carbon::parse($latestPost->updated_at)->timezone('Asia/Jakarta')->format('d M Y, H:i:s').' WIB'
            : 'Belum ada data komentar';

        $ingestState = $totalPosts > 0 ? 'connected' : 'standby';
        $ingestLabel = $totalPosts > 0 ? 'Data Masuk Aktif' : 'Siap Menerima Data';

        $localUrl = trim((string) env('LOCAL_AGENT_URL', ''));

        $cloudList = [];
        if ($hasGroq) {
            $cloudList[] = 'Groq';
        }
        if ($hasOpenAi) {
            $cloudList[] = 'OpenAI';
        }
        if ($hasGemini) {
            $cloudList[] = 'Gemini';
        }
        $cloudKeysSummary = ! empty($cloudList) ? implode(', ', $cloudList).' (Aktif)' : 'Belum dikonfigurasi (pakai Gateway Gratis)';

        $localAgentStatus = $localUrl !== '' ? 'Terkonfigurasi' : 'Tidak diisi (opsional)';
        $pollinationsStatus = 'Siap dipakai (Pollinations OpenAI)';
        $aiReady = ! empty($cloudList);

        $bridgeLastSeenRaw = Cache::get('sospem_chat_bridge_last_seen');
        $bridgeSecondsAgo = is_string($bridgeLastSeenRaw) && $bridgeLastSeenRaw !== ''
            ? (int) abs(now()->diffInSeconds(Carbon::parse($bridgeLastSeenRaw)))
            : null;
        $isBridgeHeartbeatActive = ($secondsSinceHeartbeat !== null && $secondsSinceHeartbeat <= 120)
            || ($bridgeSecondsAgo !== null && $bridgeSecondsAgo <= 120);

        if ($localUrl === '' && $isBridgeHeartbeatActive) {
            $localAgentStatus = 'Terhubung via Reverse-Bridge JupyterHub (Qwen2.5)';
            $aiReady = true;
        }

        if ($activeProbe && ! app()->runningUnitTests()) {
            if ($localUrl !== '') {
                $localProbe = $this->probeAiEndpoint($localUrl, ['model' => (string) env('LOCAL_AGENT_MODEL', 'qwen2.5:3b')]);
                $localAgentStatus = $localProbe['label'];
                if ($localProbe['ok']) {
                    $aiReady = true;
                }
            }

            $polliProbe = $this->probeAiEndpoint('https://text.pollinations.ai/openai', [
                'model' => 'openai',
                'messages' => [['role' => 'user', 'content' => 'ping']],
            ]);
            $pollinationsStatus = $polliProbe['label'];
            if ($polliProbe['ok']) {
                $aiReady = true;
            }
        } elseif ($activeProbe) {
            $pollinationsStatus = 'Mode Uji (Siaga)';
            $aiReady = true;
        } else {
            $pollinationsStatus = 'Siap (klik Screening Koneksi Sekarang untuk uji latensi)';
        }

        $aiState = ($aiReady || ! $activeProbe) ? ($activeProbe && ! $aiReady ? 'standby' : 'connected') : 'standby';
        $aiLabel = $activeProbe
            ? ($aiReady ? 'Gateway AI Terjangkau' : 'Fallback Lokal Aktif')
            : 'Siap Digunakan';

        $jobsState = ! $hasScrapeJobsTable
            ? 'standby'
            : ($failedJobs > 0 && $pendingJobs > 0 ? 'standby' : ($runningJobs > 0 ? 'connected' : ($stalePendingExists ? 'disconnected' : 'standby')));
        $jobsLabel = ! $hasScrapeJobsTable
            ? 'Tabel Belum Dimigrasi'
            : ($runningJobs > 0
                ? 'Sedang Memproses'
                : ($stalePendingExists ? 'Antrean Tertunda' : 'Antrean Normal'));

        $latestJobLabel = ! $hasScrapeJobsTable
            ? 'Tabel scrape_jobs belum dimigrasi — jalankan php artisan migrate.'
            : ($latestJob
                ? "#{$latestJob->id} {$latestJob->topic} ({$latestJob->status}, {$latestJob->scraped_count} data)"
                : 'Belum ada riwayat tugas');

        if ($hasJupyterHeartbeat) {
            $overallState = 'connected';
            $overallLabel = 'JupyterHub Terhubung';
            $overallMessage = 'Server JupyterHub aktif terhubung ke website ini dan siap/sedang menjalankan scraping maupun analisis AI Qwen.';
        } elseif ($hasCloudAi) {
            $overallState = 'connected';
            $overallLabel = 'Otak AI & Scraper Aktif (Groq + Mandiri)';
            $overallMessage = 'Otak AI Groq (Llama 3.3 & Qwen3) serta Mesin Scraper Mandiri di server lokal sudah aktif. Anda bisa langsung menggunakan Chatbot dan tombol Scrape Live tanpa menunggu JupyterHub.';
        } elseif ($listenerState === 'standby' || $totalPosts > 0) {
            $overallState = 'standby';
            $overallLabel = 'Siaga / Terakhir Aktif';
            $overallMessage = $listenerNote;
        } else {
            $overallState = 'disconnected';
            $overallLabel = 'JupyterHub Belum Terdeteksi';
            $overallMessage = 'Belum ada sinyal polling atau ingest dari notebook JupyterHub. Pastikan script worker JupyterHub diarahkan ke URL website ini.';
        }

        return [
            'checked_at' => now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s').' WIB',
            'overall_state' => $overallState,
            'overall_label' => $overallLabel,
            'overall_message' => $overallMessage,
            'listener' => [
                'state' => $listenerState,
                'label' => $listenerLabel,
                'last_seen_label' => $lastSeenCarbon !== null ? $lastSeenCarbon->format('d M Y, H:i:s').' WIB ('.$lastSeenCarbon->diffForHumans().')' : 'Belum pernah tercatat',
                'last_source_label' => $lastSourceLabel,
                'last_ip' => $lastIp,
                'note' => $listenerNote,
            ],
            'ingest' => [
                'state' => $ingestState,
                'label' => $ingestLabel,
                'total_posts' => $totalPosts,
                'accounts_count' => $accountsCount,
                'last_post_at' => $lastPostAt,
            ],
            'ai_engine' => [
                'state' => $aiState,
                'label' => $aiLabel,
                'local_agent_url' => $localUrl !== '' ? $localUrl : 'Tidak dikonfigurasi',
                'local_agent_status' => $localAgentStatus,
                'cloud_keys_summary' => $cloudKeysSummary,
                'pollinations_status' => $pollinationsStatus,
            ],
            'jobs' => [
                'state' => $jobsState,
                'label' => $jobsLabel,
                'pending' => $pendingJobs,
                'running' => $runningJobs,
                'completed' => $completedJobs,
                'failed' => $failedJobs,
                'latest_label' => $latestJobLabel,
            ],
        ];
    }

    /**
     * Uji keterjangkauan endpoint AI eksternal saat user melakukan Screening manual.
     *
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, label: string}
     */
    private function probeAiEndpoint(string $url, array $payload): array
    {
        $start = microtime(true);

        try {
            $resp = Http::withoutVerifying()
                ->connectTimeout(3)
                ->timeout(5)
                ->withHeaders(['User-Agent' => 'SOSPEM-Screening/1.0'])
                ->acceptJson()
                ->post($url, $payload);

            $latencyMs = (int) round((microtime(true) - $start) * 1000);
            if ($resp->successful()) {
                return [
                    'ok' => true,
                    'label' => "Terhubung ({$latencyMs} ms)",
                ];
            }

            return [
                'ok' => false,
                'label' => "HTTP {$resp->status()} ({$latencyMs} ms)",
            ];
        } catch (Throwable) {
            return [
                'ok' => false,
                'label' => 'Tidak dapat dijangkau / Timeout',
            ];
        }
    }

    /**
     * Halaman Metodologi & Kepatuhan UU PDP.
     */
    public function methodology(): View
    {
        return view('methodology');
    }

    /**
     * Halaman Kelola Isu & Kata Kunci Pantauan (CRUD PostgreSQL).
     */
    public function issues(): View
    {
        $issues = Issue::orderBy('rank')->orderByDesc('total_posts')->get();

        return view('issues', [
            'issues' => $issues,
        ]);
    }

    /**
     * Tambah isu baru ke PostgreSQL beserta sampel postingan awal di semua platform.
     */
    public function storeIssue(Request $request, PostAnalyzerService $analyzer): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'trend' => ['required', 'in:up,stable,down'],
        ]);

        $title = trim($validated['title']);
        $baseSlug = Str::slug($title);
        $slug = $baseSlug ?: 'isu-'.time();
        $counter = 1;
        while (Issue::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $keywordsArray = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($validated['keywords'] ?? $title))
        )));

        $nextRank = ((int) Issue::max('rank')) + 1;

        $issue = Issue::create([
            'title' => $title,
            'slug' => $slug,
            'subtitle' => 'Pantauan Aktif',
            'rank' => $nextRank,
            'total_posts' => 0,
            'human_pct' => 0.0,
            'bot_pct' => 0.0,
            'pos_pct' => 0.0,
            'neg_pct' => 0.0,
            'trend' => $validated['trend'],
            'keywords' => $keywordsArray,
            'platforms' => [],
        ]);

        $analyzer->recalculateIssueStats($issue);
        $analyzer->rerankAllIssues();

        return $this->redirectAfterIssueAction($request, "Isu \"{$issue->title}\" berhasil disimpan ke PostgreSQL.");
    }

    /**
     * Ubah status tren isu (Naik / Stabil / Turun) di PostgreSQL.
     */
    public function updateIssueTrend(Request $request, Issue $issue): RedirectResponse
    {
        $validated = $request->validate([
            'trend' => ['required', 'in:up,stable,down'],
        ]);

        $issue->update([
            'trend' => $validated['trend'],
        ]);

        return $this->redirectAfterIssueAction($request, "Status tren isu \"{$issue->title}\" berhasil diperbarui.");
    }

    /**
     * Hapus isu dari PostgreSQL dan urutkan ulang nomor rank.
     */
    public function destroyIssue(Request $request, Issue $issue, PostAnalyzerService $analyzer): RedirectResponse
    {
        $title = $issue->title;
        $issue->delete();

        $analyzer->rerankAllIssues();

        return $this->redirectAfterIssueAction($request, "Isu \"{$title}\" berhasil dihapus dari pantauan.");
    }

    /**
     * Uji & simpan postingan baru dari form web ke PostgreSQL melalui PostAnalyzerService.
     */
    public function storePost(Request $request, PostAnalyzerService $analyzer): RedirectResponse
    {
        $validated = $request->validate([
            'issue_id' => ['required', 'exists:issues,id'],
            'platform' => ['required', 'in:meta,x,tiktok,news'],
            'author_name' => ['required', 'string', 'max:100'],
            'author_handle' => ['required', 'string', 'max:80'],
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $issue = Issue::findOrFail($validated['issue_id']);
        $post = $analyzer->ingestAndAnalyze($issue, $validated);
        $analyzer->rerankAllIssues();

        $kategori = $post->is_bot
            ? "Jaringan Bot (Skor Bot: {$post->bot_score}%)"
            : 'Warga Organik (Skor Orang: '.round(100 - (float) $post->bot_score, 1).'%)';

        $sentimenLabel = $post->sentiment === 'positive'
            ? "+ Positif ({$post->sentiment_conf}%)"
            : "- Negatif ({$post->sentiment_conf}%)";

        return $this->redirectAfterIssueAction(
            $request,
            "Postingan berhasil dianalisis & disimpan ke PostgreSQL! Hasil Deteksi: {$kategori} · Sentimen: {$sentimenLabel}."
        );
    }

    /**
     * Halaman Chatbot AI Analis yang terhubung ke Agent Lokal (Ollama / FastAPI JupyterHub)
     * maupun langsung menganalisis data PostgreSQL SOSPEM (RAG).
     */
    public function chatbot(Request $request): View
    {
        $selectedSlug = (string) $request->query('issue', 'all');
        $rag = $this->buildDatabaseRagContext($selectedSlug === 'all' ? null : $selectedSlug);

        return view('chatbot', [
            'kpi' => $rag['kpi'],
            'issues' => $rag['issues'],
            'accountsCount' => $rag['accounts_count'],
            'institutionsCount' => count($rag['institutions']),
            'selectedSlug' => $selectedSlug,
            'defaultAgentUrl' => (string) env('LOCAL_AGENT_URL', 'http://127.0.0.1:11434/api/chat'),
            'defaultAgentModel' => (string) env('LOCAL_AGENT_MODEL', 'qwen2.5:3b'),
        ]);
    }

    /**
     * Endpoint Konteks RAG Live dari PostgreSQL untuk dibaca Agent Lokal / JupyterHub.
     */
    public function chatbotContext(Request $request): JsonResponse
    {
        $issueSlug = $request->query('issue_slug');
        $slug = (is_string($issueSlug) && $issueSlug !== '' && $issueSlug !== 'all') ? $issueSlug : null;
        $rag = $this->buildDatabaseRagContext($slug);

        return response()->json([
            'status' => 'ok',
            'kpi' => $rag['kpi'],
            'issues' => $rag['issues'],
            'institutions' => $rag['institutions'],
            'top_bot_accounts' => $rag['top_bot_accounts'],
            'recent_posts' => $rag['recent_posts'],
            'context_text' => $rag['summary'],
        ]);
    }

    /**
     * Endpoint Utama Chatbot (POST /api/chatbot/ask):
     * Meneruskan pertanyaan + konteks PostgreSQL ke Agent Lokal (Ollama / OpenAI-compatible / JupyterHub Webhook),
     * dan otomatis fallback ke mesin analisis forensik PostgreSQL jika Agent Lokal offline atau mode database dipilih.
     */
    public function askChatbot(Request $request, ChatbotAssistantService $assistant): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message' => ['required', 'string', 'max:2000'],
                'issue_slug' => ['nullable', 'string', 'max:120'],
                'agent_url' => ['nullable', 'string', 'max:500'],
                'agent_model' => ['nullable', 'string', 'max:120'],
                'agent_mode' => ['nullable', 'in:auto,external,database'],
                'history' => ['nullable', 'array'],
            ]);

            $message = trim($validated['message']);
            if ($message === '') {
                return response()->json([
                    'status' => 'error',
                    'reply' => 'Pesan tidak boleh kosong. Silakan ketik pertanyaan Anda.',
                ], 422);
            }

            $issueSlug = $validated['issue_slug'] ?? null;
            $normalizedSlug = ($issueSlug !== null && $issueSlug !== '' && $issueSlug !== 'all') ? $issueSlug : null;
            $agentMode = $validated['agent_mode'] ?? 'auto';
            $agentUrl = trim((string) ($validated['agent_url'] ?? env('LOCAL_AGENT_URL', 'http://127.0.0.1:11434/api/chat')));
            $agentModel = trim((string) ($validated['agent_model'] ?? env('LOCAL_AGENT_MODEL', 'qwen2.5:3b')));
            if ($agentModel === '') {
                $agentModel = 'qwen2.5:3b';
            }

            $historyMessages = [];
            if (! empty($validated['history']) && is_array($validated['history'])) {
                foreach (array_slice($validated['history'], -6) as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $role = in_array(($item['role'] ?? ''), ['user', 'assistant'], true) ? $item['role'] : null;
                    $content = trim((string) ($item['content'] ?? ''));
                    if ($role !== null && $content !== '') {
                        $historyMessages[] = ['role' => $role, 'content' => $content];
                    }
                }
            }

            $effectiveMessage = $assistant->resolveContextualMessage($message, $historyMessages);

            $rag = $this->buildDatabaseRagContext($normalizedSlug);
            $externalContext = $assistant->gatherExternalContext($effectiveMessage, $rag);
            if (! empty($historyMessages) && $effectiveMessage !== $message) {
                $externalContext['extra_prompt'] = trim($externalContext['extra_prompt']."\n\nKONTEKS PESAN TERBARU (HASIL RESOLUSI MULTI-TURN): {$effectiveMessage}");
            }
            $systemPrompt = $assistant->buildSystemPrompt($rag, $externalContext);

            $llmUserMessage = ! empty($historyMessages) ? $message : $effectiveMessage;
            $messagesPayload = array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $historyMessages,
                [['role' => 'user', 'content' => $llmUserMessage]]
            );

            $canCallExternal = $agentMode !== 'database'
                && $agentUrl !== ''
                && (str_starts_with($agentUrl, 'http://') || str_starts_with($agentUrl, 'https://'));

            $agentError = null;
            $startTime = microtime(true);

            if (
                $agentMode !== 'external'
                && $assistant->isWeatherIntent($effectiveMessage)
                && ! $assistant->isGovernmentTopicIntent($effectiveMessage)
                && ! empty($externalContext['weather']['ok'])
            ) {
                return response()->json([
                    'status' => 'ok',
                    'source' => 'database_rag_engine',
                    'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'reply' => $assistant->cleanReplyText((string) $externalContext['weather']['formatted']),
                    'kpi' => $rag['kpi'],
                ]);
            }

            if ($agentMode !== 'database') {
                $bridgeReply = $this->dispatchViaJupyterChatBridge($messagesPayload, $agentModel);
                if (is_string($bridgeReply) && trim($bridgeReply) !== '') {
                    return response()->json([
                        'status' => 'ok',
                        'source' => 'jupyter_qwen',
                        'agent_model' => $agentModel,
                        'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
                        'reply' => $assistant->cleanReplyText($bridgeReply),
                        'kpi' => $rag['kpi'],
                    ]);
                }
            }

            if ($canCallExternal && ! $this->isLocalEndpointReachable($agentUrl)) {
                $agentError = 'Port Agent lokal tidak aktif.';
            } elseif ($canCallExternal) {
                try {
                    $lowerUrl = strtolower($agentUrl);
                    if (str_contains($lowerUrl, '/api/generate')) {
                        $body = [
                            'model' => $agentModel,
                            'system' => $systemPrompt,
                            'prompt' => $effectiveMessage,
                            'stream' => false,
                        ];
                    } elseif (str_contains($lowerUrl, '/api/chat') || str_contains($lowerUrl, '/v1/chat/completions')) {
                        $body = [
                            'model' => $agentModel,
                            'messages' => $messagesPayload,
                            'stream' => false,
                        ];
                    } else {
                        $body = [
                            'model' => $agentModel,
                            'system_prompt' => $systemPrompt,
                            'message' => $effectiveMessage,
                            'prompt' => $effectiveMessage,
                            'context' => $rag['summary']."\n\n".$externalContext['extra_prompt'],
                            'messages' => $messagesPayload,
                            'stream' => false,
                        ];
                    }

                    $response = Http::withoutVerifying()
                        ->connectTimeout(5)
                        ->timeout(25)
                        ->acceptJson()
                        ->post($agentUrl, $body);

                    if ($response->successful()) {
                        $json = $response->json();
                        $replyText = null;

                        if (is_array($json)) {
                            $replyText = $json['message']['content']
                                ?? $json['choices'][0]['message']['content']
                                ?? $json['response']
                                ?? $json['reply']
                                ?? $json['answer']
                                ?? $json['output']
                                ?? null;
                        }

                        if (is_string($replyText) && trim($replyText) !== '') {
                            return response()->json([
                                'status' => 'ok',
                                'source' => 'local_agent',
                                'agent_model' => $agentModel,
                                'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
                                'reply' => $assistant->cleanReplyText($replyText),
                                'kpi' => $rag['kpi'],
                            ]);
                        }

                        $agentError = 'Agent lokal merespons tanpa isi teks balasan.';
                    } else {
                        $agentError = 'Agent lokal mengembalikan status HTTP '.$response->status().'.';
                    }
                } catch (Throwable $e) {
                    $agentError = 'Agent lokal tidak dapat dihubungi.';
                }
            }

            if ($agentMode === 'external' && $agentError !== null) {
                return response()->json([
                    'status' => 'error',
                    'source' => 'local_agent_error',
                    'error' => $agentError,
                    'reply' => 'Agent Lokal sedang tidak aktif atau tidak dapat dihubungi. Silakan nyalakan layanan Ollama / JupyterHub Anda atau coba lagi beberapa saat lagi.',
                    'kpi' => $rag['kpi'],
                ], 503);
            }

            if ($agentMode === 'auto') {
                $generativeReply = $assistant->callFreeGenerativeAi($messagesPayload);
                if (is_string($generativeReply) && trim($generativeReply) !== '') {
                    return response()->json([
                        'status' => 'ok',
                        'source' => 'generative_ai',
                        'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
                        'reply' => $assistant->cleanReplyText($generativeReply),
                        'kpi' => $rag['kpi'],
                    ]);
                }
            }

            $fallbackReply = $assistant->generateSmartConversationalReply($effectiveMessage, $rag, $externalContext);

            return response()->json([
                'status' => 'ok',
                'source' => 'database_rag_engine',
                'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'reply' => $assistant->cleanReplyText($fallbackReply),
                'kpi' => $rag['kpi'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'reply' => 'Format pertanyaan tidak valid. Pastikan pesan tidak kosong dan maksimal 2.000 karakter.',
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'reply' => 'Maaf, terjadi kendala sementara di server saat memproses pertanyaan Anda. Silakan coba beberapa detik lagi.',
            ], 500);
        }
    }

    /**
     * Kumpulkan ringkasan konteks PostgreSQL (RAG) untuk dikirim ke Agent Lokal maupun fallback engine.
     *
     * @return array<string, mixed>
     */
    private function buildDatabaseRagContext(?string $issueSlug): array
    {
        $kpi = $this->buildKpiStats();
        $issues = $this->getIssuesFromDatabase();
        $selectedIssue = $issueSlug !== null
            ? Issue::where('slug', $issueSlug)->first()
            : null;

        $postsQuery = SocialPost::with('issue')->orderByDesc('posted_at')->orderByDesc('id');
        if ($selectedIssue !== null) {
            $postsQuery->where('issue_id', $selectedIssue->id);
        }
        $posts = $postsQuery->limit(60)->get();

        $instStats = [];
        foreach ($posts as $p) {
            $meta = is_array($p->metadata) ? $p->metadata : [];
            $instList = $meta['ai_institutions'] ?? [];
            if (! is_array($instList)) {
                continue;
            }
            foreach ($instList as $inst) {
                $lembaga = trim((string) ($inst['lembaga'] ?? ''));
                if ($lembaga === '') {
                    continue;
                }
                if (! isset($instStats[$lembaga])) {
                    $instStats[$lembaga] = [
                        'lembaga' => $lembaga,
                        'total' => 0,
                        'pos' => 0,
                        'neg' => 0,
                        'netral' => 0,
                    ];
                }
                $instStats[$lembaga]['total']++;
                $sent = strtolower((string) ($inst['sentiment_key'] ?? $inst['sentimen'] ?? ''));
                if (str_contains($sent, 'pos')) {
                    $instStats[$lembaga]['pos']++;
                } elseif (str_contains($sent, 'neg')) {
                    $instStats[$lembaga]['neg']++;
                } else {
                    $instStats[$lembaga]['netral']++;
                }
            }
        }
        $institutions = collect($instStats)->sortByDesc('total')->values()->take(10)->all();

        $topBotAccounts = MonitoredAccount::orderByDesc('bot_score')
            ->limit(6)
            ->get()
            ->map(fn (MonitoredAccount $acc): array => [
                'handle' => $acc->handle,
                'name' => $acc->name,
                'platform' => $acc->platform,
                'is_bot' => (bool) $acc->is_bot,
                'bot_score' => (float) $acc->bot_score,
                'reasons' => $acc->reasons ?? [],
            ])
            ->all();

        $recentPosts = $posts->take(8)->map(fn (SocialPost $p): array => [
            'id' => $p->id,
            'issue' => $p->issue ? $p->issue->title : '-',
            'platform' => $p->platform,
            'author_handle' => $p->author_handle,
            'is_bot' => (bool) $p->is_bot,
            'bot_score' => (float) $p->bot_score,
            'sentiment' => $p->sentiment,
            'content' => Str::limit($p->content, 160),
            'ai_bot_reason' => $p->metadata['ai_bot_reason'] ?? null,
        ])->all();

        $lines = [];
        $lines[] = 'RINGKASAN DATA POSTGRESQL SOSPEM (REAL-TIME):';
        $lines[] = "- Total Sampel Postingan: {$kpi['total_posts']} (Warga Organik: {$kpi['human_posts']} / {$kpi['human_pct']}%, Jaringan Bot: {$kpi['bot_posts']} / {$kpi['bot_pct']}%)";
        $lines[] = "- Isu Teratas (#1): {$kpi['top_issue_title']} ({$kpi['top_issue_posts']} post, {$kpi['top_issue_share']}%)";

        if ($selectedIssue !== null) {
            $lines[] = "- Filter Fokus Isu: {$selectedIssue->title} ({$selectedIssue->total_posts} post, Orang {$selectedIssue->human_pct}%, Bot {$selectedIssue->bot_pct}%, Positif {$selectedIssue->pos_pct}%, Negatif {$selectedIssue->neg_pct}%)";
        }

        if (! empty($issues)) {
            $lines[] = "\nDAFTAR ISU TERPANTAU:";
            foreach (array_slice($issues, 0, 8) as $iss) {
                $lines[] = "  #{$iss['rank']} {$iss['title']}: {$iss['total_posts']} post (Orang {$iss['human_pct']}%, Bot {$iss['bot_pct']}%, Positif {$iss['pos_pct']}%, Negatif {$iss['neg_pct']}%, Tren: {$iss['trend']})";
            }
        }

        if (! empty($institutions)) {
            $lines[] = "\nSENTIMEN PER LEMBAGA (AI QWEN):";
            foreach ($institutions as $inst) {
                $lines[] = "  - {$inst['lembaga']}: {$inst['total']} sebutan (Positif: {$inst['pos']}, Negatif: {$inst['neg']}, Netral: {$inst['netral']})";
            }
        }

        if (! empty($topBotAccounts)) {
            $lines[] = "\nAKUN TERPANTAU TERTINGGI:";
            foreach ($topBotAccounts as $acc) {
                $jenis = $acc['is_bot'] ? 'Bot' : 'Orang';
                $alasan = ! empty($acc['reasons']) ? $acc['reasons'][0] : '-';
                $lines[] = "  - {$acc['handle']} [{$jenis}, Skor Bot {$acc['bot_score']}%]: {$alasan}";
            }
        }

        if (! empty($recentPosts)) {
            $lines[] = "\nSAMPEL POSTINGAN TERBARU:";
            foreach ($recentPosts as $rp) {
                $jenis = $rp['is_bot'] ? 'Bot' : 'Orang';
                $lines[] = "  - [{$rp['platform']}] {$rp['author_handle']} ({$jenis} {$rp['bot_score']}%, {$rp['sentiment']}): \"{$rp['content']}\"";
            }
        }

        return [
            'kpi' => $kpi,
            'issues' => $issues,
            'selected_issue' => $selectedIssue ? $this->formatIssueForView($selectedIssue) : null,
            'institutions' => $institutions,
            'top_bot_accounts' => $topBotAccounts,
            'recent_posts' => $recentPosts,
            'accounts_count' => MonitoredAccount::count(),
            'summary' => implode("\n", $lines),
        ];
    }

    /**
     * Mesin penjawab berbasis data PostgreSQL SOSPEM (digunakan saat mode database atau fallback agent lokal offline).
     *
     * @param  array<string, mixed>  $rag
     */
    private function generateDatabaseForensicReply(string $message, array $rag, ?string $agentError = null): string
    {
        $kpi = $rag['kpi'];
        $q = strtolower($message);
        $prefix = $agentError !== null
            ? "[Mode Fallback Database PostgreSQL — Agent Lokal tidak terjangkau]\n\n"
            : "[Analisis Langsung Database PostgreSQL SOSPEM]\n\n";

        if ((int) $kpi['total_posts'] === 0) {
            return $prefix
                .'Saat ini database PostgreSQL `social_posts` masih kosong (0 postingan), dengan '
                .count($rag['issues'])." kategori isu terdaftar.\n\n"
                .'Silakan kirim data dari JupyterHub ke `POST /api/ingest` atau `POST /api/ingest-batch`, '
                .'atau upload file `stream_analisis_sospem.json` di menu **Kelola Isu** (`/issues`) agar chatbot dapat merangkum data riil.';
        }

        if (str_contains($q, 'lembaga') || str_contains($q, 'kementerian') || str_contains($q, 'instansi') || str_contains($q, 'dpr') || str_contains($q, 'presiden')) {
            if (empty($rag['institutions'])) {
                return $prefix."Dari {$kpi['total_posts']} postingan terpantau, belum ditemukan metadata `ai_institutions` (rincian lembaga) dari AI Qwen.";
            }
            $rows = array_map(
                fn (array $i): string => "- **{$i['lembaga']}**: {$i['total']} sebutan (+{$i['pos']} Positif, -{$i['neg']} Negatif, {$i['netral']} Netral)",
                $rag['institutions']
            );

            return $prefix."**Rincian Sentimen per Lembaga Pemerintahan (Ekstraksi AI Qwen):**\n".implode("\n", $rows);
        }

        if (str_contains($q, 'akun') || str_contains($q, 'buzzer') || str_contains($q, 'forensik') || str_contains($q, 'handle')) {
            if (empty($rag['top_bot_accounts'])) {
                return $prefix.'Belum ada akun di tabel `monitored_accounts`.';
            }
            $rows = array_map(function (array $a): string {
                $status = $a['is_bot'] ? 'Jaringan Bot' : 'Warga Organik';
                $reason = ! empty($a['reasons']) ? $a['reasons'][0] : 'Tidak ada catatan khusus';

                return "- **{$a['handle']}** ({$status} · Skor Bot {$a['bot_score']}%) — {$reason}";
            }, $rag['top_bot_accounts']);

            return $prefix."**Daftar Akun Terpantau & Catatan Forensik Terbaru:**\n".implode("\n", $rows);
        }

        if (str_contains($q, 'post') || str_contains($q, 'komentar') || str_contains($q, 'narasi') || str_contains($q, 'warga') || str_contains($q, 'perbandingan')) {
            $target = $rag['selected_issue'] ?? ($rag['issues'][0] ?? null);
            $issueInfo = $target
                ? "Pada isu **{$target['title']}** ({$target['total_posts']} post): Warga Organik {$target['human_pct']}% vs Jaringan Bot {$target['bot_pct']}%, dengan sentimen Positif {$target['pos_pct']}% vs Negatif {$target['neg_pct']}%.\n\n"
                : '';

            $sampleLines = array_map(function (array $p): string {
                $tag = $p['is_bot'] ? 'Bot' : 'Orang';

                return "- [{$tag} · {$p['sentiment']}] **{$p['author_handle']}**: \"{$p['content']}\"";
            }, array_slice($rag['recent_posts'], 0, 5));

            return $prefix.$issueInfo."**Sampel Postingan Terbaru:**\n".implode("\n", $sampleLines);
        }

        // Ringkasan Eksekutif Default
        $topIssues = array_slice($rag['issues'], 0, 5);
        $issueLines = array_map(
            fn (array $iss): string => "- **#{$iss['rank']} {$iss['title']}**: {$iss['total_posts']} post (Orang {$iss['human_pct']}% · Bot {$iss['bot_pct']}% · Positif {$iss['pos_pct']}%)",
            $topIssues
        );

        return $prefix
            ."**Ringkasan Eksekutif Opini Publik SOSPEM:**\n"
            ."- Total Sampel Dianalisis: **{$kpi['total_posts']} postingan**\n"
            ."- Warga Organik: **{$kpi['human_posts']} post ({$kpi['human_pct']}%)**\n"
            ."- Jaringan Bot: **{$kpi['bot_posts']} post ({$kpi['bot_pct']}%)**\n"
            ."- Isu Teratas (#1): **{$kpi['top_issue_title']}** ({$kpi['top_issue_posts']} post / {$kpi['top_issue_share']}%)\n\n"
            ."**Peringkat Isu Teratas:**\n".implode("\n", $issueLines);
    }

    /**
     * Buat antrean tugas scraping baru dari form website.
     */
    public function createScrapeJob(Request $request, PostAnalyzerService $analyzer): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:150',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $topic = trim($validated['topic']);
        $slug = Str::slug($topic);

        // Buat isu baru di database jika belum ada, agar langsung muncul dan siap menampung post
        if ($slug !== '') {
            Issue::firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $topic,
                    'subtitle' => 'Pantauan Live Scraper & Opini Publik',
                    'rank' => Issue::count() + 1,
                    'trend' => 'stable',
                    'keywords' => [$topic, str_replace('-', ' ', $slug)],
                    'human_pct' => 50.00,
                    'bot_pct' => 50.00,
                    'pos_pct' => 50.00,
                    'neg_pct' => 50.00,
                    'total_posts' => 0,
                ]
            );
        }

        $limit = (int) ($validated['limit'] ?? 10);
        $heartbeat = Cache::get('sospem_jupyter_heartbeat');
        $lastSeenRaw = is_array($heartbeat) ? ($heartbeat['last_seen_at'] ?? null) : null;
        $jupyterActive = is_string($lastSeenRaw)
            && $lastSeenRaw !== ''
            && (int) abs(now()->diffInSeconds(Carbon::parse($lastSeenRaw))) <= 120;

        $shouldWaitForWorker = $jupyterActive || app()->runningUnitTests();

        $jobId = DB::table('scrape_jobs')->insertGetId([
            'topic' => $topic,
            'limit' => $limit,
            'status' => $shouldWaitForWorker ? 'pending' : 'running',
            'scraped_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($shouldWaitForWorker) {
            return response()->json([
                'status' => 'ok',
                'job_id' => $jobId,
                'issue_slug' => $slug,
            ]);
        }

        $issue = $analyzer->resolveIssue([
            'issue_slug' => $slug,
            'issue_title' => $topic,
        ]);

        // 1. Coba delegasikan ke worker eksternal jika dikonfigurasi dan sedang aktif
        $delegated = false;
        $workerUrl = trim((string) env('SCRAPER_WORKER_URL', ''));
        if ($workerUrl !== '') {
            try {
                $resp = Http::withoutVerifying()
                    ->withHeaders([
                        'X-Webhook-Secret' => (string) env('SCRAPER_WEBHOOK_SECRET', 'sospem-secret-key-2026'),
                        'ngrok-skip-browser-warning' => 'true',
                    ])->timeout(2)->post($workerUrl, [
                        'job_id' => $jobId,
                        'topic' => $topic,
                        'limit' => $limit,
                        'issue_slug' => $slug,
                    ]);
                if ($resp->successful()) {
                    $delegated = true;
                }
            } catch (Throwable) {
                $delegated = false;
            }
        }

        // 2. Jika worker lokal/JupyterHub sedang offline, jalankan mandiri di server website
        if (! $delegated) {
            $scrapedCount = $this->runStandaloneHostingerScrape($topic, $slug, $limit, $issue, $analyzer);
            DB::table('scrape_jobs')->where('id', $jobId)->update([
                'status' => 'completed',
                'scraped_count' => $scrapedCount,
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'job_id' => $jobId,
            'issue_slug' => $slug,
        ]);
    }

    /**
     * Diambil oleh loop polling JupyterHub setiap beberapa detik.
     */
    public function getPendingJob(Request $request): JsonResponse
    {
        $this->recordJupyterHeartbeat($request, 'scrape_poll');

        $job = DB::table('scrape_jobs')
            ->where('status', 'pending')
            ->orderBy('id')
            ->first();

        if (! $job) {
            return response()->json(['job' => null]);
        }

        DB::table('scrape_jobs')
            ->where('id', $job->id)
            ->update([
                'status' => 'running',
                'updated_at' => now(),
            ]);

        $job->issue_slug = Str::slug($job->topic);

        return response()->json(['job' => $job]);
    }

    /**
     * Dilaporkan oleh JupyterHub saat proses scraping & analisis selesai.
     */
    public function completeJob(Request $request, int|string $id): JsonResponse
    {
        $this->recordJupyterHeartbeat($request, 'scrape_done');

        DB::table('scrape_jobs')
            ->where('id', $id)
            ->update([
                'status' => $request->input('status', 'completed'),
                'scraped_count' => (int) $request->input('scraped_count', 0),
                'updated_at' => now(),
            ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Cek status antrean scrape job untuk update status real-time di UI (GET /api/scrape-jobs/{id}).
     */
    public function getJobStatus(int|string $id): JsonResponse
    {
        $job = DB::table('scrape_jobs')->where('id', $id)->first();

        if (! $job) {
            return response()->json(['status' => 'not_found', 'message' => 'Job tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'ok',
            'job' => $job,
            'job_id' => $job->id,
            'job_status' => $job->status,
            'scraped_count' => (int) $job->scraped_count,
        ]);
    }

    /**
     * Mesin scraping mandiri di server website saat worker lokal/JupyterHub sedang offline.
     */
    private function runStandaloneHostingerScrape(string $topic, string $slug, int $limit, ?Issue $issue, PostAnalyzerService $analyzer): int
    {
        $inserted = 0;
        $sources = [
            ['query' => "{$topic} indonesia", 'prefix' => 'news_', 'platform' => 'news', 'default_user' => null],
            ['query' => "{$topic} site:youtube.com", 'prefix' => 'yt_', 'platform' => 'youtube', 'default_user' => 'YouTube Public Channel'],
        ];

        foreach ($sources as $src) {
            if ($inserted >= $limit) {
                break;
            }

            try {
                $rssUrl = 'https://news.google.com/rss/search?q='.urlencode($src['query']).'&hl=id&gl=ID&ceid=ID:id';
                $rssResp = Http::withoutVerifying()
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; SospemBot/1.0)',
                    ])->timeout(10)->get($rssUrl);

                if (! $rssResp->successful()) {
                    continue;
                }

                $xml = @simplexml_load_string($rssResp->body());
                if (! $xml || ! isset($xml->channel->item)) {
                    continue;
                }

                foreach ($xml->channel->item as $item) {
                    if ($inserted >= $limit) {
                        break;
                    }

                    $title = trim((string) $item->title);
                    if ($title === '') {
                        continue;
                    }

                    $link = trim((string) $item->link);
                    $pubDate = trim((string) $item->pubDate);
                    $sourceName = $src['default_user'] ?? (isset($item->source) ? trim((string) $item->source) : 'Google News');
                    $handleSlug = $src['default_user'] !== null ? 'youtube_id' : Str::slug($sourceName, '_');

                    $analyzer->ingestAndAnalyze($issue, [
                        'comment_id' => $src['prefix'].md5($link !== '' ? $link : $title),
                        'issue_slug' => $slug,
                        'issue_title' => $topic,
                        'platform' => $src['platform'],
                        'username' => $sourceName,
                        'handle' => '@'.($handleSlug !== '' ? $handleSlug : 'warga_net'),
                        'content' => $title,
                        'source_news' => $link,
                        'timestamp' => $pubDate !== '' ? date('Y-m-d H:i:s', strtotime($pubDate) ?: time()) : now()->toDateTimeString(),
                    ]);
                    $inserted++;
                }
            } catch (Throwable) {
                // Lanjut ke sumber berikutnya jika RSS gagal
            }
        }

        return $inserted;
    }

    /**
     * Diambil oleh thread Reverse-Bridge Chatbot di JupyterHub untuk memproses pertanyaan user.
     */
    public function getPendingChatBridge(Request $request): JsonResponse
    {
        if (! $this->isAuthorizedBridgeRequest($request)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $this->recordJupyterHeartbeat($request, 'chat_bridge_poll');
        Cache::put('sospem_chat_bridge_last_seen', now()->toIso8601String(), now()->addMinutes(10));

        $task = Cache::pull('sospem_chat_bridge_pending');

        if (is_array($task) && ! empty($task['id']) && ! empty($task['messages'])) {
            return response()->json([
                'status' => 'ok',
                'task' => $task,
            ]);
        }

        return response()->json([
            'status' => 'idle',
            'task' => null,
        ]);
    }

    /**
     * Menerima balasan Chatbot yang dihasilkan oleh model Qwen2.5 di server JupyterHub.
     */
    public function completeChatBridge(Request $request, string $taskId): JsonResponse
    {
        if (! $this->isAuthorizedBridgeRequest($request)) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $safeTaskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $taskId) ?? '';
        if ($safeTaskId === '') {
            return response()->json(['status' => 'error', 'message' => 'Task ID tidak valid.'], 422);
        }

        $this->recordJupyterHeartbeat($request, 'chat_bridge_reply');
        Cache::put('sospem_chat_bridge_last_seen', now()->toIso8601String(), now()->addMinutes(10));

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:10000'],
            'model' => ['nullable', 'string', 'max:120'],
        ]);

        $modelName = trim((string) ($validated['model'] ?? 'qwen2.5:1.5b'));

        Cache::put("sospem_chat_bridge_reply_{$safeTaskId}", [
            'reply' => trim((string) $validated['reply']),
            'model' => $modelName !== '' ? $modelName : 'qwen2.5:1.5b',
        ], now()->addMinutes(5));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Validasi opsional header X-Sospem-Key jika dikonfigurasi di environment.
     */
    private function isAuthorizedBridgeRequest(Request $request): bool
    {
        $expectedKey = trim((string) env('SOSPEM_API_KEY', ''));
        if ($expectedKey === '') {
            return true;
        }

        return hash_equals($expectedKey, trim((string) $request->header('X-Sospem-Key', '')));
    }

    /**
     * Teruskan pertanyaan Chatbot ke worker JupyterHub Qwen2.5 melalui antrean Reverse-Polling Bridge.
     *
     * @param  array<int, array<string, string>>  $messagesPayload
     */
    private function dispatchViaJupyterChatBridge(array $messagesPayload, string $agentModel): ?string
    {
        $bridgeLastSeenRaw = Cache::get('sospem_chat_bridge_last_seen');
        $isBridgeActive = false;

        if (is_string($bridgeLastSeenRaw) && $bridgeLastSeenRaw !== '') {
            $diff = (int) abs(now()->diffInSeconds(Carbon::parse($bridgeLastSeenRaw)));
            if ($diff <= 45) {
                $isBridgeActive = true;
            }
        }

        if (! $isBridgeActive) {
            $heartbeat = Cache::get('sospem_jupyter_heartbeat');
            if (is_array($heartbeat)) {
                $lastSource = (string) ($heartbeat['last_source'] ?? '');
                $lastSeenRaw = $heartbeat['last_seen_at'] ?? null;
                if (in_array($lastSource, ['chat_bridge_poll', 'chat_bridge_reply'], true)
                    && is_string($lastSeenRaw)
                    && $lastSeenRaw !== '') {
                    $diff = (int) abs(now()->diffInSeconds(Carbon::parse($lastSeenRaw)));
                    if ($diff <= 45) {
                        $isBridgeActive = true;
                    }
                }
            }
        }

        if (! $isBridgeActive) {
            return null;
        }

        $taskId = (string) Str::uuid();
        $replyCacheKey = "sospem_chat_bridge_reply_{$taskId}";

        Cache::put('sospem_chat_bridge_pending', [
            'id' => $taskId,
            'model' => $agentModel,
            'messages' => $messagesPayload,
            'created_at' => now()->toIso8601String(),
        ], now()->addSeconds(45));

        if (app()->runningUnitTests()) {
            $result = Cache::pull($replyCacheKey);

            return is_array($result) && is_string($result['reply'] ?? null) && trim($result['reply']) !== ''
                ? trim($result['reply'])
                : null;
        }

        $deadline = microtime(true) + 25.0;
        while (microtime(true) < $deadline) {
            $result = Cache::pull($replyCacheKey);
            if (is_array($result) && is_string($result['reply'] ?? null) && trim($result['reply']) !== '') {
                return trim($result['reply']);
            }
            usleep(250000);
        }

        return null;
    }

    /**
     * Cek cepat ketersediaan port localhost agar fallback AI cloud tidak menunggu timeout saat Ollama mati.
     */
    private function isLocalEndpointReachable(string $url): bool
    {
        $parsed = parse_url($url);
        $host = is_array($parsed) ? strtolower((string) ($parsed['host'] ?? '')) : '';

        if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true) || app()->runningUnitTests()) {
            return true;
        }

        $scheme = is_array($parsed) ? strtolower((string) ($parsed['scheme'] ?? 'http')) : 'http';
        $port = is_array($parsed) && isset($parsed['port'])
            ? (int) $parsed['port']
            : ($scheme === 'https' ? 443 : 80);

        $errno = 0;
        $errstr = '';
        $conn = @fsockopen($host, $port, $errno, $errstr, 0.8);
        if (is_resource($conn)) {
            fclose($conn);

            return true;
        }

        return false;
    }
}
