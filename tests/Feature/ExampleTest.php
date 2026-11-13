<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use Database\Seeders\SospemDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all main SOSPEM pages, issue CRUD, post analysis, account audit, and real-time ingest pipeline.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(SospemDatabaseSeeder::class);

        $this->get('/')->assertStatus(200);
        $this->get('/issues')->assertStatus(200);
        $this->get('/feed/makan-bergizi-gratis/x')->assertStatus(200);
        $this->get('/accounts')->assertStatus(200);
        $this->get('/analytics')->assertStatus(200);
        $this->get('/analytics?issue=makan-bergizi-gratis')->assertStatus(200);
        $this->get('/methodology')->assertStatus(200);

        // Test tambah isu baru
        $this->post('/issues', [
            'title' => 'Subsidi KRL Jabodetabek',
            'keywords' => 'krl, tarif krl, commuter line',
            'trend' => 'up',
        ])->assertRedirect('/issues');

        $newIssue = Issue::where('slug', 'subsidi-krl-jabodetabek')->first();
        $this->assertNotNull($newIssue);

        // Test uji input & analisis postingan baru
        $this->post('/posts', [
            'issue_id' => $newIssue->id,
            'platform' => 'x',
            'author_name' => 'Bot Kampanye 99',
            'author_handle' => '@buzzer_amp_99',
            'content' => 'Dukungan penuh untuk Subsidi KRL Jabodetabek! #IndonesiaMaju #KerjaNyata',
        ])->assertRedirect('/issues');

        // Test ubah tren isu
        $this->patch("/issues/{$newIssue->id}/trend", [
            'trend' => 'down',
        ])->assertRedirect('/issues');

        $this->assertSame('down', $newIssue->fresh()->trend);

        // Test hapus isu
        $this->delete("/issues/{$newIssue->id}")->assertRedirect('/issues');
        $this->assertDatabaseMissing('issues', ['id' => $newIssue->id]);

        // Test tambah & hapus audit akun
        $this->post('/accounts', [
            'name' => 'Akun Uji Audit',
            'handle' => '@akun_audit_88',
            'platform' => 'X (Twitter)',
            'age' => '10 hari',
            'followers' => 5,
            'following' => 1200,
            'posting_frequency' => '150 post / hari',
        ])->assertRedirect('/accounts?handle=%40akun_audit_88');

        $audited = MonitoredAccount::where('handle', '@akun_audit_88')->first();
        $this->assertNotNull($audited);
        $this->assertTrue($audited->is_bot);

        $this->delete("/accounts/{$audited->id}")->assertRedirect('/accounts');
        $this->assertDatabaseMissing('monitored_accounts', ['id' => $audited->id]);

        // Test ingest stream standar
        $this->postJson('/ingest', [
            'issue_slug' => 'makan-bergizi-gratis',
            'platform' => 'x',
            'author_name' => 'Warga Uji',
            'author_handle' => '@warga_uji',
            'content' => 'Evaluasi pelaksanaan Makan Bergizi Gratis di daerah.',
        ])->assertStatus(200)->assertJsonPath('status', 'ok');

        // Test /api/ingest dengan parameter AI Qwen dari JupyterHub (prioritas AI + auto issue matching)
        $aiResponse = $this->postJson('/api/ingest', [
            'issue_slug' => 'auto',
            'platform' => 'youtube',
            'author_name' => 'Akun Bot Jupyter',
            'author_handle' => '@jupyter_qwen_bot',
            'content' => 'Komentar tentang kebijakan iuran BPJS Kesehatan terbaru di rumah sakit.',
            'likes_count' => 42,
            'ai_sentiment' => 'Negatif',
            'ai_bot_score' => 84.5,
            'ai_bot_reason' => 'Terdeteksi pola repetisi kalimat identik oleh Qwen AI.',
            'ai_institutions' => [
                ['lembaga' => 'Kantor Wakil Presiden', 'sentimen' => 'Positif', 'alasan' => 'Apresiasi kunjungan kerja'],
                ['lembaga' => 'DPR RI', 'sentimen' => 'Negatif', 'alasan' => 'Kritik pengawasan regulasi'],
            ],
            'source_url' => 'https://youtube.com/watch?v=test123',
        ]);

        $aiResponse->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('post.is_bot', true)
            ->assertJsonPath('post.bot_score', 84.5)
            ->assertJsonPath('post.sentiment', 'negative')
            ->assertJsonPath('post.likes', 42)
            ->assertJsonPath('post.url', 'https://youtube.com/watch?v=test123')
            ->assertJsonPath('post.ai_bot_reason', 'Terdeteksi pola repetisi kalimat identik oleh Qwen AI.');

        // Test /api/ingest-batch dengan deduplikasi (author_handle + content)
        $batchItems = [
            [
                'issue_slug' => 'auto',
                'platform' => 'tiktok',
                'author_name' => 'User Batch 1',
                'author_handle' => '@batch_user_1',
                'content' => 'Komentar unik batch pertama tentang makan bergizi gratis di sekolah.',
                'likes_count' => 15,
                'ai_sentiment' => 'Positif',
                'ai_bot_score' => 10.0,
                'ai_bot_reason' => 'Warga organik murni.',
                'ai_institutions' => [
                    ['lembaga' => 'Kementerian Pendidikan', 'sentimen' => 'Positif', 'alasan' => 'Distribusi tepat sasaran'],
                ],
            ],
            [
                'issue_slug' => 'auto',
                'platform' => 'tiktok',
                'author_name' => 'User Batch 1',
                'author_handle' => '@batch_user_1',
                'content' => 'Komentar unik batch pertama tentang makan bergizi gratis di sekolah.',
                'likes_count' => 15,
                'ai_sentiment' => 'Positif',
                'ai_bot_score' => 10.0,
            ],
        ];

        $this->postJson('/api/ingest-batch', ['items' => $batchItems])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('inserted', 1)
            ->assertJsonPath('skipped_duplicates', 1);

        // Test /api/live-stats untuk polling real-time
        $this->getJson('/api/live-stats?issue_slug=makan-bergizi-gratis&platform=tiktok')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'kpi', 'issues', 'human_posts', 'bot_posts']);

        // Test halaman Chatbot & endpoint konteks + tanya agent (database & external Http::fake)
        $this->get('/chatbot')->assertStatus(200);

        $this->getJson('/api/chatbot/context')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'kpi', 'issues', 'institutions', 'top_bot_accounts', 'recent_posts', 'context_text']);

        $this->postJson('/api/chatbot/ask', [
            'message' => 'Bagaimana sentimen per lembaga pemerintahan saat ini?',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('source', 'database_rag_engine');

        Http::preventStrayRequests();
        Http::fake([
            '127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Berdasarkan data SOSPEM, terpantau komentar terkait lembaga pemerintahan.',
                ],
            ], 200),
        ]);

        $this->postJson('/api/chatbot/ask', [
            'message' => 'Berikan ringkasan singkat opini publik.',
            'agent_mode' => 'external',
            'agent_url' => 'http://127.0.0.1:11434/api/chat',
            'agent_model' => 'qwen2.5:3b',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('source', 'local_agent')
            ->assertJsonPath('reply', 'Berdasarkan data SOSPEM, terpantau komentar terkait lembaga pemerintahan.');
    }
}
