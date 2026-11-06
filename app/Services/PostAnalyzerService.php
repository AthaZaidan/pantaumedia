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
     * Analisis teks postingan secara otomatis (Heuristik Bot + Sentimen) lalu simpan ke PostgreSQL.
     *
     * @param  array<string, mixed>  $payload
     */
    public function ingestAndAnalyze(Issue $issue, array $payload): SocialPost
    {
        $content = (string) ($payload['content'] ?? '');
        $handle = (string) ($payload['author_handle'] ?? '@warga_net');
        $authorName = (string) ($payload['author_name'] ?? 'Warga Netizen');
        $platform = strtolower((string) ($payload['platform'] ?? 'x'));

        // 1. Cek apakah akun sudah ada di tabel monitored_accounts
        $account = MonitoredAccount::where('handle', $handle)->first();

        // 2. Hitung Skor Kemungkinan Bot (0 - 100%) berdasarkan heuristik teks & akun
        $botScore = $this->calculateBotScore($content, $handle, $account, $payload['force_bot'] ?? null);
        $isBot = $botScore >= 60.0;

        // 3. Klasifikasi Sentimen Bahasa Indonesia (positive / negative + confidence)
        [$sentiment, $sentimentConf] = $this->analyzeSentiment($content, $payload['force_sentiment'] ?? null);

        // 4. Simpan ke tabel social_posts di PostgreSQL
        $post = SocialPost::create([
            'issue_id' => $issue->id,
            'monitored_account_id' => $account?->id,
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
            'posted_at' => now(),
            'metadata' => [
                'ingested_via' => 'realtime_analyzer',
                'hashtag_count' => preg_match_all('/#\w+/u', $content),
            ],
        ]);

        // 5. Update total_posts pada tabel issues di PostgreSQL
        $issue->increment('total_posts');

        return $post;
    }

    /**
     * Heuristik skor bot: memeriksa duplikasi teks, jumlah tagar kampanye, pola angka di handle, dan riwayat akun.
     */
    public function calculateBotScore(string $content, string $handle, ?MonitoredAccount $account, ?bool $forceBot = null): float
    {
        if ($account !== null) {
            return (float) $account->bot_score;
        }

        if ($forceBot === true) {
            return round(rand(910, 985) / 10, 1);
        }

        if ($forceBot === false) {
            return round(rand(25, 89) / 10, 1);
        }

        $score = 12.0;

        // Indikator 1: Jumlah hashtag berlebih (> 1 hashtag kampanye)
        $hashtagCount = preg_match_all('/#\w+/u', $content);
        if ($hashtagCount >= 2) {
            $score += 38.5;
        } elseif ($hashtagCount === 1) {
            $score += 18.0;
        }

        // Indikator 2: Kemiripan teks / duplikasi persis di database
        $duplicateExists = SocialPost::where('content', $content)->exists();
        if ($duplicateExists) {
            $score += 42.0;
        }

        // Indikator 3: Pola username diakhiri deretan angka (contoh: @akun_9981)
        if (preg_match('/\d{2,}$/', $handle)) {
            $score += 22.4;
        }

        return min(99.4, round($score, 1));
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

