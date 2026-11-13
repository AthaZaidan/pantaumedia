@extends('layouts.app')

@section('title', 'Audit Profil & Jaringan Akun — SOSPEM')

@section('styles')
<style>
    .accounts-layout {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 18px;
        align-items: start;
    }

    @media (max-width: 960px) {
        .accounts-layout {
            grid-template-columns: 1fr;
        }
    }

    .left-accounts-stack {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .account-selector-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .selector-head {
        padding: 14px 18px;
        border-bottom: 1px solid var(--color-border);
        font-size: 13px;
        font-weight: 600;
        color: var(--color-text-primary);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .account-nav-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        border-bottom: 1px solid var(--color-border-subtle);
        text-decoration: none;
        color: var(--color-text-primary);
        transition: background 0.12s;
    }
    .account-nav-item:last-child {
        border-bottom: none;
    }
    .account-nav-item:hover {
        background: var(--color-surface-subtle);
    }
    .account-nav-item.is-selected {
        background: var(--color-surface-hover);
        font-weight: 600;
    }

    .profile-detail-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 24px 28px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .profile-meta-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }
    @media (max-width: 768px) {
        .profile-meta-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .meta-stat-box {
        background: var(--color-surface-subtle);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .meta-stat-label {
        font-size: 11.5px;
        color: var(--color-text-secondary);
    }

    .meta-stat-val {
        font-size: 16px;
        font-weight: 700;
        color: var(--color-text-primary);
    }

    .audit-form-body {
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .audit-input,
    .audit-select {
        width: 100%;
        padding: 7px 10px;
        font-size: 12.5px;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        background: var(--color-surface-subtle);
        color: var(--color-text-primary);
        outline: none;
    }
    .audit-input:focus,
    .audit-select:focus {
        border-color: var(--color-teal);
        background: var(--color-surface);
    }

    .audit-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .history-post-item {
        background: var(--color-surface-subtle);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        gap: 6px;
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
            <span>Audit Profil &amp; Jaringan Akun</span>
            <span class="page-heading-muted">&middot; Forensik Heuristik Bot (PostgreSQL)</span>
        </h1>
        <div class="top-right-actions">
            <a href="{{ route('dashboard') }}" class="btn-clean">&larr; Dashboard</a>
        </div>
    </header>

    @if(session('status'))
        <div class="alert-banner">
            {{ session('status') }}
        </div>
    @endif

    <div class="accounts-layout">
        <!-- Kolom Kiri: Daftar Akun Terpantau + Form Audit Akun Baru -->
        <div class="left-accounts-stack">
            <aside class="account-selector-card">
                <div class="selector-head">
                    <span>Akun Terpantau ({{ count($accountsList) }})</span>
                </div>
                @foreach($accountsList as $handleKey => $acc)
                    <a href="{{ route('accounts', ['handle' => $handleKey]) }}"
                       class="account-nav-item {{ $profile && $profile['handle'] === $handleKey ? 'is-selected' : '' }}">
                        <div>
                            <div style="font-size: 13px;">{{ $acc['name'] }}</div>
                            <div class="tabular" style="font-size: 11.5px; color: var(--color-text-muted);">{{ $acc['handle'] }}</div>
                        </div>
                        @if($acc['is_bot'])
                            <span class="tabular" style="font-size: 12px; font-weight: 600; color: var(--color-red-dark);">{{ $acc['bot_score'] }}% Bot</span>
                        @else
                            <span class="tabular" style="font-size: 12px; font-weight: 600; color: var(--color-teal-dark);">Orang</span>
                        @endif
                    </a>
                @endforeach
            </aside>

            <!-- Form Audit Akun Baru ke PostgreSQL -->
            <aside class="account-selector-card">
                <div class="selector-head">
                    <span>Audit / Tambah Akun Baru</span>
                </div>
                <form action="{{ route('accounts.store') }}" method="POST" class="audit-form-body">
                    @csrf
                    <div>
                        <label style="font-size: 11.5px; color: var(--color-text-secondary);">Nama Tampilan</label>
                        <input type="text" name="name" class="audit-input" placeholder="Contoh: Suara Nusantara 99" required>
                    </div>
                    <div class="audit-row-2">
                        <div>
                            <label style="font-size: 11.5px; color: var(--color-text-secondary);">Username (@handle)</label>
                            <input type="text" name="handle" class="audit-input" placeholder="@nusantara_99" required>
                        </div>
                        <div>
                            <label style="font-size: 11.5px; color: var(--color-text-secondary);">Platform</label>
                            <select name="platform" class="audit-select">
                                <option value="X (Twitter)">X (Twitter)</option>
                                <option value="Meta (IG & FB)">Meta (IG &amp; FB)</option>
                                <option value="TikTok & YouTube">TikTok &amp; YouTube</option>
                            </select>
                        </div>
                    </div>
                    <div class="audit-row-2">
                        <div>
                            <label style="font-size: 11.5px; color: var(--color-text-secondary);">Follower</label>
                            <input type="number" name="followers" class="audit-input" value="12" min="0" required>
                        </div>
                        <div>
                            <label style="font-size: 11.5px; color: var(--color-text-secondary);">Following</label>
                            <input type="number" name="following" class="audit-input" value="1450" min="0" required>
                        </div>
                    </div>
                    <div class="audit-row-2">
                        <div>
                            <label style="font-size: 11.5px; color: var(--color-text-secondary);">Usia Akun</label>
                            <input type="text" name="age" class="audit-input" value="14 hari" required>
                        </div>
                        <div>
                            <label style="font-size: 11.5px; color: var(--color-text-secondary);">Frekuensi Post</label>
                            <input type="text" name="posting_frequency" class="audit-input" value="120 post / hari" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-clean btn-primary" style="justify-content: center; margin-top: 4px; padding: 8px 12px;">
                        + Hitung Skor &amp; Simpan Akun
                    </button>
                </form>
            </aside>
        </div>

        <!-- Kolom Kanan: Rincian Profil Akun & Riwayat Postingan PostgreSQL -->
        @if($profile)
            <main class="profile-detail-card">
                <div style="background: var(--color-surface-subtle); border-left: 3px solid var(--color-teal); padding: 10px 14px; font-size: 12.5px; color: var(--color-text-secondary); border-radius: var(--radius-xs);">
                    <strong>Kepatuhan UU PDP No. 27/2022:</strong> Sistem hanya memantau metadata publik terbuka untuk mengidentifikasi pola koordinasi otomatis (botnet), tanpa mengumpulkan data pribadi warga negara.
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid var(--color-border); padding-bottom: 16px;">
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700;">{{ $profile['name'] }}</h2>
                        <div class="tabular" style="font-size: 13px; color: var(--color-text-secondary);">{{ $profile['handle'] }} &middot; {{ $profile['platform'] }}</div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 14px;">
                        @if($profile['is_bot'])
                            <span class="tabular" style="font-size: 13.5px; font-weight: 600; color: var(--color-red-dark);">Skor Kemungkinan Bot: {{ $profile['bot_score'] }}%</span>
                        @else
                            <span class="tabular" style="font-size: 13.5px; font-weight: 600; color: var(--color-teal-dark);">Warga Organik (Skor Bot: {{ $profile['bot_score'] }}%)</span>
                        @endif

                        @if(isset($profile['id']))
                            <form action="{{ route('accounts.destroy', $profile['id']) }}" method="POST" onsubmit="return confirm('Hapus akun {{ $profile['handle'] }} dari daftar audit?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-clean" style="color: var(--color-red-dark); padding: 4px 10px; font-size: 12px;">Hapus Akun</button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- 4 Info Dasar Akun -->
                <div class="profile-meta-grid">
                    <div class="meta-stat-box">
                        <span class="meta-stat-label">Usia Akun</span>
                        <span class="meta-stat-val tabular">{{ $profile['age'] }}</span>
                    </div>
                    <div class="meta-stat-box">
                        <span class="meta-stat-label">Pengikut (Follower)</span>
                        <span class="meta-stat-val tabular">{{ number_format($profile['followers'], 0, ',', '.') }}</span>
                    </div>
                    <div class="meta-stat-box">
                        <span class="meta-stat-label">Mengikuti (Following)</span>
                        <span class="meta-stat-val tabular">{{ number_format($profile['following'], 0, ',', '.') }}</span>
                    </div>
                    <div class="meta-stat-box">
                        <span class="meta-stat-label">Frekuensi Posting</span>
                        <span class="meta-stat-val" style="font-size: 13px;">{{ $profile['freq'] }}</span>
                    </div>
                </div>

                <!-- Alasan Skor Bot / Heuristik -->
                <div>
                    <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Analisis Perilaku &amp; Indikator Heuristik:</h3>
                    <ul style="margin-left: 18px; display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: var(--color-text-secondary);">
                        @foreach($profile['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>

                <!-- Topik yang Sering Dibahas -->
                <div>
                    <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Topik Isu yang Sering Dibahas:</h3>
                    <div style="display: flex; gap: 14px; flex-wrap: wrap; font-size: 13px; font-weight: 500; color: var(--color-text-secondary);">
                        @foreach($profile['topics'] as $topic)
                            <span>&bull; {{ $topic }}</span>
                        @endforeach
                    </div>
                </div>

                <!-- Akun Serupa dalam Jaringan -->
                @if(count($profile['cluster']) > 0)
                    <div style="border-top: 1px solid var(--color-border-subtle); padding-top: 16px;">
                        <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Akun Lain dalam Kluster Koordinasi Serupa:</h3>
                        <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                            @foreach($profile['cluster'] as $peer)
                                <span class="tabular" style="font-size: 13px; font-weight: 600; color: var(--color-red-dark);">{{ $peer }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Riwayat Postingan Akun dari Tabel social_posts PostgreSQL -->
                <div style="border-top: 1px solid var(--color-border-subtle); padding-top: 16px; display: flex; flex-direction: column; gap: 10px;">
                    <h3 style="font-size: 14px; font-weight: 600;">
                        Riwayat Postingan Akun Ini di PostgreSQL ({{ $accountPosts->count() }} sampel terbaru)
                    </h3>

                    @forelse($accountPosts as $ap)
                        <div class="history-post-item">
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--color-text-secondary);">
                                <span>
                                    Isu:
                                    @if($ap->issue)
                                        <a href="{{ route('feed.platform', ['issueSlug' => $ap->issue->slug, 'platform' => $ap->platform]) }}" style="color: var(--color-text-primary); font-weight: 600;">
                                            {{ $ap->issue->title }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                    &middot; Platform: {{ strtoupper($ap->platform) }}
                                </span>
                                <span class="tabular">
                                    @if($ap->sentiment === 'positive')
                                        <span style="color: var(--color-teal-dark); font-weight: 600;">+ Positif ({{ $ap->sentiment_conf }}%)</span>
                                    @else
                                        <span style="color: var(--color-red-dark); font-weight: 600;">- Negatif ({{ $ap->sentiment_conf }}%)</span>
                                    @endif
                                </span>
                            </div>
                            <div style="font-size: 13px; color: var(--color-text-primary);">
                                {{ $ap->content }}
                            </div>
                        </div>
                    @empty
                        <div style="font-size: 12.5px; color: var(--color-text-muted);">
                            Belum ada riwayat postingan untuk akun ini di tabel social_posts.
                        </div>
                    @endforelse
                </div>
            </main>
        @else
            <main class="profile-detail-card">
                <div style="padding: 24px; text-align: center; color: var(--color-text-secondary); font-size: 13.5px;">
                    Belum ada akun terpantau di database. Akun akan otomatis tercatat saat AI Agent Qwen mengirim data melalui <code>/api/ingest</code> atau <code>/api/ingest-batch</code>, atau Anda dapat mengaudit akun secara manual melalui formulir di sebelah kiri.
                </div>
            </main>
        @endif
    </div>
@endsection
