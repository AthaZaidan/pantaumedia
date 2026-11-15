@extends('layouts.app')

@section('title', 'Pengaturan, Screening JupyterHub & Kelola Isu — SOSPEM')

@section('styles')
<style>
    .settings-tabs {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        border-bottom: 1px solid var(--color-border);
        padding-bottom: 12px;
    }

    .settings-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--color-border);
        background: var(--color-surface);
        color: var(--color-text-secondary);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .settings-tab-btn:hover {
        color: var(--color-text-primary);
        border-color: var(--color-border-strong);
        background: var(--color-surface-subtle);
    }

    .settings-tab-btn.is-active {
        background: var(--color-teal-bg);
        border-color: var(--color-teal-border);
        color: var(--color-teal-dark);
        font-weight: 600;
    }

    .settings-panel {
        display: none;
        flex-direction: column;
        gap: 18px;
    }

    .settings-panel.is-active {
        display: flex;
    }

    /* Screening Hero & Grid */
    .screening-hero-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 20px 24px;
        box-shadow: var(--shadow-card);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        flex-wrap: wrap;
    }

    .screening-hero-left {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        min-width: 0;
        flex: 1;
    }

    .status-dot-ring {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-top: 5px;
        flex-shrink: 0;
        background: var(--color-text-muted);
    }
    .status-dot-ring.is-connected {
        background: var(--color-teal);
        box-shadow: 0 0 0 4px rgba(24, 178, 111, 0.2);
    }
    .status-dot-ring.is-standby {
        background: #f59e0b;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.18);
    }
    .status-dot-ring.is-disconnected {
        background: #ef4444;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.18);
    }

    .screening-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
        border: 1px solid var(--color-border);
        background: var(--color-surface-subtle);
        color: var(--color-text-secondary);
    }
    .screening-badge.is-connected {
        background: var(--color-teal-bg);
        border-color: var(--color-teal-border);
        color: var(--color-teal-dark);
    }
    .screening-badge.is-standby {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.32);
        color: #f59e0b;
    }
    .screening-badge.is-disconnected {
        background: var(--color-red-bg);
        border-color: rgba(239, 68, 68, 0.35);
        color: var(--color-red-dark);
    }

    .screening-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
    @media (max-width: 900px) {
        .screening-grid {
            grid-template-columns: 1fr;
        }
    }

    .diag-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 18px 20px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .diag-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--color-border-subtle);
    }

    .diag-title {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--color-text-primary);
    }

    .diag-meta-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .diag-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        font-size: 12.5px;
    }

    .diag-key {
        color: var(--color-text-secondary);
        flex-shrink: 0;
    }

    .diag-val {
        color: var(--color-text-primary);
        font-weight: 500;
        text-align: right;
        word-break: break-word;
    }

    .endpoint-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }
    .endpoint-table th,
    .endpoint-table td {
        text-align: left;
        padding: 10px 14px;
        border-bottom: 1px solid var(--color-border-subtle);
    }
    .endpoint-table th {
        color: var(--color-text-secondary);
        font-weight: 600;
        background: var(--color-surface-subtle);
    }
    .endpoint-table tr:last-child td {
        border-bottom: none;
    }

    /* Kelola Isu Grid */
    .issues-manage-grid {
        display: grid;
        grid-template-columns: 360px 1fr;
        gap: 18px;
        align-items: start;
    }
    @media (max-width: 980px) {
        .issues-manage-grid {
            grid-template-columns: 1fr;
        }
    }

    .left-forms-stack {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .manage-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .manage-card-head {
        padding: 15px 20px;
        border-bottom: 1px solid var(--color-border);
        font-size: 13.5px;
        font-weight: 600;
        color: var(--color-text-primary);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .manage-form-body {
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .field-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .field-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .field-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--color-text-secondary);
    }

    .field-input,
    .field-select,
    .field-textarea {
        width: 100%;
        padding: 8px 11px;
        font-size: 12.5px;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        background: var(--color-surface-subtle);
        color: var(--color-text-primary);
        outline: none;
    }
    .field-textarea {
        resize: vertical;
        min-height: 76px;
        line-height: 1.5;
    }
    .field-input:focus,
    .field-select:focus,
    .field-textarea:focus {
        border-color: var(--color-teal);
        background: var(--color-surface);
    }

    .manage-row {
        padding: 14px 20px;
        border-bottom: 1px solid var(--color-border-subtle);
        display: grid;
        grid-template-columns: 36px 1fr auto auto;
        align-items: center;
        gap: 16px;
    }
    .manage-row:last-child {
        border-bottom: none;
    }
    .manage-row:hover {
        background: var(--color-surface-subtle);
    }
    @media (max-width: 768px) {
        .manage-row {
            grid-template-columns: 32px 1fr;
        }
    }

    .rank-circle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--color-surface-pill);
        border: 1px solid var(--color-border);
        color: var(--color-text-secondary);
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .trend-select-inline {
        padding: 4px 8px;
        font-size: 12px;
        border-radius: var(--radius-xs);
        border: 1px solid var(--color-border);
        background: var(--color-surface-subtle);
        color: var(--color-text-primary);
        cursor: pointer;
    }

    .btn-danger-text {
        background: transparent;
        border: none;
        color: var(--color-red-dark);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        padding: 4px 8px;
    }
    .btn-danger-text:hover {
        text-decoration: underline;
    }

    .alert-banner {
        background: var(--color-teal-bg);
        border: 1px solid var(--color-teal-border);
        color: var(--color-teal-dark);
        padding: 10px 16px;
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 500;
    }

    /* Metodologi */
    .doc-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 24px 28px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .doc-section-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--color-text-primary);
        margin-bottom: 6px;
    }

    .doc-text {
        font-size: 13.5px;
        line-height: 1.65;
        color: var(--color-text-secondary);
    }

    .pipeline-steps {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }
    @media (max-width: 960px) {
        .pipeline-steps {
            grid-template-columns: 1fr;
        }
    }

    .step-item {
        background: var(--color-surface-subtle);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .step-num {
        font-size: 11px;
        font-weight: 700;
        color: var(--color-teal-dark);
    }

    .step-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--color-text-primary);
    }
