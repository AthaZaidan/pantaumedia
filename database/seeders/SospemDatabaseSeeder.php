<?php

namespace Database\Seeders;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\PostAnalyzerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SospemDatabaseSeeder extends Seeder
{
    /**
     * Bersihkan seluruh data dummy (postingan & akun dummy) dan hanya siapkan
     * daftar kategori isu awal dengan nilai 0 agar siap menerima data riil dari AI Agent Qwen.
     */
    public function run(): void
    {
        $analyzer = app(PostAnalyzerService::class);

        // Pastikan akun admin default tersedia untuk login
        User::updateOrCreate(
            ['email' => 'admin@sospem.id'],
            [
                'name' => 'Admin SOSPEM',
                'password' => Hash::make('password'),
            ]
        );

        // Hapus seluruh data postingan dan akun dummy
        SocialPost::query()->delete();
        MonitoredAccount::query()->delete();

        // Daftar topik isu pantauan awal (tanpa postingan dummy, total_posts = 0)
        $issuesDataset = [
            [
                'rank' => 1,
                'slug' => 'makan-bergizi-gratis',
                'title' => 'Makan Bergizi Gratis (MBG)',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['makan bergizi gratis', 'mbg', 'menu mbg', 'gizi gratis'],
            ],
            [
                'rank' => 2,
                'slug' => 'kabur-aja-dulu',
                'title' => 'kabur aja dulu',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['kabur aja dulu', 'kerja luar negeri', 'pajak kelas menengah'],
            ],
            [
                'rank' => 3,
                'slug' => 'gas-alam',
                'title' => 'gas alam',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['gas alam', 'elpiji 3kg', 'gas melon'],
            ],
            [
                'rank' => 4,
                'slug' => 'karhutla',
                'title' => 'Karhutla',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['karhutla', 'titik api', 'kabut asap'],
            ],
            [
                'rank' => 5,
                'slug' => 'kopdes',
                'title' => 'kopdes',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['kopdes', 'koperasi desa', 'pangan desa'],
            ],
            [
                'rank' => 6,
                'slug' => 'subsidi-pupuk',
                'title' => 'subsidi pupuk',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['subsidi pupuk', 'pupuk urea', 'kartu tani'],
            ],
            [
                'rank' => 7,
                'slug' => 'polusi-udara-jkt',
                'title' => 'polusi udara jkt',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['polusi udara', 'ispu jakarta', 'kualitas udara'],
            ],
            [
                'rank' => 8,
                'slug' => 'kenaikan-pbb',
                'title' => 'kenaikan pbb',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['kenaikan pbb', 'njop naik', 'pajak bumi bangunan'],
            ],
            [
                'rank' => 9,
                'slug' => 'jalan-rusak-lpg',
                'title' => 'jalan rusak lpg',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['jalan rusak', 'infrastruktur daerah', 'perbaikan jalan'],
            ],
            [
                'rank' => 10,
                'slug' => 'iuran-tapera',
                'title' => 'iuran tapera',
                'subtitle' => 'Pantauan Aktif',
                'trend' => 'stable',
                'keywords' => ['iuran tapera', 'potongan tapera', 'tabungan perumahan', 'bpjs'],
            ],
        ];

        foreach ($issuesDataset as $issueData) {
            $issue = Issue::updateOrCreate(['slug' => $issueData['slug']], array_merge($issueData, [
                'total_posts' => 0,
                'human_pct' => 0.0,
                'bot_pct' => 0.0,
                'pos_pct' => 0.0,
                'neg_pct' => 0.0,
                'platforms' => [],
            ]));

            $analyzer->recalculateIssueStats($issue);
        }
    }
}
