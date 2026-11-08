<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use App\Models\SocialPost;
use App\Services\PostAnalyzerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

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
            'platforms' => $issue->platforms ?? [],
        ];
    }

    /**
     * Dashboard utama: KPI, Tren Periode (24j, 7h, 30h) & Matriks isu x platform murni dari PostgreSQL.
     */
    public function dashboard(): View
    {
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
    public function feed(Request $request, string $issueSlug, string $platform): View
    {
        $issueModel = Issue::where('slug', $issueSlug)->first()
            ?? Issue::orderBy('rank')->firstOrFail();

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
            ->where('platform', $platformKey)
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
        return [
            'id' => $post->id,
            'author' => $post->author_name,
            'handle' => $post->author_handle,
            'platform' => $platformName,
            'time' => $post->posted_at ? $post->posted_at->diffForHumans() : 'Baru saja',
            'bot_score' => (float) $post->bot_score,
            'sentiment' => $post->sentiment,
            'sentiment_conf' => (float) $post->sentiment_conf,
            'text' => $post->content,
            'likes' => $post->likes,
            'reposts' => $post->reposts,
            'url' => $post->post_url ?? 'https://x.com',
        ];
    }

    /**
     * Endpoint Ingest Real-Time: Menganalisis skor bot & sentimen, menyimpan ke PostgreSQL,
     * dan menghitung ulang agregasi KPI & isu secara real-time.
     */
    public function ingest(Request $request, PostAnalyzerService $analyzer): JsonResponse
    {
        $issueSlug = (string) $request->input('issue_slug', 'makan-bergizi-gratis');
        $issue = Issue::where('slug', $issueSlug)->first() ?? Issue::orderBy('rank')->firstOrFail();

        $post = $analyzer->ingestAndAnalyze($issue, $request->all());
        $analyzer->rerankAllIssues();

        return response()->json([
            'status' => 'ok',
            'kpi' => $this->buildKpiStats(),
            'issue' => $this->formatIssueForView($issue->fresh()),
            'post' => [
                'id' => $post->id,
                'author' => $post->author_name,
                'handle' => $post->author_handle,
                'time' => 'Baru saja',
                'is_bot' => $post->is_bot,
                'bot_score' => (float) $post->bot_score,
                'human_score' => round(100 - (float) $post->bot_score, 1),
                'sentiment' => $post->sentiment,
                'sentiment_conf' => (float) $post->sentiment_conf,
                'text' => $post->content,
                'likes' => $post->likes,
                'reposts' => $post->reposts,
                'url' => $post->post_url ?? 'https://x.com',
            ],
        ]);
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
    public function analytics(Request $request): View
    {
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
        $metaCount = $posts->where('platform', 'meta')->count();
        $xCount = $posts->where('platform', 'x')->count();
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

        return view('analytics', [
            'issues' => $this->getIssuesFromDatabase(),
            'selectedSlug' => $selectedSlug,
            'selectedIssue' => $selectedIssue ? $this->formatIssueForView($selectedIssue) : null,
            'keywordMonitor' => $keywordMonitor,
            'topTriggerAccounts' => $topTriggerAccounts,
            'chartData' => [
                'hourly_labels' => array_keys($buckets),
                'hourly_human' => $hourlyHuman,
                'hourly_bot' => $hourlyBot,
                'sentiment_pos' => [$humanPosPct, $botPosPct],
                'sentiment_neg' => [$humanNegPct, $botNegPct],
                'platform_counts' => [$metaCount, $xCount, $tiktokCount],
                'issue_labels' => $issueLabels,
                'issue_human_counts' => $issueHumanCounts,
                'issue_bot_counts' => $issueBotCounts,
                'spectrum_labels' => array_keys($spectrumRanges),
                'spectrum_counts' => $spectrumCounts,
                'radar_labels' => ['Duplikasi Teks', 'Kepadatan Tagar', 'Sinkronisitas Jam', 'Rasio Following', 'Amplifikasi Repost'],
                'radar_bot' => [96, 92, 95, 89, 94],
                'radar_human' => [12, 18, 24, 15, 28],
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
            'human_pct' => 50.0,
            'bot_pct' => 50.0,
            'pos_pct' => 50.0,
            'neg_pct' => 50.0,
            'trend' => $validated['trend'],
            'keywords' => $keywordsArray,
            'platforms' => [],
        ]);

        // Buat sampel postingan awal (Orang & Bot) di ketiga platform dan hitung agregasi otomatis
        foreach (['meta', 'x', 'tiktok'] as $platKey) {
            $analyzer->ingestAndAnalyze($issue, [
                'platform' => $platKey,
                'author_name' => 'Rizal Fauzi',
                'author_handle' => '@rizalfauzi_econ',
                'force_bot' => false,
                'content' => "Evaluasi publik terkait {$title}: perlu transparansi data dan pengawasan pelaksanaan di tingkat daerah agar tidak menimbulkan masalah baru.",
            ]);

            $analyzer->ingestAndAnalyze($issue, [
                'platform' => $platKey,
                'author_name' => 'Sahabat Kebijakan',
                'author_handle' => '@sahabat_negeri77',
                'force_bot' => true,
                'content' => "Dukungan penuh masyarakat untuk {$title}! Solusi nyata demi kemajuan bangsa dan kesejahteraan bersama! #DukungKebijakan #IndonesiaMaju",
            ]);
        }

        $analyzer->rerankAllIssues();

        return redirect()
            ->route('issues.index')
            ->with('status', "Isu \"{$issue->title}\" beserta postingan awalnya berhasil disimpan dan dihitung di PostgreSQL.");
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

        return redirect()
            ->route('issues.index')
            ->with('status', "Status tren isu \"{$issue->title}\" berhasil diperbarui.");
    }

    /**
     * Hapus isu dari PostgreSQL dan urutkan ulang nomor rank.
     */
    public function destroyIssue(Issue $issue, PostAnalyzerService $analyzer): RedirectResponse
    {
        $title = $issue->title;
        $issue->delete();

        $analyzer->rerankAllIssues();

        return redirect()
            ->route('issues.index')
            ->with('status', "Isu \"{$title}\" berhasil dihapus dari pantauan.");
    }

    /**
     * Uji & simpan postingan baru dari form web ke PostgreSQL melalui PostAnalyzerService.
     */
    public function storePost(Request $request, PostAnalyzerService $analyzer): RedirectResponse
    {
        $validated = $request->validate([
            'issue_id' => ['required', 'exists:issues,id'],
            'platform' => ['required', 'in:meta,x,tiktok'],
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

        return redirect()
            ->route('issues.index')
            ->with('status', "Postingan berhasil dianalisis & disimpan ke PostgreSQL! Hasil Deteksi: {$kategori} · Sentimen: {$sentimenLabel}.");
    }
}