</style>
@endsection

@section('content')
    <header class="page-title-bar">
        <h1 class="page-heading">
            <span>Pengaturan Sistem</span>
            <span class="page-heading-muted">&middot; JupyterHub, Kelola Isu &amp; Metodologi</span>
        </h1>
        <div class="top-right-actions">
            <a href="{{ route('dashboard') }}" class="btn-clean">&larr; Kembali ke Dashboard</a>
        </div>
    </header>

    @if(session('status'))
        <div class="alert-banner">
            {{ session('status') }}
        </div>
    @endif

    <div class="settings-tabs" role="tablist" aria-label="Menu Pengaturan SOSPEM">
        <button type="button"
            class="settings-tab-btn {{ $activeTab === 'screening' ? 'is-active' : '' }}"
            data-tab="screening"
            onclick="switchSettingsTab('screening')">
            Screening JupyterHub &amp; AI
        </button>
        <button type="button"
            class="settings-tab-btn {{ $activeTab === 'issues' ? 'is-active' : '' }}"
            data-tab="issues"
            onclick="switchSettingsTab('issues')">
            Kelola Isu &amp; Uji Data
        </button>
        <button type="button"
            class="settings-tab-btn {{ $activeTab === 'methodology' ? 'is-active' : '' }}"
            data-tab="methodology"
            onclick="switchSettingsTab('methodology')">
            Metodologi &amp; UU PDP
        </button>
    </div>

    <!-- ================================================================
         TAB 1: SCREENING KONEKSI JUPYTERHUB & OTAK AI
         ================================================================ -->
    <section id="tab-screening" class="settings-panel {{ $activeTab === 'screening' ? 'is-active' : '' }}">
        <div class="screening-hero-card">
            <div class="screening-hero-left">
                <span class="status-dot-ring is-{{ $jupyterStatus['overall_state'] }}" id="screenOverallDot"></span>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <h2 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--color-text-primary);">
                            Status Koneksi JupyterHub (Otak AI &amp; Scraper)
                        </h2>
                        <span class="screening-badge is-{{ $jupyterStatus['overall_state'] }}" id="screenOverallBadge">
                            {{ $jupyterStatus['overall_label'] }}
                        </span>
                    </div>
                    <p id="screenOverallDesc" style="margin: 6px 0 0; font-size: 13px; color: var(--color-text-secondary); line-height: 1.55;">
                        {{ $jupyterStatus['overall_message'] }}
                    </p>
                    <div class="tabular" id="screenCheckedAt" style="margin-top: 6px; font-size: 11.5px; color: var(--color-text-muted);">
                        Terakhir diperiksa: {{ $jupyterStatus['checked_at'] }}
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" id="btnScreenJupyter" class="btn-clean btn-primary" onclick="runJupyterScreening(true)" style="padding: 8px 16px;">
                    Screening Koneksi Sekarang
                </button>
            </div>
        </div>

        <div class="screening-grid">
            <!-- Kartu 1: Scraper Listener JupyterHub -->
            <div class="diag-card">
                <div class="diag-head">
                    <span class="diag-title">1. Listener Scraper JupyterHub</span>
                    <span class="screening-badge is-{{ $jupyterStatus['listener']['state'] }}" id="diagListenerBadge">
                        {{ $jupyterStatus['listener']['label'] }}
                    </span>
                </div>
                <div class="diag-meta-list tabular">
                    <div class="diag-row">
                        <span class="diag-key">Polling / Aktivitas Terakhir</span>
                        <span class="diag-val" id="diagListenerLastSeen">{{ $jupyterStatus['listener']['last_seen_label'] }}</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Sumber Sinyal</span>
                        <span class="diag-val" id="diagListenerSource">{{ $jupyterStatus['listener']['last_source_label'] }}</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Alamat IP Worker</span>
                        <span class="diag-val" id="diagListenerIp">{{ $jupyterStatus['listener']['last_ip'] }}</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Catatan Diagnosa</span>
                        <span class="diag-val" id="diagListenerNote">{{ $jupyterStatus['listener']['note'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Kartu 2: Pipeline Ingest AI Qwen -->
            <div class="diag-card">
                <div class="diag-head">
                    <span class="diag-title">2. Pipeline Ingest Data AI Qwen</span>
                    <span class="screening-badge is-{{ $jupyterStatus['ingest']['state'] }}" id="diagIngestBadge">
                        {{ $jupyterStatus['ingest']['label'] }}
                    </span>
                </div>
                <div class="diag-meta-list tabular">
                    <div class="diag-row">
                        <span class="diag-key">Total Postingan Teranalisis</span>
                        <span class="diag-val" id="diagIngestTotal">{{ number_format($jupyterStatus['ingest']['total_posts'], 0, ',', '.') }} post</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Akun Terpantau (Bot &amp; Warga)</span>
                        <span class="diag-val" id="diagIngestAccounts">{{ number_format($jupyterStatus['ingest']['accounts_count'], 0, ',', '.') }} akun</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Data Terakhir Masuk</span>
                        <span class="diag-val" id="diagIngestLastPost">{{ $jupyterStatus['ingest']['last_post_at'] }}</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Kesiapan Endpoint</span>
                        <span class="diag-val">/api/ingest &amp; /api/ingest-batch</span>
                    </div>
                </div>
            </div>

            <!-- Kartu 3: Otak AI & Gateway LLM -->
            <div class="diag-card">
                <div class="diag-head">
                    <span class="diag-title">3. Otak AI Chatbot &amp; Gateway LLM</span>
                    <span class="screening-badge is-{{ $jupyterStatus['ai_engine']['state'] }}" id="diagAiBadge">
                        {{ $jupyterStatus['ai_engine']['label'] }}
                    </span>
                </div>
                <div class="diag-meta-list tabular">
                    <div class="diag-row">
                        <span class="diag-key">URL Agent Lokal / Ollama</span>
                        <span class="diag-val" id="diagAiLocalUrl">{{ $jupyterStatus['ai_engine']['local_agent_url'] }}</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Status Agent Lokal</span>
                        <span class="diag-val" id="diagAiLocalStatus">{{ $jupyterStatus['ai_engine']['local_agent_status'] }}</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Gateway Cloud Key (Groq/OpenAI/Gemini)</span>
                        <span class="diag-val" id="diagAiCloudKeys">{{ $jupyterStatus['ai_engine']['cloud_keys_summary'] }}</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Gateway AI Gratis (Pollinations)</span>
                        <span class="diag-val" id="diagAiPollinations">{{ $jupyterStatus['ai_engine']['pollinations_status'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Kartu 4: Antrean Tugas Scraping (scrape_jobs) -->
            <div class="diag-card">
                <div class="diag-head">
                    <span class="diag-title">4. Antrean Tugas Scrape Live</span>
                    <span class="screening-badge is-{{ $jupyterStatus['jobs']['state'] }}" id="diagJobsBadge">
                        {{ $jupyterStatus['jobs']['label'] }}
                    </span>
                </div>
                <div class="diag-meta-list tabular">
                    <div class="diag-row">
                        <span class="diag-key">Antrean Menunggu (Pending)</span>
                        <span class="diag-val" id="diagJobsPending">{{ $jupyterStatus['jobs']['pending'] }}</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Sedang Berjalan (Running)</span>
                        <span class="diag-val" id="diagJobsRunning">{{ $jupyterStatus['jobs']['running'] }}</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Selesai / Gagal</span>
                        <span class="diag-val" id="diagJobsDone">{{ $jupyterStatus['jobs']['completed'] }} selesai &middot; {{ $jupyterStatus['jobs']['failed'] }} gagal</span>
                    </div>
                    <div class="diag-row">
                        <span class="diag-key">Tugas Terbaru</span>
                        <span class="diag-val" id="diagJobsLatest">{{ $jupyterStatus['jobs']['latest_label'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Referensi Endpoint JupyterHub -->
        <div class="manage-card">
            <div class="manage-card-head">
                <span>Endpoint Integrasi Server SOSPEM &harr; JupyterHub</span>
                <span style="font-size: 12px; color: var(--color-text-muted); font-weight: 400;">Base URL: {{ url('/') }}</span>
            </div>
            <div style="overflow-x: auto;">
                <table class="endpoint-table">
                    <thead>
                        <tr>
                            <th>Fungsi</th>
                            <th>Method &amp; Endpoint</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Ambil Antrean Scrape</td>
                            <td><code>GET {{ url('/api/scrape-jobs/pending') }}</code></td>
                            <td>Dipanggil berkala oleh worker JupyterHub (otomatis memperbarui status Terhubung di layar ini).</td>
                        </tr>
                        <tr>
                            <td>Kirim Hasil Tunggal</td>
                            <td><code>POST {{ url('/api/ingest') }}</code></td>
                            <td>Mengirim 1 komentar + hasil analisis sentimen &amp; skor bot AI Qwen ke PostgreSQL.</td>
                        </tr>
                        <tr>
                            <td>Kirim Hasil Batch</td>
                            <td><code>POST {{ url('/api/ingest-batch') }}</code></td>
                            <td>Mengirim banyak komentar sekaligus tanpa duplikasi (<code>author_handle + content</code>).</td>
                        </tr>
                        <tr>
                            <td>Selesaikan Tugas Scrape</td>
                            <td><code>POST {{ url('/api/scrape-jobs/{id}/done') }}</code></td>
                            <td>Melaporkan jumlah komentar masuk dan menandai job selesai.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ================================================================
         TAB 2: KELOLA ISU & UJI POSTINGAN
         ================================================================ -->
    <section id="tab-issues" class="settings-panel {{ $activeTab === 'issues' ? 'is-active' : '' }}">
        <div class="issues-manage-grid">
            <div class="left-forms-stack">
                <!-- Form 1: Tambah Isu Baru ke PostgreSQL -->
                <aside class="manage-card">
                    <div class="manage-card-head">
                        <span>1. Tambah Isu Pantauan Baru</span>
                    </div>
                    <form action="{{ route('issues.store') }}" method="POST" class="manage-form-body">
                        @csrf
                        <input type="hidden" name="redirect_to" value="settings">
                        <div class="field-group">
                            <label for="title" class="field-label">Judul Isu / Topik Kebijakan</label>
                            <input type="text" id="title" name="title" class="field-input" placeholder="Contoh: Subsidi KRL Jabodetabek" required>
                        </div>

                        <div class="field-group">
                            <label for="keywords" class="field-label">Kata Kunci Pencarian (pisahkan koma)</label>
                            <input type="text" id="keywords" name="keywords" class="field-input" placeholder="krl, tarif krl, commuter line">
                        </div>

                        <div class="field-group">
                            <label for="trend" class="field-label">Status Tren Awal</label>
                            <select id="trend" name="trend" class="field-select">
                                <option value="up">▲ Naik</option>
                                <option value="stable" selected>━ Stabil</option>
                                <option value="down">▼ Turun</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-clean btn-primary" style="justify-content: center; margin-top: 2px; padding: 8px 14px;">
                            + Simpan Isu ke PostgreSQL
                        </button>
                    </form>
                </aside>

                <!-- Form 2: Uji & Input Postingan Baru -->
                <aside class="manage-card">
                    <div class="manage-card-head">
                        <span>2. Uji Input Postingan (Deteksi Otomatis)</span>
                    </div>
                    <form action="{{ route('posts.store') }}" method="POST" class="manage-form-body">
                        @csrf
                        <input type="hidden" name="redirect_to" value="settings">
                        <div class="field-group">
                            <label for="issue_id" class="field-label">Pilih Isu</label>
                            <select id="issue_id" name="issue_id" class="field-select" required>
                                @foreach($issues as $iss)
                                    <option value="{{ $iss->id }}">#{{ str_pad((string) $iss->rank, 2, '0', STR_PAD_LEFT) }} {{ $iss->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-group">
                            <label for="platform" class="field-label">Platform Media Sosial</label>
                            <select id="platform" name="platform" class="field-select" required>
                                <option value="x">X &amp; Threads</option>
                                <option value="meta">Meta (IG &amp; FB)</option>
                                <option value="tiktok">TikTok &amp; YouTube</option>
                            </select>
                        </div>

                        <div class="field-row-2">
                            <div class="field-group">
                                <label for="author_name" class="field-label">Nama Akun</label>
                                <input type="text" id="author_name" name="author_name" class="field-input" placeholder="Andi Pratama" required>
                            </div>
                            <div class="field-group">
                                <label for="author_handle" class="field-label">Username (@handle)</label>
                                <input type="text" id="author_handle" name="author_handle" class="field-input" placeholder="@andi_jkt atau @bot_99" required>
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="content" class="field-label">Isi Teks Postingan</label>
                            <textarea id="content" name="content" class="field-textarea" placeholder="Ketik teks opini warga atau teks terindikasi bot..." required></textarea>
                        </div>

                        <button type="submit" class="btn-clean btn-primary" style="justify-content: center; margin-top: 2px; padding: 8px 14px;">
                            Analisis &amp; Simpan Postingan
                        </button>
                    </form>
                </aside>

                <!-- Form 3: Sinkronisasi / Upload File stream_analisis_sospem.json -->
                <aside class="manage-card">
                    <div class="manage-card-head">
                        <span>3. Sinkronisasi JSON AI Qwen (JupyterHub)</span>
                    </div>
                    <form action="{{ route('issues.sync-json') }}" method="POST" enctype="multipart/form-data" class="manage-form-body">
                        @csrf
                        <input type="hidden" name="redirect_to" value="settings">
                        <div class="field-group">
                            <label for="json_file" class="field-label">Upload File stream_analisis_sospem.json (Opsional)</label>
                            <input type="file" id="json_file" name="json_file" accept=".json,application/json" class="field-input">
                        </div>
                        <div style="font-size: 11.5px; color: var(--color-text-muted); line-height: 1.45;">
                            Jika file tidak dipilih, sistem otomatis membaca <code>stream_analisis_sospem.json</code> di direktori utama proyek dan menyinkronkan komentar baru tanpa duplikasi.
                        </div>
                        <button type="submit" class="btn-clean btn-primary" style="justify-content: center; margin-top: 2px; padding: 8px 14px;">
                            Sinkronkan Data AI Qwen Sekarang
                        </button>
                    </form>
                </aside>
            </div>

            <!-- Kolom Kanan: Daftar Isu Aktif -->
            <main class="manage-card">
                <div class="manage-card-head">
                    <span>Daftar Isu Terpantau ({{ $issues->count() }} Isu)</span>
                </div>

                @foreach($issues as $issue)
                    <div class="manage-row">
                        <div class="rank-circle tabular">{{ str_pad((string) $issue->rank, 2, '0', STR_PAD_LEFT) }}</div>

                        <div>
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <a href="{{ route('feed.platform', ['issueSlug' => $issue->slug, 'platform' => 'x']) }}"
                                   style="font-size: 13.5px; font-weight: 600; color: var(--color-text-primary); text-decoration: none;">
                                    {{ $issue->title }}
                                </a>
                                <span class="tabular" style="font-size: 12px; color: var(--color-text-secondary);">
                                    {{ number_format($issue->total_posts, 0, ',', '.') }} post
                                    &middot; Orang {{ number_format($issue->human_pct, 1) }}% / Bot {{ number_format($issue->bot_pct, 1) }}%
                                </span>
                            </div>

                            @if(!empty($issue->keywords))
                                <div style="font-size: 12px; color: var(--color-text-muted); margin-top: 2px;">
                                    Kata kunci: {{ implode(', ', $issue->keywords) }}
                                </div>
                            @endif
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if($issue->trend === 'up')
                                <span class="status-pill is-up">▲ Naik</span>
                            @elseif($issue->trend === 'down')
                                <span class="status-pill is-down">▼ Turun</span>
                            @else
                                <span class="status-pill is-stable">━ Stabil</span>
                            @endif

                            <form action="{{ route('issues.update-trend', $issue) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="redirect_to" value="settings">
                                <select name="trend" class="trend-select-inline" onchange="this.form.submit()" title="Ubah status tren">
                                    <option value="up" {{ $issue->trend === 'up' ? 'selected' : '' }}>Naik</option>
                                    <option value="stable" {{ $issue->trend === 'stable' ? 'selected' : '' }}>Stabil</option>
                                    <option value="down" {{ $issue->trend === 'down' ? 'selected' : '' }}>Turun</option>
                                </select>
                            </form>
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <form action="{{ route('issues.destroy', $issue) }}" method="POST" onsubmit="return confirm('Hapus isu {{ $issue->title }} dari pantauan?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="redirect_to" value="settings">
                                <button type="submit" class="btn-danger-text">Hapus</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </main>
        </div>
    </section>

    <!-- ================================================================
         TAB 3: METODOLOGI & KEPATUHAN UU PDP
         ================================================================ -->
    <section id="tab-methodology" class="settings-panel {{ $activeTab === 'methodology' ? 'is-active' : '' }}">
        <main class="doc-card">
            <div>
                <h2 class="doc-section-title">1. Alur Pemrosesan Data Real-Time</h2>
                <p class="doc-text">Setiap postingan media sosial yang masuk diproses secara berurutan sebelum ditampilkan pada matriks dashboard:</p>
            </div>

            <div class="pipeline-steps">
                <div class="step-item">
                    <span class="step-num tabular">TAHAP 01</span>
                    <span class="step-title">Pengumpul Data</span>
                    <span style="font-size: 12px; color: var(--color-text-muted);">Scraper YouTube/RSS &amp; API publik via JupyterHub</span>
                </div>
                <div class="step-item">
                    <span class="step-num tabular">TAHAP 02</span>
                    <span class="step-title">Deteksi Bot</span>
                    <span style="font-size: 12px; color: var(--color-text-muted);">Skor heuristik usia akun, kemiripan teks &amp; jeda waktu</span>
                </div>
                <div class="step-item">
                    <span class="step-num tabular">TAHAP 03</span>
                    <span class="step-title">Sentimen AI Qwen</span>
                    <span style="font-size: 12px; color: var(--color-text-muted);">Klasifikasi bahasa Indonesia (Positif / Negatif / Lembaga)</span>
                </div>
                <div class="step-item">
                    <span class="step-num tabular">TAHAP 04</span>
                    <span class="step-title">Agregasi Database</span>
                    <span style="font-size: 12px; color: var(--color-text-muted);">Kalkulasi rasio Orang vs Bot per isu &amp; platform</span>
                </div>
                <div class="step-item">
                    <span class="step-num tabular">TAHAP 05</span>
                    <span class="step-title">Live Stream</span>
                    <span style="font-size: 12px; color: var(--color-text-muted);">Pembaruan langsung ke layar analis setiap 8 detik</span>
                </div>
            </div>

            <div style="border-top: 1px solid var(--color-border-subtle); padding-top: 16px;">
                <h2 class="doc-section-title">2. Parameter Heuristik Deteksi Bot / Buzzer</h2>
                <p class="doc-text">
                    Sistem memisahkan opini warga organik dari mobilisasi bot menggunakan 4 indikator terukur:
                    <strong>(a) Kemiripan Leksikal (Min-Hash)</strong> untuk mendeteksi salin-tempel narasi seragam,
                    <strong>(b) Sinkronisitas Waktu</strong> untuk mendeteksi pengiriman serentak dalam hitungan detik,
                    <strong>(c) Rasio Follower/Following</strong> pada akun berusia di bawah 30 hari, dan
                    <strong>(d) Frekuensi Posting Ekstrem</strong> di luar batas wajar manusia.
                </p>
            </div>

            <div style="border-top: 1px solid var(--color-border-subtle); padding-top: 16px;">
                <h2 class="doc-section-title">3. Kepatuhan UU Pelindungan Data Pribadi (UU No. 27 Tahun 2022)</h2>
                <p class="doc-text">
                    SOSPEM dirancang hanya untuk membaca postingan berstatus publik terbuka dan menghitung agregasi statistik opini kebijakan. Sistem tidak melakukan penyadapan pesan privat, tidak menyimpan data kependudukan (NIK/alamat), serta berfokus pada audit transparansi manipulasi algoritma/botnet.
                </p>
            </div>
        </main>
    </section>
@endsection

@section('scripts')
<script>
    function switchSettingsTab(tabKey) {
        document.querySelectorAll('.settings-tab-btn').forEach(btn => {
            btn.classList.toggle('is-active', btn.getAttribute('data-tab') === tabKey);
        });
        document.querySelectorAll('.settings-panel').forEach(panel => {
            panel.classList.toggle('is-active', panel.id === 'tab-' + tabKey);
        });

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {
            // Abaikan bila browser tidak mendukung replaceState
        }
    }

    function applyBadgeState(el, state, text) {
        if (!el) return;
        el.classList.remove('is-connected', 'is-standby', 'is-disconnected');
        el.classList.add('is-' + state);
        if (typeof text === 'string') {
            el.innerText = text;
        }
    }

    async function runJupyterScreening(isManualProbe = false) {
        const btn = document.getElementById('btnScreenJupyter');
        if (btn && isManualProbe) {
            btn.disabled = true;
            btn.innerText = 'Memeriksa koneksi...';
        }

        try {
            const url = "{{ route('api.jupyter.status') }}" + (isManualProbe ? '?probe=1' : '');
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            const data = await res.json();
            if (data.status !== 'ok' || !data.screening) {
                throw new Error('Format screening tidak valid');
            }

            const s = data.screening;

            const dot = document.getElementById('screenOverallDot');
            if (dot) {
                dot.classList.remove('is-connected', 'is-standby', 'is-disconnected');
                dot.classList.add('is-' + s.overall_state);
            }
            applyBadgeState(document.getElementById('screenOverallBadge'), s.overall_state, s.overall_label);
            document.getElementById('screenOverallDesc').innerText = s.overall_message;
            document.getElementById('screenCheckedAt').innerText = 'Terakhir diperiksa: ' + s.checked_at;

            applyBadgeState(document.getElementById('diagListenerBadge'), s.listener.state, s.listener.label);
            document.getElementById('diagListenerLastSeen').innerText = s.listener.last_seen_label;
            document.getElementById('diagListenerSource').innerText = s.listener.last_source_label;
            document.getElementById('diagListenerIp').innerText = s.listener.last_ip;
            document.getElementById('diagListenerNote').innerText = s.listener.note;

            applyBadgeState(document.getElementById('diagIngestBadge'), s.ingest.state, s.ingest.label);
            document.getElementById('diagIngestTotal').innerText = Number(s.ingest.total_posts).toLocaleString('id-ID') + ' post';
            document.getElementById('diagIngestAccounts').innerText = Number(s.ingest.accounts_count).toLocaleString('id-ID') + ' akun';
            document.getElementById('diagIngestLastPost').innerText = s.ingest.last_post_at;

            applyBadgeState(document.getElementById('diagAiBadge'), s.ai_engine.state, s.ai_engine.label);
            document.getElementById('diagAiLocalUrl').innerText = s.ai_engine.local_agent_url;
            document.getElementById('diagAiLocalStatus').innerText = s.ai_engine.local_agent_status;
            document.getElementById('diagAiCloudKeys').innerText = s.ai_engine.cloud_keys_summary;
            document.getElementById('diagAiPollinations').innerText = s.ai_engine.pollinations_status;

            applyBadgeState(document.getElementById('diagJobsBadge'), s.jobs.state, s.jobs.label);
            document.getElementById('diagJobsPending').innerText = s.jobs.pending;
            document.getElementById('diagJobsRunning').innerText = s.jobs.running;
            document.getElementById('diagJobsDone').innerText = `${s.jobs.completed} selesai · ${s.jobs.failed} gagal`;
            document.getElementById('diagJobsLatest').innerText = s.jobs.latest_label;

            if (isManualProbe && typeof showToast === 'function') {
                showToast('Screening selesai: ' + s.overall_label);
            }
        } catch (err) {
            if (isManualProbe && typeof showToast === 'function') {
                showToast('Gagal melakukan screening koneksi');
            }
        } finally {
            if (btn && isManualProbe) {
                btn.disabled = false;
                btn.innerText = 'Screening Koneksi Sekarang';
            }
        }
    }
</script>
@endsection

