<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\SocialPost;
use Database\Seeders\SospemDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScrapeJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrape_job_lifecycle(): void
    {
        // 1. Buat tugas scraping baru
        $createRes = $this->postJson('/api/scrape-jobs', [
            'topic' => 'Kenaikan Tarif PPN',
            'limit' => 15,
        ]);

        $createRes->assertOk()
            ->assertJson([
                'status' => 'ok',
            ]);

        $jobId = $createRes->json('job_id');
        $this->assertNotNull($jobId);

        // Pastikan isu 'Kenaikan Tarif PPN' otomatis dibuat di tabel issues
        $this->assertDatabaseHas('issues', [
            'slug' => 'kenaikan-tarif-ppn',
            'title' => 'Kenaikan Tarif PPN',
        ]);

        // 2. JupyterHub polling mengambil tugas pending
        $pendingRes = $this->getJson('/api/scrape-jobs/pending');
        $pendingRes->assertOk()
            ->assertJsonPath('job.id', $jobId)
            ->assertJsonPath('job.topic', 'Kenaikan Tarif PPN')
            ->assertJsonPath('job.limit', 15)
            ->assertJsonPath('job.issue_slug', 'kenaikan-tarif-ppn');

        // Saat dicek kembali, status sudah berubah jadi running sehingga tidak diambil ganda
        $nextPending = $this->getJson('/api/scrape-jobs/pending');
        $nextPending->assertOk()
            ->assertJson(['job' => null]);

        // Cek status running lewat endpoint GET /api/scrape-jobs/{id}
        $statusRes = $this->getJson("/api/scrape-jobs/{$jobId}");
        $statusRes->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('job_status', 'running')
            ->assertJsonPath('job.status', 'running');

        // 3. JupyterHub melaporkan bahwa tugas telah selesai
        $doneRes = $this->postJson("/api/scrape-jobs/{$jobId}/done", [
            'scraped_count' => 12,
        ]);
        $doneRes->assertOk()
            ->assertJson(['status' => 'ok']);

        // Cek status akhir lewat GET /api/scrape-jobs/{id}
        $finalStatusRes = $this->getJson("/api/scrape-jobs/{$jobId}");
        $finalStatusRes->assertOk()
            ->assertJsonPath('job_status', 'completed')
            ->assertJsonPath('scraped_count', 12)
            ->assertJsonPath('job.status', 'completed')
            ->assertJsonPath('job.scraped_count', 12);
    }

    public function test_scrape_job_validation(): void
    {
        $res = $this->postJson('/api/scrape-jobs', [
            'topic' => '',
            'limit' => 999,
        ]);

        $res->assertUnprocessable()
            ->assertJsonValidationErrors(['topic', 'limit']);
    }

    public function test_ingest_creates_dynamic_issue_when_slug_and_title_provided(): void
    {
        $this->seed(SospemDatabaseSeeder::class);

        $res = $this->postJson('/api/ingest', [
            'issue_slug' => 'gempa-bumi-cianjur',
            'issue_title' => 'Gempa Bumi Cianjur',
            'platform' => 'tiktok',
            'author_name' => 'Warga Jawa Barat',
            'author_handle' => '@warga_jabar',
            'content' => 'Getaran terasa cukup kencang beberapa detik lalu, mohon warga tetap waspada.',
            'ai_sentiment' => 'negative',
            'ai_bot_score' => 15.0,
        ]);

        $res->assertOk();
        $this->assertDatabaseHas('issues', [
            'slug' => 'gempa-bumi-cianjur',
            'title' => 'Gempa Bumi Cianjur',
        ]);

        $issue = Issue::where('slug', 'gempa-bumi-cianjur')->first();
        $this->assertNotNull($issue);
        $this->assertEquals(1, $issue->total_posts);
    }

    public function test_ingest_auto_resolves_to_running_scrape_job_topic(): void
    {
        $this->seed(SospemDatabaseSeeder::class);

        // Buat job scrape 'Banjir Demak' lalu ubah status jadi running
        $createRes = $this->postJson('/api/scrape-jobs', [
            'topic' => 'Banjir Demak',
            'limit' => 10,
        ]);
        $jobId = $createRes->json('job_id');

        // JupyterHub mengambil job (status jadi running)
        $this->getJson('/api/scrape-jobs/pending');

        // Post masuk dengan issue_slug 'auto'
        $ingestRes = $this->postJson('/api/ingest', [
            'issue_slug' => 'auto',
            'platform' => 'tiktok',
            'author_name' => 'Relawan Bencana',
            'author_handle' => '@relawan_demak',
            'content' => 'Bantuan sembako dan logistik perahu karet telah disalurkan ke posko utama.',
            'ai_sentiment' => 'positive',
            'ai_bot_score' => 10.0,
        ]);

        $ingestRes->assertOk();

        // Harus masuk ke isu 'Banjir Demak' dan BUKAN MBG
        $this->assertDatabaseHas('social_posts', [
            'author_handle' => '@relawan_demak',
        ]);

        $post = SocialPost::where('author_handle', '@relawan_demak')->first();
        $this->assertNotNull($post);

        $issue = Issue::find($post->issue_id);
        $this->assertNotNull($issue);
        $this->assertEquals('banjir-demak', $issue->slug);
        $this->assertEquals('Banjir Demak', $issue->title);
    }

    public function test_ingest_auto_matches_keywords_to_correct_issue_not_mbg(): void
    {
        $this->seed(SospemDatabaseSeeder::class);

        // Uji 1: Post berisi kabut asap & kebakaran hutan harus masuk ke Karhutla
        $resKarhutla = $this->postJson('/api/ingest', [
            'issue_slug' => 'auto',
            'platform' => 'x',
            'author_name' => 'Warga Riau',
            'author_handle' => '@warga_riau',
            'content' => 'Kabut asap akibat kebakaran hutan makin pekat di perbatasan, jarak pandang tinggal 50 meter.',
            'source_news' => 'Titik Api Terdeteksi di Hutan Lindung',
            'ai_sentiment' => 'negative',
        ]);
        $resKarhutla->assertOk();
        $postKarhutla = SocialPost::where('author_handle', '@warga_riau')->first();
        $this->assertNotNull($postKarhutla);
        $this->assertEquals('karhutla', $postKarhutla->issue->slug);

        // Uji 2: Post berisi gas melon dan lpg harus masuk ke gas-alam
        $resGas = $this->postJson('/api/ingest', [
            'issue_slug' => 'auto',
            'platform' => 'meta',
            'author_name' => 'Ibu Rumah Tangga',
            'author_handle' => '@ibu_rt_pangan',
            'content' => 'Antrean tabung gas elpiji 3kg masih panjang di pangkalan kelurahan.',
            'source_news' => 'Kelangkaan Distribusi LPG Melon',
            'ai_sentiment' => 'negative',
        ]);
        $resGas->assertOk();
        $postGas = SocialPost::where('author_handle', '@ibu_rt_pangan')->first();
        $this->assertNotNull($postGas);
        $this->assertEquals('gas-alam', $postGas->issue->slug);

        // Uji 3: Post tidak berkaitan sama sekali dengan isu apapun masuk ke isu-umum-terkini, BUKAN MBG
        $resUmum = $this->postJson('/api/ingest', [
            'issue_slug' => 'auto',
            'platform' => 'tiktok',
            'author_name' => 'Pencinta Alam',
            'author_handle' => '@astronomi_id',
            'content' => 'Fenomena komet langka melintasi langit khatulistiwa malam ini, bintang terlihat sangat terang.',
            'ai_sentiment' => 'positive',
        ]);
        $resUmum->assertOk();
        $postUmum = SocialPost::where('author_handle', '@astronomi_id')->first();
        $this->assertNotNull($postUmum);
        $this->assertEquals('isu-umum-terkini', $postUmum->issue->slug);
        $this->assertNotEquals('makan-bergizi-gratis', $postUmum->issue->slug);
    }
}
