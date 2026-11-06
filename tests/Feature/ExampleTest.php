<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\MonitoredAccount;
use Database\Seeders\SospemDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // Test ingest stream
        $this->postJson('/ingest', [
            'issue_slug' => 'makan-bergizi-gratis',
            'platform' => 'x',
            'author_name' => 'Warga Uji',
            'author_handle' => '@warga_uji',
            'content' => 'Evaluasi pelaksanaan Makan Bergizi Gratis di daerah.',
        ])->assertStatus(200)->assertJsonPath('status', 'ok');
    }
}
