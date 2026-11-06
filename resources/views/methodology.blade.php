@extends('layouts.app')

@section('title', 'Metodologi & Kepatuhan UU PDP — SOSPEM')

@section('styles')
<style>
    .doc-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 26px 30px;
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
            <span>Metodologi &amp; Kepatuhan Regulasi</span>
            <span class="page-heading-muted">&middot; Arsitektur SOSPEM</span>
        </h1>
        <div class="top-right-actions">
            <a href="{{ route('dashboard') }}" class="btn-clean">&larr; Dashboard</a>
        </div>
    </header>

    <main class="doc-card">
        <div>
            <h2 class="doc-section-title">1. Alur Pemrosesan Data Real-Time</h2>
            <p class="doc-text">Setiap postingan media sosial yang masuk diproses secara berurutan sebelum ditampilkan pada matriks dashboard:</p>
        </div>

        <div class="pipeline-steps">
            <div class="step-item">
                <span class="step-num tabular">TAHAP 01</span>
                <span class="step-title">Pengumpul Data</span>
                <span style="font-size: 12px; color: var(--color-text-muted);">API resmi &amp; crawler publik Meta, X, TikTok</span>
            </div>
            <div class="step-item">
                <span class="step-num tabular">TAHAP 02</span>
                <span class="step-title">Deteksi Bot</span>
                <span style="font-size: 12px; color: var(--color-text-muted);">Skor heuristik usia akun, kemiripan teks &amp; jeda waktu</span>
            </div>
            <div class="step-item">
                <span class="step-num tabular">TAHAP 03</span>
                <span class="step-title">Sentimen IndoBERT</span>
                <span style="font-size: 12px; color: var(--color-text-muted);">Klasifikasi bahasa Indonesia (Positif / Negatif)</span>
            </div>
            <div class="step-item">
                <span class="step-num tabular">TAHAP 04</span>
                <span class="step-title">Agregasi Database</span>
                <span style="font-size: 12px; color: var(--color-text-muted);">Kalkulasi rasio Orang vs Bot per isu &amp; platform</span>
            </div>
            <div class="step-item">
                <span class="step-num tabular">TAHAP 05</span>
                <span class="step-title">Live Stream</span>
                <span style="font-size: 12px; color: var(--color-text-muted);">Pembaruan langsung ke layar analis via WebSocket</span>
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
@endsection

