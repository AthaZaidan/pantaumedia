<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use App\Models\User;
use App\Services\ChatbotAssistantService;
use Database\Seeders\SospemDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

        // Tamu belum login diarahkan ke /login dan halaman login bersih tanpa bocoran akun default
        $this->get('/')->assertRedirect('/login');
        $loginPage = $this->get('/login')->assertStatus(200);
        $loginPage->assertDontSee('Akun Default:');
        $loginPage->assertDontSee('Isi Otomatis');

        // Uji gagal login dengan sandi salah
        $this->post('/login', [
            'email' => 'Admin',
            'password' => 'salah-sandi',
        ])->assertSessionHasErrors('email');

        // Uji berhasil login dengan username Admin & password admin
        $this->post('/login', [
            'email' => 'Admin',
            'password' => 'admin',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        $user = User::where('email', 'admin@sospem.id')->first();
        $this->assertNotNull($user);
        $this->assertSame('Admin', $user->name);

        $dashboardRes = $this->get('/')->assertStatus(200);
        $dashboardRes->assertDontSee('Simulasi Stream');
        $dashboardRes->assertSee('Mulai Scraping Live')->assertDontSee('🚀')->assertDontSee('⚡');
        $dashboardRes->assertSee('Pengaturan');

        $this->get('/settings')->assertStatus(200)
            ->assertSee('Screening JupyterHub')
            ->assertSee('Screening Koneksi Sekarang')
            ->assertSee('Tambah Isu Pantauan Baru')
            ->assertSee('Metodologi');

        $this->getJson('/api/jupyter/status?probe=1')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'status',
                'screening' => ['overall_state', 'overall_label', 'listener', 'ingest', 'ai_engine', 'jobs'],
            ]);

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
        $this->get('/chatbot')
            ->assertStatus(200)
            ->assertSee('Halo')
            ->assertSee('+ Chat Baru');

        $this->getJson('/api/chatbot/context')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'kpi', 'issues', 'institutions', 'top_bot_accounts', 'recent_posts', 'context_text']);

        // Test sapaan & "apakabar mu" menjawab langsung tanpa menu tawaran
        $hiRes = $this->postJson('/api/chatbot/ask', [
            'message' => 'hi',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok');
        $this->assertStringNotContainsString('Anda dapat bertanya atau berdiskusi tentang', (string) $hiRes->json('reply'));

        $kabarRes = $this->postJson('/api/chatbot/ask', [
            'message' => 'apakabar mu',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok');
        $this->assertStringContainsString('Kabar saya sangat baik', (string) $kabarRes->json('reply'));
        $this->assertStringNotContainsString('Ingin saya cek salah satu topik di atas?', (string) $kabarRes->json('reply'));

        // Test peran teman bicara santai & diskusi kebijakan pemerintahan Indonesia
        $this->postJson('/api/chatbot/ask', [
            'message' => 'ngobrol yuk',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertSee('kebijakan pemerintah');

        $this->postJson('/api/chatbot/ask', [
            'message' => 'Jelaskan tentang program Makan Bergizi Gratis dan peran pemerintah',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertSee('Badan Gizi Nasional');

        $this->postJson('/api/chatbot/ask', [
            'message' => 'Bagaimana fungsi pengawasan DPR RI?',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertSee('legislasi');

        // Test perhitungan matematika langsung
        $this->postJson('/api/chatbot/ask', [
            'message' => 'berapa 25 * 40',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertSee('1.000');

        // Test perpindahan topik cepat & kata kunci singkat (tanpa pertanyaan balik yang kaku)
        $oasisRes = $this->postJson('/api/chatbot/ask', [
            'message' => 'oasis dont go away',
            'agent_mode' => 'database',
            'history' => [
                ['role' => 'user', 'content' => 'apa kamu bisa melihat berita tentang kemunduran gibran'],
                ['role' => 'assistant', 'content' => 'Berikut beberapa berita terkait.'],
            ],
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok');
        $this->assertStringContainsString('Oasis', (string) $oasisRes->json('reply'));
        $this->assertStringNotContainsString('bisa dijelaskan maksud', strtolower((string) $oasisRes->json('reply')));

        // Pastikan cleanReplyText membersihkan tanda bintang markdown (*Konde.co*) tanpa merusak rumus (25 * 40)
        $assistantService = app(ChatbotAssistantService::class);
        $this->assertSame('Artikel di Konde.co dan 25 * 40 = 1000', $assistantService->cleanReplyText('Artikel di *Konde.co* dan 25 * 40 = 1000'));

        $this->postJson('/api/chatbot/ask', [
            'message' => 'Bagaimana sentimen per lembaga pemerintahan saat ini?',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('source', 'database_rag_engine');

        // Test obrolan harian, tanggal hari ini, & update website
        $this->postJson('/api/chatbot/ask', [
            'message' => 'Hari ini tanggal berapa ya?',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertSee('WIB');

        $this->postJson('/api/chatbot/ask', [
            'message' => 'Apa update terbaru website kita?',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertSee('Dashboard Isu');

        Http::preventStrayRequests();
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 31.4,
                    'apparent_temperature' => 35.0,
                    'relative_humidity_2m' => 72,
                    'weather_code' => 2,
                    'wind_speed_10m' => 11.2,
                ],
            ], 200),
            'news.google.com/*' => Http::response('', 503),
            '127.0.0.1:59999/*' => Http::response('', 503),
            '127.0.0.1:11434/api/chat' => Http::response([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Berdasarkan data SOSPEM, terpantau komentar terkait lembaga pemerintahan.',
                ],
            ], 200),
        ]);

        // Test pertanyaan cuaca live via Open-Meteo
        $this->postJson('/api/chatbot/ask', [
            'message' => 'Bagaimana cuaca di Medan hari ini?',
            'agent_mode' => 'database',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertSee('Medan')
            ->assertSee('31.4');

        // Regresi: pertanyaan cuaca "apa kondisi cuaca saat ini" setelah obrolan Wapres tidak boleh tertimpa topik Gibran
        $govHistory = [
            ['role' => 'user', 'content' => 'apa kabar dengan wakil presiden indonesia saat ini'],
            ['role' => 'assistant', 'content' => 'Saat ini Wakil Presiden Republik Indonesia adalah Gibran Rakabuming Raka.'],
        ];
        $this->assertSame(
            'apa kondisi cuaca saat ini',
            $assistantService->resolveContextualMessage('apa kondisi cuaca saat ini', $govHistory)
        );

        $weatherSwitchRes = $this->postJson('/api/chatbot/ask', [
            'message' => 'apa kondisi cuaca saat ini',
            'agent_mode' => 'auto',
            'agent_url' => 'http://127.0.0.1:59999/api/chat',
            'history' => $govHistory,
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok');
        $weatherSwitchReply = (string) $weatherSwitchRes->json('reply');
        $this->assertStringContainsString('Medan', $weatherSwitchReply);
        $this->assertStringContainsString('31.4°C', $weatherSwitchReply);
        $this->assertStringNotContainsString('Gibran', $weatherSwitchReply);

        // Regresi: cleanReplyText membersihkan tabel Markdown (| Tanggal | ...) menjadi daftar poin rapi
        $markdownTableInput = "Berikut kabar terbaru:\n\n| Tanggal | Judul Berita | Ringkasan |\n|---|---|---|\n| 15 Nov 2026 | Judul A | Ringkasan singkat. |";
        $cleanedTableOutput = $assistantService->cleanReplyText($markdownTableInput);
        $this->assertStringNotContainsString('|', $cleanedTableOutput);
        $this->assertStringNotContainsString('---', $cleanedTableOutput);
        $this->assertStringContainsString('• 15 Nov 2026 — Judul A — Ringkasan singkat.', $cleanedTableOutput);

        // Regresi: "apa kabar dengan wakil presiden kita" saat hosted server tidak punya Ollama & AI cloud/RSS sedang gagal
        $wapresRes = $this->postJson('/api/chatbot/ask', [
            'message' => 'apa kabar dengan wakil presiden kita',
            'agent_mode' => 'auto',
            'agent_url' => 'http://127.0.0.1:59999/api/chat',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok');
        $wapresReply = (string) $wapresRes->json('reply');
        $this->assertStringContainsString('Gibran Rakabuming Raka', $wapresReply);
        $this->assertStringContainsString('Wakil Presiden', $wapresReply);
        $this->assertStringContainsString('Statistik Database SOSPEM', $wapresReply);
        $this->assertStringNotContainsString('Harry S. Truman', $wapresReply);
        $this->assertStringNotContainsString('Kabar saya sangat baik', $wapresReply);

        // Regresi multi-turn: klarifikasi "Indonesia dong" dan "kalau dpr gimana?" mengikuti riwayat 2-3 pesan user sebelumnya
        $followupRes = $this->postJson('/api/chatbot/ask', [
            'message' => 'Indonesia dong',
            'agent_mode' => 'auto',
            'agent_url' => 'http://127.0.0.1:59999/api/chat',
            'history' => [
                ['role' => 'user', 'content' => 'apa kabar dengan wakil presiden kita'],
                ['role' => 'assistant', 'content' => 'Harry S. Truman adalah presiden Amerika Serikat ke-33.'],
            ],
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok');
        $followupReply = (string) $followupRes->json('reply');
        $this->assertStringContainsString('Gibran Rakabuming Raka', $followupReply);
        $this->assertStringContainsString('Wakil Presiden', $followupReply);
        $this->assertStringNotContainsString('Asia Tenggara dan Oseania', $followupReply);
        $this->assertStringNotContainsString('Harry S. Truman', $followupReply);

        $dprFollowupRes = $this->postJson('/api/chatbot/ask', [
            'message' => 'kalau dpr gimana?',
            'agent_mode' => 'auto',
            'agent_url' => 'http://127.0.0.1:59999/api/chat',
            'history' => [
                ['role' => 'user', 'content' => 'apa kabar dengan wakil presiden kita'],
                ['role' => 'assistant', 'content' => 'Wakil Presiden RI saat ini adalah Gibran Rakabuming Raka.'],
                ['role' => 'user', 'content' => 'indonesia dong'],
                ['role' => 'assistant', 'content' => 'Berikut kabar Wakil Presiden RI Gibran Rakabuming Raka.'],
            ],
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok');
        $dprReply = (string) $dprFollowupRes->json('reply');
        $this->assertStringContainsString('DPR RI', $dprReply);
        $this->assertStringContainsString('Statistik Database SOSPEM', $dprReply);

        // Pastikan setelah worker JupyterHub memanggil endpoint polling/ingest, screening membaca status Terhubung
        $this->getJson('/api/scrape-jobs/pending')->assertStatus(200);
        $this->getJson('/api/jupyter/status')
            ->assertStatus(200)
            ->assertJsonPath('screening.overall_state', 'connected');

        $this->postJson('/api/chatbot/ask', [
            'message' => 'Berikan ringkasan singkat opini publik.',
            'agent_mode' => 'external',
            'agent_url' => 'http://127.0.0.1:11434/api/chat',
            'agent_model' => 'qwen2.5:3b',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('source', 'local_agent')
            ->assertJsonPath('reply', 'Berdasarkan data SOSPEM, terpantau komentar terkait lembaga pemerintahan.');

        // Pastikan endpoint Reverse-Bridge Chatbot JupyterHub (/api/chat-bridge/pending & /{taskId}/reply) mencatat heartbeat
        $this->getJson('/api/chat-bridge/pending')
            ->assertStatus(200)
            ->assertJsonPath('status', 'idle')
            ->assertJsonPath('task', null);

        $this->getJson('/api/jupyter/status')
            ->assertStatus(200)
            ->assertJsonPath('screening.overall_state', 'connected')
            ->assertJsonPath('screening.listener.last_source_label', 'Bridge Chatbot AI (/api/chat-bridge/pending)');

        Cache::put('sospem_chat_bridge_pending', [
            'id' => 'bridge-task-001',
            'model' => 'qwen2.5:1.5b',
            'messages' => [['role' => 'user', 'content' => 'Halo Qwen']],
        ], now()->addSeconds(30));

        $this->getJson('/api/chat-bridge/pending')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('task.id', 'bridge-task-001');

        $this->postJson('/api/chat-bridge/bridge-task-001/reply', [
            'reply' => 'Halo! Opini pemerintahan terpantau aktif di SOSPEM.',
            'model' => 'Qwen/Qwen2.5-1.5B-Instruct',
        ])->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        $this->assertSame(
            'Halo! Opini pemerintahan terpantau aktif di SOSPEM.',
            Cache::get('sospem_chat_bridge_reply_bridge-task-001')['reply'] ?? null
        );

        $this->getJson('/api/jupyter/status')
            ->assertStatus(200)
            ->assertJsonPath('screening.overall_state', 'connected')
            ->assertJsonPath('screening.listener.last_source_label', 'Balasan Chatbot Qwen (/api/chat-bridge/{id}/reply)');

        Cache::forget('sospem_chat_bridge_last_seen');

        // Uji error handling saat pesan kosong / whitespace
        $this->postJson('/api/chatbot/ask', [
            'message' => '   ',
        ])->assertStatus(422)
            ->assertJsonPath('status', 'error');

        // Uji logout
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_chatbot_switches_topics_cleanly_after_clock_and_follows_up_wapres_issue(): void
    {
        $this->seed(SospemDatabaseSeeder::class);

        $rssXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Google News - Gibran Rakabuming Raka</title>
    <item>
      <title>Perkembangan Terbaru Wakil Presiden Gibran Rakabuming Raka - Kompas.com</title>
      <link>https://news.google.com/articles/gibran-update</link>
      <pubDate>Sun, 15 Nov 2026 05:10:00 GMT</pubDate>
      <source>Kompas.com</source>
    </item>
  </channel>
</rss>
XML;

        Http::preventStrayRequests();
        Http::fake([
            'news.google.com/*' => Http::response($rssXml, 200, ['Content-Type' => 'application/xml']),
            '127.0.0.1:59999/*' => Http::response([], 503),
            '*' => Http::response([], 503),
        ]);

        $assistant = app(ChatbotAssistantService::class);

        $this->assertTrue($assistant->isTimeOrDateQuery('jam berapa ini'));
        $this->assertTrue($assistant->isTimeOrDateQuery('pukul berapa sekarang?'));
        $this->assertFalse($assistant->isTimeOrDateQuery('bukannya dia mau mengundurkan diri'));
        $this->assertFalse($assistant->isTimeOrDateQuery('jam berapa ini — bukannya dia mau mengundurkan diri'));

        $this->assertTrue($assistant->isInternetNewsOrInfoIntent('apa kondisi wakil presiden kita'));
        $this->assertTrue($assistant->isInternetNewsOrInfoIntent('bukannya dia mau mengundurkan diri'));

        $clockHistory = [
            ['role' => 'user', 'content' => 'jam berapa ini'],
            ['role' => 'assistant', 'content' => 'Sekarang pukul 12:24 WIB.'],
            ['role' => 'user', 'content' => 'apa kondisi wakil presiden kita'],
            ['role' => 'assistant', 'content' => 'Saat ini Wakil Presiden Republik Indonesia adalah Gibran Rakabuming Raka.'],
        ];

        $resolvedFollowUp = $assistant->resolveContextualMessage('bukannya dia mau mengundurkan diri', $clockHistory);
        $this->assertStringContainsString('Wakil Presiden Republik Indonesia Gibran Rakabuming Raka', $resolvedFollowUp);
        $this->assertStringContainsString('Wakil Presiden Gibran mengundurkan diri mundur', $resolvedFollowUp);
        $this->assertStringNotContainsString('jam berapa', strtolower($resolvedFollowUp));
        $this->assertFalse($assistant->isTimeOrDateQuery($resolvedFollowUp));

        $newsForFollowUp = $assistant->fetchInternetNewsOrSearch($resolvedFollowUp);
        $this->assertTrue($newsForFollowUp['ok']);
        $this->assertStringContainsString('Wakil Presiden Gibran mengundurkan diri mundur', $newsForFollowUp['formatted']);

        $clockResponse = $this->postJson('/api/chatbot/ask', [
            'message' => 'jam berapa ini',
        ])->assertStatus(200);
        $this->assertStringContainsString('WIB', (string) $clockResponse->json('reply'));

        $wapresResponse = $this->postJson('/api/chatbot/ask', [
            'message' => 'apa kondisi wakil presiden kita',
            'history' => [
                ['role' => 'user', 'content' => 'jam berapa ini'],
                ['role' => 'assistant', 'content' => (string) $clockResponse->json('reply')],
            ],
        ])->assertStatus(200);
        $wapresReply = (string) $wapresResponse->json('reply');
        $this->assertStringContainsString('Gibran Rakabuming Raka', $wapresReply);
        $this->assertStringContainsString('Perkembangan Terbaru Wakil Presiden Gibran Rakabuming Raka', $wapresReply);

        $followUpResponse = $this->postJson('/api/chatbot/ask', [
            'message' => 'bukannya dia mau mengundurkan diri',
            'history' => [
                ['role' => 'user', 'content' => 'jam berapa ini'],
                ['role' => 'assistant', 'content' => (string) $clockResponse->json('reply')],
                ['role' => 'user', 'content' => 'apa kondisi wakil presiden kita'],
                ['role' => 'assistant', 'content' => $wapresReply],
            ],
        ])->assertStatus(200);
        $followUpReply = (string) $followUpResponse->json('reply');
        $this->assertStringContainsString('Gibran Rakabuming Raka', $followUpReply);
        $this->assertStringNotContainsString('Menurut jam sistem saat ini', $followUpReply);

        $govResponse = $this->postJson('/api/chatbot/ask', [
            'message' => 'tentang pemerintahan',
            'history' => [
                ['role' => 'user', 'content' => 'apa yang terjadi di indonesia'],
                ['role' => 'assistant', 'content' => 'Berita Terkini di Internet (Indonesia): ...'],
            ],
        ])->assertStatus(200);
        $govReply = (string) $govResponse->json('reply');
        $this->assertStringContainsString('Prabowo Subianto', $govReply);
        $this->assertStringContainsString('Gibran Rakabuming Raka', $govReply);
        $this->assertStringNotContainsString('koneksi ke layanan AI eksternal saat ini belum aktif', $govReply);

        $offlineContextHistory = [
            ['role' => 'user', 'content' => 'apa yang terjadi di indonesia'],
            ['role' => 'assistant', 'content' => 'Berita Terkini di Internet (Indonesia): ...'],
            ['role' => 'user', 'content' => 'tentang pemerintahan'],
            ['role' => 'assistant', 'content' => 'Topik tersebut menarik, hanya saja koneksi ke layanan AI eksternal saat ini belum aktif dari server.'],
        ];

        $this->assertSame('kenapa begitu', $assistant->resolveContextualMessage('kenapa begitu', $offlineContextHistory));
        $this->assertSame('lah', $assistant->resolveContextualMessage('lah', $offlineContextHistory));

        $whyReply = (string) $this->postJson('/api/chatbot/ask', [
            'message' => 'kenapa begitu',
            'history' => $offlineContextHistory,
        ])->assertStatus(200)->json('reply');
        $this->assertStringNotContainsString('yang terjadi indonesia', strtolower($whyReply));
        $this->assertStringNotContainsString('Berita Terkini di Internet', $whyReply);

        $lahReply = (string) $this->postJson('/api/chatbot/ask', [
            'message' => 'lah',
            'history' => $offlineContextHistory,
        ])->assertStatus(200)->json('reply');
        $this->assertStringNotContainsString('koneksi ke layanan AI eksternal saat ini belum aktif dari server', $lahReply);
    }
}
