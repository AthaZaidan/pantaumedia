@extends('layouts.app')

@section('title', 'Kelola Isu & Uji Postingan — SOSPEM')

@section('styles')
<style>
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
</style>
@endsection

@section('content')
    <header class="page-title-bar">
        <h1 class="page-heading">
            <span>Kelola Isu &amp; Uji Postingan</span>
            <span class="page-heading-muted">&middot; Database PostgreSQL</span>
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

    <div class="issues-manage-grid">
        <!-- Kolom Kiri: Form Tambah Isu + Form Uji Deteksi Postingan -->
        <div class="left-forms-stack">
            <!-- Form 1: Tambah Isu Baru ke PostgreSQL -->
            <aside class="manage-card">
                <div class="manage-card-head">
                    <span>1. Tambah Isu Pantauan Baru</span>
                </div>
                <form action="{{ route('issues.store') }}" method="POST" class="manage-form-body">
                    @csrf
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

            <!-- Form 2: Uji & Input Postingan Baru (Deteksi Bot & Sentimen Otomatis) -->
            <aside class="manage-card">
                <div class="manage-card-head">
                    <span>2. Uji Input Postingan (Deteksi Otomatis)</span>
                </div>
                <form action="{{ route('posts.store') }}" method="POST" class="manage-form-body">
                    @csrf
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
                        <textarea id="content" name="content" class="field-textarea" placeholder="Ketik teks opini warga (misal: perlu evaluasi distribusi) atau teks bot (misal: dukung penuh sukses #IndonesiaMaju #KerjaNyata)..." required></textarea>
                    </div>

                    <button type="submit" class="btn-clean btn-primary" style="justify-content: center; margin-top: 2px; padding: 8px 14px;">
                        Analisis &amp; Simpan Postingan
                    </button>
                </form>
            </aside>
        </div>

        <!-- Kolom Kanan: Daftar Isu Aktif di PostgreSQL -->
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

                    <!-- Status Badge + Ubah Tren Cepat -->
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
                            <select name="trend" class="trend-select-inline" onchange="this.form.submit()" title="Ubah status tren">
                                <option value="up" {{ $issue->trend === 'up' ? 'selected' : '' }}>Naik</option>
                                <option value="stable" {{ $issue->trend === 'stable' ? 'selected' : '' }}>Stabil</option>
                                <option value="down" {{ $issue->trend === 'down' ? 'selected' : '' }}>Turun</option>
                            </select>
                        </form>
                    </div>

                    <!-- Tombol Hapus Isu -->
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <form action="{{ route('issues.destroy', $issue) }}" method="POST" onsubmit="return confirm('Hapus isu {{ $issue->title }} dari pantauan?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger-text">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </main>
    </div>
@endsection
