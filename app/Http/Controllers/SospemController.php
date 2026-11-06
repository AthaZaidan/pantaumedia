<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use App\Models\SocialPost;
use App\Services\PostAnalyzerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            ->get()
            ->map(fn (Issue $issue): array => $this->formatIssueForView($issue))
            ->all();
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
     * Dashboard utama: Matriks isu x platform dari PostgreSQL.
     */
    public function dashboard(): View
    {
        return view('dashboard', [
            'issues' => $this->getIssuesFromDatabase(),
        ]);
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
            ->limit(30)
            ->get()
            ->map(fn (SocialPost $p): array => $this->formatPostForView($p, $platformData['name']))
            ->all();

        $botPosts = (clone $baseQuery)
            ->where('is_bot', true)
            ->limit(30)
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
     * Endpoint Ingest Real-Time: Menganalisis skor bot & sentimen lalu menyimpan ke PostgreSQL.
     */
    public function ingest(Request $request, PostAnalyzerService $analyzer): JsonResponse
    {
        $issueSlug = (string) $request->input('issue_slug', 'makan-bergizi-gratis');
        $issue = Issue::where('slug', $issueSlug)->first() ?? Issue::orderBy('rank')->firstOrFail();

        $post = $analyzer->ingestAndAnalyze($issue, $request->all());

        return response()->json([
            'status' => 'ok',
            'issue_total_posts' => $issue->fresh()?->total_posts ?? $issue->total_posts,
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

        return view('accounts', [
            'profile' => $profile,
            'accountsList' => $accountsList,
        ]);
    }

    /**
     * Halaman Grafik Deret Waktu & Analitik.
     */
    public function analytics(): View
    {
        return view('analytics', [
            'issues' => $this->getIssuesFromDatabase(),
        ]);
    }

    /**
     * Halaman Metodologi & Kepatuhan UU PDP.
     */
    public function methodology(): View
    {
        return view('methodology');
    }
}
