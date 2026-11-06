<?php

namespace Database\Seeders;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use App\Models\SocialPost;
use App\Services\PostAnalyzerService;
use Illuminate\Database\Seeder;

class SospemDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $analyzer = app(PostAnalyzerService::class);

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
                'trend' => 'up',
                'keywords' => ['makan bergizi gratis', 'mbg', 'menu mbg'],
                'multiplier' => 4,
            ],
            [
                'rank' => 2,
                'slug' => 'kabur-aja-dulu',
                'title' => 'kabur aja dulu',
                'subtitle' => 'Ketenagakerjaan & Pajak · Dominan di X',
                'trend' => 'down',
                'keywords' => ['kabur aja dulu', 'kerja luar negeri', 'pajak kelas menengah'],
                'multiplier' => 3,
            ],
            [
                'rank' => 3,
                'slug' => 'gas-alam',
                'title' => 'gas alam',
                'subtitle' => 'Pasokan & Distribusi Elpiji 3kg',
                'trend' => 'up',
                'keywords' => ['gas alam', 'elpiji 3kg', 'gas melon'],
                'multiplier' => 3,
            ],
            [
                'rank' => 4,
                'slug' => 'karhutla',
                'title' => 'Karhutla',
                'subtitle' => 'Titik Api Riau & Jambi',
                'trend' => 'up',
                'keywords' => ['karhutla', 'titik api', 'kabut asap'],
                'multiplier' => 2,
            ],
            [
                'rank' => 5,
                'slug' => 'kopdes',
                'title' => 'kopdes',
                'subtitle' => 'Koperasi Pedesaan Pangan',
                'trend' => 'stable',
                'keywords' => ['kopdes', 'koperasi desa', 'pangan desa'],
                'multiplier' => 2,
            ],
            [
                'rank' => 6,
                'slug' => 'subsidi-pupuk',
                'title' => 'subsidi pupuk',
                'subtitle' => 'Alokasi Petani Daerah',
                'trend' => 'stable',
                'keywords' => ['subsidi pupuk', 'pupuk urea', 'kartu tani'],
                'multiplier' => 2,
            ],
            [
                'rank' => 7,
                'slug' => 'polusi-udara-jkt',
                'title' => 'polusi udara jkt',
                'subtitle' => 'ISPU Jabodetabek',
                'trend' => 'up',
                'keywords' => ['polusi udara', 'ispu jakarta', 'kualitas udara'],
                'multiplier' => 1,
            ],
            [
                'rank' => 8,
                'slug' => 'kenaikan-pbb',
                'title' => 'kenaikan pbb',
                'subtitle' => 'Pajak Bumi & Bangunan Daerah',
                'trend' => 'down',
                'keywords' => ['kenaikan pbb', 'njop naik', 'pajak bumi bangunan'],
                'multiplier' => 1,
            ],
            [
                'rank' => 9,
                'slug' => 'jalan-rusak-lpg',
                'title' => 'jalan rusak lpg',
                'subtitle' => 'Infrastruktur Jalan Daerah',
                'trend' => 'stable',
                'keywords' => ['jalan rusak', 'infrastruktur daerah', 'perbaikan jalan'],
                'multiplier' => 1,
            ],
            [
                'rank' => 10,
                'slug' => 'iuran-tapera',
                'title' => 'iuran tapera',
                'subtitle' => 'Potongan Gaji & Tabungan',
                'trend' => 'down',
                'keywords' => ['iuran tapera', 'potongan tapera', 'tabungan perumahan'],
                'multiplier' => 1,
            ],
        ];

        SocialPost::query()->delete();

        // Jam distribusi agar grafik deret waktu per jam di Analytics tampak hidup
        $hoursPool = [1, 4, 7, 9, 10, 12, 13, 14, 16, 18, 19, 20, 22];

        foreach ($issuesDataset as $idx => $issueData) {
            $multiplier = $issueData['multiplier'];
            unset($issueData['multiplier']);

            $issue = Issue::updateOrCreate(['slug' => $issueData['slug']], array_merge($issueData, [
                'total_posts' => 0,
                'human_pct' => 50.0,
                'bot_pct' => 50.0,
                'pos_pct' => 50.0,
                'neg_pct' => 50.0,
                'platforms' => [],
            ]));

            $title = $issue->title;

            foreach (['meta', 'x', 'tiktok'] as $platKey) {
                $humanTemplates = [
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
                    ],
                ];

                $botTemplates = [
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
                    ],
                ];

                $platWeight = $platKey === 'x' ? ($multiplier + 1) : $multiplier;

                for ($round = 0; $round < $platWeight; $round++) {
                    foreach ($humanTemplates as $hIdx => $hs) {
                        $hour = $hoursPool[($idx + $round + $hIdx) % count($hoursPool)];
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
                            'posted_at' => now()->startOfDay()->addHours($hour)->addMinutes(($hIdx * 7) % 55),
                        ]);
                    }

                    // Lebih sedikit bot dibanding warga organik (~44% bot, ~56% warga organik)
                    $botSlice = array_slice($botTemplates, 0, ($idx % 2 === 0) ? 4 : 3);
                    foreach ($botSlice as $bIdx => $bs) {
                        $hour = $hoursPool[($idx + $round + $bIdx + 3) % count($hoursPool)];
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
                            'posted_at' => now()->startOfDay()->addHours($hour)->addMinutes(($bIdx * 11) % 55),
                        ]);
                    }
                }
            }

            // Hitung ulang statistik isu secara real dari baris social_posts yang baru dibuat
            $analyzer->recalculateIssueStats($issue);
        }

        $analyzer->rerankAllIssues();
    }
}
