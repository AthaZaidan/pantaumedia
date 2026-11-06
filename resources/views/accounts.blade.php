@extends('layouts.app')

@section('title', 'Audit Profil & Jaringan Akun — SOSPEM')

@section('styles')
<style>
    .accounts-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 18px;
        align-items: start;
    }

    @media (max-width: 960px) {
        .accounts-layout {
            grid-template-columns: 1fr;
        }
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
</style>
@endsection

@section('content')
    <header class="page-title-bar">
        <h1 class="page-heading">
            <span>Audit Profil &amp; Jaringan Akun</span>
            <span class="page-heading-muted">&middot; Forensik Heuristik Bot</span>
        </h1>
        <div class="top-right-actions">
            <a href="{{ route('dashboard') }}" class="btn-clean">&larr; Dashboard</a>
        </div>
    </header>

    <div class="accounts-layout">
        <!-- Daftar Akun Terpantau -->
        <aside class="account-selector-card">
            <div class="selector-head">Sampel Akun Terpantau</div>
            @foreach($accountsList as $handleKey => $acc)
                <a href="{{ route('accounts', ['handle' => $handleKey]) }}"
                   class="account-nav-item {{ $profile['handle'] === $handleKey ? 'is-selected' : '' }}">
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

        <!-- Rincian Profil Akun -->
        <main class="profile-detail-card">
            <div style="background: var(--color-surface-subtle); border-left: 3px solid var(--color-teal); padding: 10px 14px; font-size: 12.5px; color: var(--color-text-secondary); border-radius: var(--radius-xs);">
                <strong>Kepatuhan UU PDP No. 27/2022:</strong> Sistem hanya memantau metadata publik terbuka untuk mengidentifikasi pola koordinasi otomatis (botnet), tanpa mengumpulkan data pribadi warga negara.
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid var(--color-border); padding-bottom: 16px;">
                <div>
                    <h2 style="font-size: 20px; font-weight: 700;">{{ $profile['name'] }}</h2>
                    <div class="tabular" style="font-size: 13px; color: var(--color-text-secondary);">{{ $profile['handle'] }} &middot; {{ $profile['platform'] }}</div>
                </div>

                @if($profile['is_bot'])
                    <span class="tabular" style="font-size: 13.5px; font-weight: 600; color: var(--color-red-dark);">Skor Kemungkinan Bot: {{ $profile['bot_score'] }}%</span>
                @else
                    <span class="tabular" style="font-size: 13.5px; font-weight: 600; color: var(--color-teal-dark);">Warga Organik (Skor Bot: {{ $profile['bot_score'] }}%)</span>
                @endif
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
        </main>
    </div>
@endsection

