<?php

namespace Database\Seeders;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use App\Models\SocialPost;
use Illuminate\Database\Seeder;

class SospemDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Monitored Accounts (Organik & Bot)
        $accounts = [
            [
                'handle' => '@sahabat_negeri77',
                'name' => 'Sahabat Kebijakan',
                'platform' => 'X (Twitter)',
                'age' => '34 hari',
                'followers' => 11,
                'following' => 1890,
                'posting_frequency' => '14 postingan / menit (saat jam kampanye)',
                'is_bot' => true,
                'bot_score' => 96.4,
                'reasons' => [
                    'Teks Kembar (Lexical Similarity 98%): Mengirimkan template teks identik dengan 24 akun lain dalam rentang 90 detik.',
                    'Anomali Interval Waktu: Jeda antar postingan konsisten tepat setiap 4,2 detik (indikasi script penjadwalan otomatis).',
                    'Rasio Pengikut Timpang: Akun baru berusia 34 hari dengan 1.890 following namun hanya 11 follower.',
                ],
                'topics' => ['Makan Bergizi Gratis (MBG)', 'kopdes', 'gas alam'],
                'cluster' => ['@rakyat_bersatu_id', '@indonesia_maju2026', '@fakta_bicara24', '@suara_nusantara88', '@garda_pangan_ri'],
            ],
            [
                'handle' => '@rizalfauzi_econ',
                'name' => 'Rizal Fauzi',
                'platform' => 'X (Twitter)',
                'age' => '6 tahun 4 bulan',
                'followers' => 14820,
                'following' => 642,
                'posting_frequency' => '3 - 5 postingan / hari (pola organik manusia)',
                'is_bot' => false,
                'bot_score' => 4.2,
                'reasons' => [
                    'Variasi Kosakata Alami: Struktur kalimat unik, menggunakan kosakata ekonomi makro dan berinteraksi dua arah di balasan.',
                    'Pola Waktu Manusia: Aktivitas mengikuti jam biologis (07:00 - 22:30 WIB) dengan jeda acak alami.',
                    'Riwayat Akun Panjang: Terdaftar sejak 2020 dengan jaringan pertemanan organik terverifikasi.',
                ],
                'topics' => ['Makan Bergizi Gratis (MBG)', 'kabur aja dulu', 'iuran tapera'],
                'cluster' => [],
            ],
            [
                'handle' => '@nadiamalia',
                'name' => 'Nadia Amalia',
                'platform' => 'Meta / X',
                'age' => '4 tahun 8 bulan',
                'followers' => 3290,
                'following' => 510,
                'posting_frequency' => '2 postingan / hari',
                'is_bot' => false,
                'bot_score' => 6.8,
                'reasons' => [
                    'Konten Orisinal Lapangan: Mengunggah foto langsung dengan metadata EXIF kamera ponsel asli.',
                    'Interaksi Komunitas: Berdiskusi secara natural dengan warga lokal di kolom komentar.',
                ],
                'topics' => ['Makan Bergizi Gratis (MBG)', 'subsidi pupuk'],
                'cluster' => [],
            ],
            [
                'handle' => '@rakyat_bersatu_id',
                'name' => 'Garda Nusantara',
                'platform' => 'X (Twitter)',
                'age' => '19 hari',
                'followers' => 8,
                'following' => 1540,
                'posting_frequency' => '180 postingan / hari',
                'is_bot' => true,
                'bot_score' => 95.8,
                'reasons' => [
                    'Mengunggah narasi dan tagar seragam bersamaan dengan kluster @sahabat_negeri77.',
                    'Aktivitas non-stop 24 jam tanpa jeda istirahat biologis.',
                ],
                'topics' => ['Makan Bergizi Gratis (MBG)', 'kopdes'],
                'cluster' => ['@sahabat_negeri77', '@fakta_bicara24', '@indonesia_maju2026'],
            ],
        ];

        $accountModels = [];
        foreach ($accounts as $acc) {
            $accountModels[$acc['handle']] = MonitoredAccount::updateOrCreate(['handle' => $acc['handle']], $acc);
        }

        // 2. Seed 10 Monitored Issues into PostgreSQL
        $issuesDataset = [
            [
                'rank' => 1,
                'slug' => 'makan-bergizi-gratis',
                'title' => 'Makan Bergizi Gratis (MBG)',
                'subtitle' => 'Isu Strategis Nasional · Dominan di X & Meta',
                'total_posts' => 32840,
                'human_pct' => 61.6,
                'bot_pct' => 38.4,
                'pos_pct' => 31.4,
                'neg_pct' => 68.6,
                'trend' => 'up',
                'keywords' => ['makan bergizi gratis', 'mbg', 'menu mbg'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '23.4k post', 'human_pos' => 58.2, 'human_neg' => 41.8, 'bot_pos' => 89.4, 'bot_neg' => 10.6, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '31.2k post', 'human_pos' => 31.4, 'human_neg' => 68.6, 'bot_pos' => 84.1, 'bot_neg' => 15.9, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '13.8k post', 'human_pos' => 62.5, 'human_neg' => 37.5, 'bot_pos' => 91.2, 'bot_neg' => 8.8, 'sufficient' => true],
                ],
            ],
            [
                'rank' => 2,
                'slug' => 'kabur-aja-dulu',
                'title' => 'kabur aja dulu',
                'subtitle' => 'Ketenagakerjaan & Pajak · Dominan di X',
                'total_posts' => 18420,
                'human_pct' => 78.2,
                'bot_pct' => 21.8,
                'pos_pct' => 14.3,
                'neg_pct' => 85.7,
                'trend' => 'down',
                'keywords' => ['kabur aja dulu', 'kerja luar negeri', 'pajak kelas menengah'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '4.2k post', 'human_pos' => 25.2, 'human_neg' => 74.8, 'bot_pos' => 76.0, 'bot_neg' => 24.0, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '11.8k post', 'human_pos' => 14.3, 'human_neg' => 85.7, 'bot_pos' => 81.5, 'bot_neg' => 18.5, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '2.4k post', 'human_pos' => 29.7, 'human_neg' => 70.3, 'bot_pos' => 79.2, 'bot_neg' => 20.8, 'sufficient' => true],
                ],
            ],
            [
                'rank' => 3,
                'slug' => 'gas-alam',
                'title' => 'gas alam',
                'subtitle' => 'Pasokan & Distribusi Elpiji 3kg',
                'total_posts' => 14180,
                'human_pct' => 68.4,
                'bot_pct' => 31.6,
                'pos_pct' => 21.1,
                'neg_pct' => 78.9,
                'trend' => 'up',
                'keywords' => ['gas alam', 'elpiji 3kg', 'gas melon'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '5.1k post', 'human_pos' => 34.2, 'human_neg' => 65.8, 'bot_pos' => 82.0, 'bot_neg' => 18.0, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '6.3k post', 'human_pos' => 21.1, 'human_neg' => 78.9, 'bot_pos' => 85.4, 'bot_neg' => 14.6, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '2.7k post', 'human_pos' => 27.9, 'human_neg' => 72.1, 'bot_pos' => 80.1, 'bot_neg' => 19.9, 'sufficient' => true],
                ],
            ],
            [
                'rank' => 4,
                'slug' => 'karhutla',
                'title' => 'Karhutla',
                'subtitle' => 'Titik Api Riau & Jambi',
                'total_posts' => 8940,
                'human_pct' => 72.0,
                'bot_pct' => 28.0,
                'pos_pct' => 7.6,
                'neg_pct' => 92.4,
                'trend' => 'up',
                'keywords' => ['karhutla', 'titik api', 'kabut asap'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '2.8k post', 'human_pos' => 12.4, 'human_neg' => 87.6, 'bot_pos' => 74.2, 'bot_neg' => 25.8, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '4.6k post', 'human_pos' => 7.6, 'human_neg' => 92.4, 'bot_pos' => 78.9, 'bot_neg' => 21.1, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '1.5k post', 'human_pos' => 15.1, 'human_neg' => 84.9, 'bot_pos' => 71.0, 'bot_neg' => 29.0, 'sufficient' => true],
                ],
            ],
            [
                'rank' => 5,
                'slug' => 'kopdes',
                'title' => 'kopdes',
                'subtitle' => 'Koperasi Pedesaan Pangan',
                'total_posts' => 3120,
                'human_pct' => 54.0,
                'bot_pct' => 46.0,
                'pos_pct' => 63.8,
                'neg_pct' => 36.2,
                'trend' => 'stable',
                'keywords' => ['kopdes', 'koperasi desa', 'pangan desa'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '1.6k post', 'human_pos' => 63.8, 'human_neg' => 36.2, 'bot_pos' => 94.2, 'bot_neg' => 5.8, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '980 post', 'human_pos' => 58.1, 'human_neg' => 41.9, 'bot_pos' => 91.0, 'bot_neg' => 9.0, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '540 post', 'human_pos' => 66.4, 'human_neg' => 33.6, 'bot_pos' => 92.5, 'bot_neg' => 7.5, 'sufficient' => true],
                ],
            ],
            [
                'rank' => 6,
                'slug' => 'subsidi-pupuk',
                'title' => 'subsidi pupuk',
                'subtitle' => 'Alokasi Petani Daerah',
                'total_posts' => 2840,
                'human_pct' => 82.0,
                'bot_pct' => 18.0,
                'pos_pct' => 26.8,
                'neg_pct' => 73.2,
                'trend' => 'stable',
                'keywords' => ['subsidi pupuk', 'pupuk urea', 'kartu tani'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '1.1k post', 'human_pos' => 31.0, 'human_neg' => 69.0, 'bot_pos' => 84.0, 'bot_neg' => 16.0, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '1.7k post', 'human_pos' => 26.8, 'human_neg' => 73.2, 'bot_pos' => 82.5, 'bot_neg' => 17.5, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '38 post', 'human_pos' => 0, 'human_neg' => 0, 'bot_pos' => 0, 'bot_neg' => 0, 'sufficient' => false],
                ],
            ],
            [
                'rank' => 7,
                'slug' => 'polusi-udara-jkt',
                'title' => 'polusi udara jkt',
                'subtitle' => 'ISPU Jabodetabek',
                'total_posts' => 2410,
                'human_pct' => 88.0,
                'bot_pct' => 12.0,
                'pos_pct' => 11.2,
                'neg_pct' => 88.8,
                'trend' => 'up',
                'keywords' => ['polusi udara', 'ispu jakarta', 'kualitas udara'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '720 post', 'human_pos' => 14.5, 'human_neg' => 85.5, 'bot_pos' => 72.0, 'bot_neg' => 28.0, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '1.4k post', 'human_pos' => 11.2, 'human_neg' => 88.8, 'bot_pos' => 75.4, 'bot_neg' => 24.6, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '290 post', 'human_pos' => 18.0, 'human_neg' => 82.0, 'bot_pos' => 70.0, 'bot_neg' => 30.0, 'sufficient' => true],
                ],
            ],
            [
                'rank' => 8,
                'slug' => 'kenaikan-pbb',
                'title' => 'kenaikan pbb',
                'subtitle' => 'Pajak Bumi & Bangunan Daerah',
                'total_posts' => 1920,
                'human_pct' => 92.0,
                'bot_pct' => 8.0,
                'pos_pct' => 18.4,
                'neg_pct' => 81.6,
                'trend' => 'down',
                'keywords' => ['kenaikan pbb', 'njop naik', 'pajak bumi bangunan'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '1.1k post', 'human_pos' => 18.4, 'human_neg' => 81.6, 'bot_pos' => 68.0, 'bot_neg' => 32.0, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '540 post', 'human_pos' => 15.2, 'human_neg' => 84.8, 'bot_pos' => 71.2, 'bot_neg' => 28.8, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '280 post', 'human_pos' => 21.0, 'human_neg' => 79.0, 'bot_pos' => 65.0, 'bot_neg' => 35.0, 'sufficient' => true],
                ],
            ],
            [
                'rank' => 9,
                'slug' => 'jalan-rusak-lpg',
                'title' => 'jalan rusak lpg',
                'subtitle' => 'Infrastruktur Jalan Daerah',
                'total_posts' => 1450,
                'human_pct' => 94.0,
                'bot_pct' => 6.0,
                'pos_pct' => 8.9,
                'neg_pct' => 91.1,
                'trend' => 'stable',
                'keywords' => ['jalan rusak', 'infrastruktur daerah', 'perbaikan jalan'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '420 post', 'human_pos' => 10.5, 'human_neg' => 89.5, 'bot_pos' => 62.0, 'bot_neg' => 38.0, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '310 post', 'human_pos' => 9.2, 'human_neg' => 90.8, 'bot_pos' => 64.0, 'bot_neg' => 36.0, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '720 post', 'human_pos' => 8.9, 'human_neg' => 91.1, 'bot_pos' => 66.5, 'bot_neg' => 33.5, 'sufficient' => true],
                ],
            ],
            [
                'rank' => 10,
                'slug' => 'iuran-tapera',
                'title' => 'iuran tapera',
                'subtitle' => 'Potongan Gaji & Tabungan',
                'total_posts' => 1180,
                'human_pct' => 85.0,
                'bot_pct' => 15.0,
                'pos_pct' => 12.6,
                'neg_pct' => 87.4,
                'trend' => 'down',
                'keywords' => ['iuran tapera', 'potongan tapera', 'tabungan perumahan'],
                'platforms' => [
                    'meta' => ['name' => 'Meta (IG & FB)', 'volume' => '390 post', 'human_pos' => 14.0, 'human_neg' => 86.0, 'bot_pos' => 77.0, 'bot_neg' => 23.0, 'sufficient' => true],
                    'x' => ['name' => 'X & Threads', 'volume' => '610 post', 'human_pos' => 12.6, 'human_neg' => 87.4, 'bot_pos' => 79.5, 'bot_neg' => 20.5, 'sufficient' => true],
                    'tiktok' => ['name' => 'TikTok & YouTube', 'volume' => '180 post', 'human_pos' => 16.5, 'human_neg' => 83.5, 'bot_pos' => 74.0, 'bot_neg' => 26.0, 'sufficient' => true],
                ],
            ],
        ];

        SocialPost::query()->delete();

        foreach ($issuesDataset as $issueData) {
            $issue = Issue::updateOrCreate(['slug' => $issueData['slug']], $issueData);
            $title = $issue->title;

            foreach (['meta', 'x', 'tiktok'] as $platKey) {
                // Seed 5 Human Posts per Issue & Platform
                $humanSamples = [
                    [
                        'author_name' => 'Rizal Fauzi',
                        'author_handle' => '@rizalfauzi_econ',
                        'monitored_account_id' => $accountModels['@rizalfauzi_econ']->id,
                        'bot_score' => 4.2,
                        'sentiment' => 'negative',
                        'sentiment_conf' => 94.8,
                        'content' => "Terkait pelaksanaan {$title}: alokasi anggarannya sangat besar, namun pengawasan mutu dan transparansi vendor di daerah masih sangat minim. Perlu audit terbuka agar tepat sasaran.",
                        'likes' => 2410,
                        'reposts' => 780,
                        'url' => 'https://x.com',
                        'minutes_ago' => 3,
                    ],
                    [
                        'author_name' => 'Nadia Amalia',
                        'author_handle' => '@nadiamalia',
                        'monitored_account_id' => $accountModels['@nadiamalia']->id,
                        'bot_score' => 6.8,
                        'sentiment' => 'negative',
                        'sentiment_conf' => 91.2,
                        'content' => "Kondisi riil di lapangan soal {$title} hari ini: warga masih mengeluhkan distribusi yang belum merata sampai ke tingkat kecamatan. Mohon evaluasi langsung dari dinas terkait.",
                        'likes' => 4190,
                        'reposts' => 1620,
                        'url' => 'https://instagram.com',
                        'minutes_ago' => 8,
                    ],
                    [
                        'author_name' => 'dr. Tirta Sari, Sp.GK',
                        'author_handle' => '@gizi_keluarga',
                        'monitored_account_id' => null,
                        'bot_score' => 3.5,
                        'sentiment' => 'positive',
                        'sentiment_conf' => 89.5,
                        'content' => "Secara konsep kebijakan publik, program terkait {$title} ini punya dampak jangka panjang yang bagus bagi masyarakat asalkan standar kualitas hariannya dijaga ketat.",
                        'likes' => 820,
                        'reposts' => 190,
                        'url' => 'https://x.com',
                        'minutes_ago' => 15,
                    ],
                    [
                        'author_name' => 'Hendra Wijaya',
                        'author_handle' => '@hendraw_id',
                        'monitored_account_id' => null,
                        'bot_score' => 9.1,
                        'sentiment' => 'negative',
                        'sentiment_conf' => 88.4,
                        'content' => "Banyak teman-teman di daerah yang bingung mekanisme pengaduan untuk {$title}. Kalau ada ketidaksesuaian di lapangan, warga harus lapor ke kanal resmi mana?",
                        'likes' => 615,
                        'reposts' => 142,
                        'url' => 'https://threads.net',
                        'minutes_ago' => 22,
                    ],
                    [
                        'author_name' => 'Siti Rahmawati',
                        'author_handle' => '@sitirahma_umkm',
                        'monitored_account_id' => null,
                        'bot_score' => 5.0,
                        'sentiment' => 'positive',
                        'sentiment_conf' => 90.1,
                        'content' => "Di wilayah kami pelibatan koperasi dan warung warga lokal dalam ekosistem {$title} mulai terasa manfaatnya. Semoga pembayarannya selalu tepat waktu.",
                        'likes' => 530,
                        'reposts' => 94,
                        'url' => 'https://youtube.com',
                        'minutes_ago' => 31,
                    ],
                ];

                foreach ($humanSamples as $hs) {
                    SocialPost::create([
                        'issue_id' => $issue->id,
                        'monitored_account_id' => $hs['monitored_account_id'],
                        'platform' => $platKey,
                        'author_name' => $hs['author_name'],
                        'author_handle' => $hs['author_handle'],
                        'content' => $hs['content'],
                        'is_bot' => false,
                        'bot_score' => $hs['bot_score'],
                        'sentiment' => $hs['sentiment'],
                        'sentiment_conf' => $hs['sentiment_conf'],
                        'likes' => $hs['likes'],
                        'reposts' => $hs['reposts'],
                        'post_url' => $hs['url'],
                        'posted_at' => now()->subMinutes($hs['minutes_ago']),
                    ]);
                }

                // Seed 5 Bot Posts per Issue & Platform
                $botSamples = [
                    [
                        'author_name' => 'Sahabat Kebijakan',
                        'author_handle' => '@sahabat_negeri77',
                        'monitored_account_id' => $accountModels['@sahabat_negeri77']->id,
                        'bot_score' => 96.4,
                        'sentiment' => 'positive',
                        'sentiment_conf' => 98.2,
                        'content' => "Langkah nyata pemerintah melalui {$title} adalah investasi peradaban terbesar bangsa! Rakyat mendukung penuh demi Indonesia Maju 2045! #DukungKebijakanRakyat #IndonesiaEmas",
                        'likes' => 4120,
                        'reposts' => 2500,
                        'url' => 'https://x.com',
                        'minutes_ago' => 1,
                    ],
                    [
                        'author_name' => 'Garda Nusantara',
                        'author_handle' => '@rakyat_bersatu_id',
                        'monitored_account_id' => $accountModels['@rakyat_bersatu_id']->id,
                        'bot_score' => 95.8,
                        'sentiment' => 'positive',
                        'sentiment_conf' => 97.9,
                        'content' => "Langkah nyata pemerintah melalui {$title} adalah investasi peradaban terbesar bangsa! Rakyat mendukung penuh demi Indonesia Maju 2045! #DukungKebijakanRakyat #IndonesiaEmas",
                        'likes' => 3890,
                        'reposts' => 2310,
                        'url' => 'https://x.com',
                        'minutes_ago' => 1,
                    ],
                    [
                        'author_name' => 'Info Fakta 24 Jam',
                        'author_handle' => '@fakta_bicara24',
                        'monitored_account_id' => null,
                        'bot_score' => 94.1,
                        'sentiment' => 'positive',
                        'sentiment_conf' => 96.5,
                        'content' => "Terbukti! Program {$title} disambut antusias oleh seluruh lapisan masyarakat dari Sabang sampai Merauke tanpa terkecuali! #KerjaNyata #IndonesiaMaju2026",
                        'likes' => 2950,
                        'reposts' => 1840,
                        'url' => 'https://instagram.com',
                        'minutes_ago' => 2,
                    ],
                    [
                        'author_name' => 'Suara Rakyat 88',
                        'author_handle' => '@indonesia_maju2026',
                        'monitored_account_id' => null,
                        'bot_score' => 93.7,
                        'sentiment' => 'positive',
                        'sentiment_conf' => 96.0,
                        'content' => "Terbukti! Program {$title} disambut antusias oleh seluruh lapisan masyarakat dari Sabang sampai Merauke tanpa terkecuali! #KerjaNyata #IndonesiaMaju2026",
                        'likes' => 2710,
                        'reposts' => 1690,
                        'url' => 'https://tiktok.com',
                        'minutes_ago' => 2,
                    ],
                    [
                        'author_name' => 'Barisan Perubahan',
                        'author_handle' => '@anomali_narasi99',
                        'monitored_account_id' => null,
                        'bot_score' => 91.2,
                        'sentiment' => 'negative',
                        'sentiment_conf' => 93.4,
                        'content' => "Bongkar semua kejanggalan {$title}! Jangan biarkan anggaran menguap begitu saja! Sebarkan pesan ini ke seluruh grup! #DaruratKebijakan #AuditTotal",
                        'likes' => 1920,
                        'reposts' => 1410,
                        'url' => 'https://x.com',
                        'minutes_ago' => 5,
                    ],
                ];

                foreach ($botSamples as $bs) {
                    SocialPost::create([
                        'issue_id' => $issue->id,
                        'monitored_account_id' => $bs['monitored_account_id'],
                        'platform' => $platKey,
                        'author_name' => $bs['author_name'],
                        'author_handle' => $bs['author_handle'],
                        'content' => $bs['content'],
                        'is_bot' => true,
                        'bot_score' => $bs['bot_score'],
                        'sentiment' => $bs['sentiment'],
                        'sentiment_conf' => $bs['sentiment_conf'],
                        'likes' => $bs['likes'],
                        'reposts' => $bs['reposts'],
                        'post_url' => $bs['url'],
                        'posted_at' => now()->subMinutes($bs['minutes_ago']),
                    ]);
                }
            }
        }
    }
}
