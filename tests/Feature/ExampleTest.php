<?php

namespace Tests\Feature;

use Database\Seeders\SospemDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all main SOSPEM pages and real-time ingest pipeline.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(SospemDatabaseSeeder::class);

        $this->get('/')->assertStatus(200);
        $this->get('/feed/makan-bergizi-gratis/x')->assertStatus(200);
        $this->get('/accounts')->assertStatus(200);
        $this->get('/analytics')->assertStatus(200);
        $this->get('/methodology')->assertStatus(200);

        $this->postJson('/ingest', [
            'issue_slug' => 'makan-bergizi-gratis',
            'platform' => 'x',
            'author_name' => 'Warga Uji',
            'author_handle' => '@warga_uji',
            'content' => 'Evaluasi pelaksanaan Makan Bergizi Gratis di daerah.',
        ])->assertStatus(200)->assertJsonPath('status', 'ok');
    }
}
