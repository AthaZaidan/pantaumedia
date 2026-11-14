@extends('layouts.app')

@section('title', 'Dashboard Utama — SOSPEM')

@section('styles')
<style>
    /* ==========================================================================
       1. ASYMMETRIC BENTO GRID (1 Primary Besar di Kiri + 3 Stack di Kanan)
       ========================================================================== */
    .kpi-bento-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
        gap: 14px;
        align-items: stretch;
        width: 100%;
    }
    @media (max-width: 760px) {
        .kpi-bento-grid {
            grid-template-columns: 1fr;
        }
    }

    .kpi-primary-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 26px 28px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 22px;
    }

    .kpi-primary-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 24px;
    }

    .kpi-primary-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--color-text-secondary);
        margin-bottom: 6px;
    }

    .kpi-primary-hero-number {
        font-size: 42px;
        font-weight: 700;
        color: var(--color-text-primary);
        letter-spacing: -0.03em;
        line-height: 1.05;
    }

    .kpi-primary-delta {
        font-size: 13px;
        color: var(--color-text-secondary);
        margin-top: 10px;
    }

    .kpi-primary-spark {
        padding-top: 10px;
        flex-shrink: 0;
    }

    .kpi-primary-divider {
        height: 1px;
        background: var(--color-border);
        width: 100%;
    }

    .kpi-primary-split {
        display: flex;
        align-items: center;
        gap: 48px;
        flex-wrap: wrap;
    }

    .kpi-split-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .kpi-split-label {
        font-size: 12.5px;
        font-weight: 500;
        color: var(--color-text-secondary);
    }

    .kpi-split-val {
        font-size: 20px;
        font-weight: 700;
        color: var(--color-text-primary);
        letter-spacing: -0.015em;
    }

    .kpi-secondary-stack {
        display: flex;
        flex-direction: column;
        gap: 12px;
        justify-content: space-between;
    }

    .kpi-compact-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        padding: 16px 20px;
        box-shadow: var(--shadow-card);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex: 1;
    }

    .kpi-compact-left {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
    }

    .kpi-compact-label {
        font-size: 12.5px;
        font-weight: 500;
        color: var(--color-text-secondary);
    }

    .kpi-compact-val {
        font-size: 22px;
        font-weight: 700;
        color: var(--color-text-primary);
        letter-spacing: -0.02em;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .kpi-compact-delta {
        font-size: 13px;
        font-weight: 500;
        color: var(--color-text-secondary);
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* ==========================================================================
       2. GRAFIK HARIAN RINGKAS
       ========================================================================== */
    .daily-chart-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 18px 24px;
        box-shadow: var(--shadow-card);
    }

    .daily-chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .daily-chart-title {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--color-text-primary);
    }

    .period-tabs {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .period-tab {
        font-size: 12px;
        font-weight: 500;
        padding: 3px 9px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: var(--color-text-secondary);
        cursor: pointer;
    }
    .period-tab.is-active {
        background: var(--color-surface-pill);
        color: var(--color-text-primary);
        font-weight: 600;
    }

    .daily-svg-wrap {
        width: 100%;
        overflow: hidden;
    }

    .daily-axis-labels {
        display: flex;
        justify-content: space-between;
        font-size: 11.5px;
        color: var(--color-text-muted);
        margin-top: 6px;
    }

    /* ==========================================================================
       3. DAFTAR 10 ISU TERPANTAU & PLATFORM CARDS (LINK KE SPLIT SCREEN)
       ========================================================================== */
    .issues-table-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .issues-card-topbar {
        padding: 16px 24px;
        border-bottom: 1px solid var(--color-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .issues-card-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--color-text-primary);
    }

    .search-clean-wrap {
        position: relative;
        width: 260px;
    }
    .search-clean-input {
        width: 100%;
        padding: 6px 12px 6px 30px;
        font-size: 12.5px;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        background: var(--color-surface-subtle);
        color: var(--color-text-primary);
        outline: none;
    }
    .search-clean-input:focus {
        background: var(--color-surface);
        border-color: var(--color-text-primary);
    }
    .search-clean-icon {
        position: absolute;
        left: 9px;
        top: 8px;
        color: var(--color-text-muted);
        pointer-events: none;
    }

    .issue-item {
        border-bottom: 1px solid var(--color-border-subtle);
        transition: background-color 0.12s ease;
    }
    .issue-item:last-child {
        border-bottom: none;
    }
    .issue-item:hover {
        background-color: var(--color-surface-subtle);
    }

    .issue-row-header {
        padding: 14px 24px;
        display: grid;
        grid-template-columns: 36px minmax(220px, 1.5fr) 150px 180px 110px 90px;
        align-items: center;
        gap: 16px;
        cursor: pointer;
        user-select: none;
    }
    @media (max-width: 1024px) {
        .issue-row-header {
            grid-template-columns: 34px 1fr auto;
        }
        .hide-on-tablet {
            display: none !important;
        }
    }

    .issue-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: var(--color-surface-pill);
        border: 1px solid var(--color-border);
        color: var(--color-text-secondary);
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .issue-item.is-lead .issue-avatar {
        background-color: var(--color-teal);
        border-color: var(--color-teal);
        color: #090b10;
    }

    .issue-primary {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }
    .issue-name {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--color-text-primary);
    }
    .issue-meta {
        font-size: 12px;
        color: var(--color-text-muted);
    }

    .bar-group-box {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .bar-labels {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: var(--color-text-secondary);
        font-weight: 500;
    }
    .bar-track {
        height: 5px;
        border-radius: 9999px;
        background: var(--color-slate-bar);
        overflow: hidden;
        display: flex;
        width: 100%;
    }
    .bar-fill-teal { background: var(--color-teal); }
    .bar-fill-slate { background: var(--color-slate-dark); }

    /* Expansion Panel Breakdown Platform */
    .issue-expansion-panel {
        display: none;
        border-top: 1px solid var(--color-border-subtle);
        background: var(--color-surface-subtle);
        padding: 16px 24px 20px 76px;
    }
    @media (max-width: 768px) {
        .issue-expansion-panel {
            padding: 14px 16px;
        }
    }
    .issue-item.is-expanded .issue-expansion-panel {
        display: block;
    }

    .platform-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }
    @media (max-width: 1200px) {
        .platform-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .platform-grid { grid-template-columns: 1fr; }
    }

    /* Card Sosmed Klikable Mengarah ke Halaman Split-Screen */
    .platform-card-link {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        text-decoration: none;
        color: inherit;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.12s ease;
    }
    .platform-card-link:hover {
        border-color: var(--color-text-primary);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        transform: translateY(-1px);
    }

    .platform-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--color-text-primary);
        border-bottom: 1px solid var(--color-border-subtle);
        padding-bottom: 6px;
    }

    .segment-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 3px 0;
    }

    .segment-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--color-text-secondary);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .dot-teal { width: 6px; height: 6px; border-radius: 50%; background: var(--color-teal); }
    .dot-slate { width: 6px; height: 6px; border-radius: 50%; background: var(--color-slate-dark); }

    .segment-metric {
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .platform-cta-hint {
        font-size: 11px;
        font-weight: 600;
        color: var(--color-text-secondary);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 6px;
        border-top: 1px dashed var(--color-border);
    }

    /* Modal Dialog Scrape Live */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(2px);
        z-index: 1200;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .modal-overlay.is-active {
        display: flex;
    }
    .modal-box {
        background: var(--color-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--color-border);
        width: 100%;
        max-width: 520px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .modal-header {
        padding: 16px 20px;
        background: var(--color-surface);
        border-bottom: 1px solid var(--color-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>
@endsection

@section('content')
    <!-- JUDUL ATAS MINIMALIS -->
    <header class="page-title-bar">
        <h1 class="page-heading">
            <span>Opini Publik</span>
            <span class="page-heading-muted">&middot; 1 Nov s/d 6 Nov 2026</span>
        </h1>

        <div class="top-right-actions">
            <a href="{{ route('issues.index') }}" class="btn-clean">+ Kelola Isu</a>
            <a href="{{ route('analytics') }}" class="btn-clean">Grafik Detail</a>
            <a href="{{ route('methodology') }}" class="btn-clean">UU PDP</a>
            <button type="button" class="btn-clean" onclick="openScrapeModal()" style="background: #2563eb; border-color: #2563eb; color: #ffffff; font-weight: 600;">⚡ + Scrape Live</button>
            <button type="button" class="btn-clean btn-primary" onclick="simulateIngest()" title="Hanya simulasi dummy stream di browser">+ Simulasi Stream</button>
        </div>
    </header>

    <!-- 1. ASYMMETRIC BENTO GRID: 1 KARTU PRIMARY BESAR + 3 STACK DI KANAN -->
    <section class="kpi-bento-grid">

        <!-- Kartu Primary Besar (Kiri) -->
        <div class="kpi-primary-card">
            <div class="kpi-primary-top">
                <div>
                    <div class="kpi-primary-label">Total Sampel Terpantau (PostgreSQL)</div>
                    <div class="kpi-primary-hero-number tabular" id="kpiTotalSampel">{{ number_format($kpi['total_posts'], 0, ',', '.') }}</div>
                    <div class="kpi-primary-delta tabular" style="color: var(--color-teal-dark); font-weight: 500;">&#8599; Terhubung langsung ke tabel social_posts</div>
                </div>

                <!-- Sparkline Hijau Stockbit dengan Area Fill Halus -->
                <div class="kpi-primary-spark">
                    <svg width="165" height="56" viewBox="0 0 165 56" fill="none">
                        <defs>
                            <linearGradient id="sparkGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#18b26f" stop-opacity="0.28"/>
                                <stop offset="100%" stop-color="#18b26f" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>
                        <path d="M4 44 C 18 38, 22 46, 36 36 C 48 26, 54 37, 68 28 C 80 20, 88 31, 102 20 C 116 10, 124 22, 138 14 C 146 9, 152 12, 158 6 L 158 54 L 4 54 Z"
                              fill="url(#sparkGrad)"/>
                        <path d="M4 44 C 18 38, 22 46, 36 36 C 48 26, 54 37, 68 28 C 80 20, 88 31, 102 20 C 116 10, 124 22, 138 14 C 146 9, 152 12, 158 6"
                              stroke="#21ce83" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <circle cx="158" cy="6" r="3.5" fill="#21ce83" />
                    </svg>
                </div>
            </div>

            <div class="kpi-primary-divider"></div>

            <!-- 2 Kolom Bawah -->
            <div class="kpi-primary-split">
                <div class="kpi-split-item">
                    <div class="kpi-split-label">Warga Organik</div>
                    <div class="kpi-split-val tabular" id="kpiHumanPosts">{{ number_format($kpi['human_posts'], 0, ',', '.') }}</div>
                </div>

                <div class="kpi-split-item">
                    <div class="kpi-split-label">Jaringan Bot</div>
                    <div class="kpi-split-val tabular" id="kpiBotPosts">{{ number_format($kpi['bot_posts'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <!-- 3 Kartu Stack di Kanan -->
        <div class="kpi-secondary-stack">
            <div class="kpi-compact-card">
                <div class="kpi-compact-left">
                    <span class="kpi-compact-label">Warga aktif organik</span>
                    <span class="kpi-compact-val tabular" id="kpiHumanPct">{{ number_format($kpi['human_pct'], 1, ',', '.') }}%</span>
                </div>
                <div class="kpi-compact-delta tabular" style="color: var(--color-teal-dark);">&#8599; Organik</div>
            </div>

            <div class="kpi-compact-card">
                <div class="kpi-compact-left">
                    <span class="kpi-compact-label">Anomali botnet</span>
                    <span class="kpi-compact-val tabular" id="kpiBotPct">{{ number_format($kpi['bot_pct'], 1, ',', '.') }}%</span>
                </div>
                <div class="kpi-compact-delta tabular" style="color: var(--color-red-dark);">&#8600; Botnet</div>
            </div>

            <div class="kpi-compact-card">
                <div class="kpi-compact-left">
                    <span class="kpi-compact-label">Isu tertinggi (#1)</span>
                    <span class="kpi-compact-val" id="kpiTopTitle" style="font-size: 18px;">{{ $kpi['top_issue_title'] }}</span>
                </div>
                <div class="kpi-compact-delta tabular" id="kpiTopShare" style="color: var(--color-teal-dark); font-weight: 600;">{{ number_format($kpi['top_issue_share'], 1, ',', '.') }}% porsi</div>
            </div>
        </div>
    </section>

    <!-- 2. GRAFIK VOLUME HARIAN (DINAMIS DARI POSTGRESQL: 24j, 7h, 30h) -->
    <section class="daily-chart-card">
        <div class="daily-chart-header">
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <span class="daily-chart-title">Tren volume percakapan harian</span>
                <span class="tabular" id="periodSummaryMeta" style="font-size: 12px; color: var(--color-text-secondary);">
                    {{ number_format($periodTrends['7h']['total'], 0, ',', '.') }} post (Orang: {{ number_format($periodTrends['7h']['human'], 0, ',', '.') }} &middot; Bot: {{ number_format($periodTrends['7h']['bot'], 0, ',', '.') }})
                </span>
            </div>
            <div class="period-tabs">
                <button type="button" class="period-tab" data-period="24j" onclick="switchPeriod(this, '24j')">24j</button>
                <button type="button" class="period-tab is-active" data-period="7h" onclick="switchPeriod(this, '7h')">7h</button>
                <button type="button" class="period-tab" data-period="30h" onclick="switchPeriod(this, '30h')">30h</button>
            </div>
        </div>

        <div class="daily-svg-wrap">
            <svg width="100%" height="96" viewBox="0 0 1000 96" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="stockbitChartGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#18b26f" stop-opacity="0.22"/>
                        <stop offset="100%" stop-color="#18b26f" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>
                <line x1="0" y1="20" x2="1000" y2="20" stroke="var(--color-border-subtle)" stroke-width="1"/>
                <line x1="0" y1="50" x2="1000" y2="50" stroke="var(--color-border-subtle)" stroke-width="1"/>
                <line x1="0" y1="80" x2="1000" y2="80" stroke="var(--color-border-subtle)" stroke-width="1"/>
                <path id="dailyAreaPath" d="{{ $periodTrends['7h']['area_path'] }}"
                      fill="url(#stockbitChartGrad)"/>
                <path id="dailyLinePath" d="{{ $periodTrends['7h']['line_path'] }}"
                      fill="none" stroke="var(--color-teal)" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>

        <div class="daily-axis-labels tabular" id="dailyAxisLabels">
            @foreach($periodTrends['7h']['axis_labels'] as $lbl)
                <span>{{ $lbl }}</span>
            @endforeach
        </div>
    </section>

    <!-- 3. DAFTAR 10 ISU TERPANTAU -->
    <main class="issues-table-card" id="issuesList">
        <div class="issues-card-topbar">
            <span class="issues-card-title">Isu terpantau</span>
            <div class="search-clean-wrap">
                <svg class="search-clean-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="searchInput" class="search-clean-input" placeholder="Cari isu..." oninput="filterIssues()">
            </div>
        </div>

        @foreach($issues as $index => $issue)
            <article class="issue-item {{ $index === 0 ? 'is-lead is-expanded' : '' }}" data-issue-slug="{{ $issue['slug'] }}" data-title="{{ strtolower($issue['title'] . ' ' . $issue['subtitle']) }}">
                <div class="issue-row-header" onclick="toggleExpand(this)">
                    <div class="issue-avatar tabular js-issue-rank">{{ $issue['rank'] }}</div>
                    <div class="issue-primary">
                        <div class="issue-name">{{ $issue['title'] }}</div>
                    </div>

                    <div class="bar-group-box hide-on-tablet">
                        <div class="bar-labels">
                            <span class="js-human-pct">Orang {{ number_format($issue['human_pct'], 1) }}%</span>
                            <span class="js-bot-pct">Bot {{ number_format($issue['bot_pct'], 1) }}%</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill-teal js-human-bar" style="width: {{ $issue['human_pct'] }}%;"></div>
                            <div class="bar-fill-slate js-bot-bar" style="width: {{ $issue['bot_pct'] }}%;"></div>
                        </div>
                    </div>

                    <div class="bar-group-box hide-on-tablet">
                        <div class="bar-labels">
                            <span class="js-pos-pct">+{{ number_format($issue['pos_pct'], 1) }}% Pos</span>
                            <span class="js-neg-pct">{{ number_format($issue['neg_pct'], 1) }}% Neg</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill-teal js-pos-bar" style="width: {{ $issue['pos_pct'] }}%;"></div>
                            <div class="bar-fill-slate js-neg-bar" style="width: {{ $issue['neg_pct'] }}%;"></div>
                        </div>
                    </div>

                    <div class="tabular js-total-posts" style="font-weight: 600; font-size: 13.5px; text-align: right;">
                        {{ number_format($issue['total_posts'], 0, ',', '.') }} post
                    </div>

                    <!-- Badge Status Tren: Hanya Naik (Hijau), Stabil (Abu-abu), Turun (Merah) -->
                    <div style="display: flex; justify-content: flex-end;">
                        @if($issue['trend'] === 'up')
                            <span class="status-pill is-up">▲ Naik</span>
                        @elseif($issue['trend'] === 'down')
                            <span class="status-pill is-down">▼ Turun</span>
                        @else
                            <span class="status-pill is-stable">━ Stabil</span>
                        @endif
                    </div>
                </div>

                <!-- Expansion Panel: Semua Card Sosmed Mengarah ke Halaman Baru Split-Screen (Kiri Orang vs Kanan Bot) -->
                <div class="issue-expansion-panel">
                    <div class="platform-grid">
                        @foreach($issue['platforms'] as $platKey => $plat)
                            @if($plat['sufficient'])
                                <a href="{{ route('feed.platform', ['issueSlug' => $issue['slug'], 'platform' => $platKey]) }}" class="platform-card-link">
                                    <div class="platform-header">
                                        <span>{{ $plat['name'] }}</span>
                                        <span class="tabular" style="color: var(--color-text-muted); font-size: 11.5px;">{{ $plat['volume'] }}</span>
                                    </div>

                                    <div class="segment-row">
                                        <span class="segment-label"><span class="dot-teal"></span>Orang (Warga)</span>
                                        <span class="segment-metric tabular">
                                            <span style="color: var(--color-teal-dark);">+{{ number_format($plat['human_pos'], 1) }}%</span>
                                            <span style="color: var(--color-text-muted);">/</span>
                                            <span style="color: var(--color-red-dark);">-{{ number_format($plat['human_neg'], 1) }}%</span>
                                        </span>
                                    </div>

                                    <div class="segment-row">
                                        <span class="segment-label"><span class="dot-slate"></span>Jaringan Bot</span>
                                        <span class="segment-metric tabular">
                                            <span style="color: var(--color-teal-dark);">+{{ number_format($plat['bot_pos'], 1) }}%</span>
                                            <span style="color: var(--color-text-muted);">/</span>
                                            <span style="color: var(--color-red-dark);">-{{ number_format($plat['bot_neg'], 1) }}%</span>
                                        </span>
                                    </div>

                                    <div class="platform-cta-hint">
                                        <span>Buka Split Feed (Orang vs Bot)</span>
                                        <span>&rarr;</span>
                                    </div>
                                </a>
                            @else
                                <div class="platform-card-link" style="opacity: 0.7; cursor: default;">
                                    <div class="platform-header">
                                        <span>{{ $plat['name'] }}</span>
                                        <span class="tabular" style="color: var(--color-text-muted); font-size: 11.5px;">{{ $plat['volume'] }}</span>
                                    </div>
                                    <div style="font-size: 12px; color: var(--color-text-muted); padding: 12px 0;">
                                        Belum cukup data untuk platform ini (&lt;45 post)
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </article>
        @endforeach
    </main>

    <!-- MODAL SCRAPE LIVE JUPYTERHUB -->
    <div class="modal-overlay" id="modalScrapeLive" onclick="handleScrapeBackdrop(event)">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h3 style="font-size: 15px; font-weight: 600; color: var(--color-text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span style="color: #2563eb;">⚡</span> Perintahkan Scraping Live
                    </h3>
                    <p style="font-size: 11.5px; color: var(--color-text-secondary); margin: 3px 0 0;">
                        Memerintahkan AI Agent & Scraper di JupyterHub menarik data asli YouTube/RSS.
                    </p>
                </div>
                <button type="button" class="btn-clean" onclick="closeScrapeModal()" style="padding: 4px 8px; font-size: 13px;">✕</button>
            </div>
            <div class="modal-body" style="padding: 18px 20px; display: flex; flex-direction: column; gap: 14px;">
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="scrapeTopic" style="font-size: 12.5px; font-weight: 600; color: var(--color-text-primary);">
                        Tema / Kata Kunci Isu
                    </label>
                    <input type="text" id="scrapeTopic" placeholder="Contoh: Makan Bergizi Gratis, IKN, Kenaikan PPN" 
                        style="width: 100%; border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 8px 12px; font-size: 13px; background: var(--color-surface); color: var(--color-text-primary); box-sizing: border-box;" />
                    <div style="font-size: 11px; color: var(--color-text-secondary);">
                        Kata kunci ini akan langsung diteruskan ke scraper YouTube & RSS di JupyterHub.
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="scrapeLimit" style="font-size: 12.5px; font-weight: 600; color: var(--color-text-primary);">
                        Jumlah Maksimal Postingan (1 - 50)
                    </label>
                    <input type="number" id="scrapeLimit" value="10" min="1" max="50" 
                        style="width: 120px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 8px 12px; font-size: 13px; background: var(--color-surface); color: var(--color-text-primary); box-sizing: border-box;" />
                </div>

                <!-- Status Polling Box -->
                <div id="scrapeStatusBox" style="display: none; padding: 12px 14px; border-radius: var(--radius-sm); background: var(--color-surface-hover); border: 1px solid var(--color-border); font-size: 12px;">
                    <div id="scrapeStatusText" style="font-weight: 600; color: var(--color-text-primary);"></div>
                    <div id="scrapeStatusDetail" style="font-size: 11.5px; color: var(--color-text-secondary); margin-top: 4px; line-height: 1.4;"></div>
                </div>

                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; margin-top: 6px; padding-top: 10px; border-top: 1px solid var(--color-border);">
                    <button type="button" class="btn-clean" onclick="closeScrapeModal()">Tutup</button>
                    <button type="button" id="btnSubmitScrape" class="btn-clean" onclick="submitScrapeJob()" style="background: #2563eb; border-color: #2563eb; color: #ffffff; font-weight: 600;">
                        🚀 Mulai Scraping Live
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    const periodTrendsData = @json($periodTrends);

    function toggleExpand(rowElement) {
        const card = rowElement.closest('.issue-item');
        card.classList.toggle('is-expanded');
    }

    function switchPeriod(btn, periodKey) {
        btn.parentElement.querySelectorAll('.period-tab').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');

        const data = periodTrendsData[periodKey];
        if (!data) {
            return;
        }

        document.getElementById('dailyAreaPath').setAttribute('d', data.area_path);
        document.getElementById('dailyLinePath').setAttribute('d', data.line_path);

        const labelsWrap = document.getElementById('dailyAxisLabels');
        labelsWrap.innerHTML = data.axis_labels.map(lbl => `<span>${lbl}</span>`).join('');

        document.getElementById('periodSummaryMeta').innerHTML =
            `${data.total.toLocaleString('id-ID')} post (Orang: ${data.human.toLocaleString('id-ID')} &middot; Bot: ${data.bot.toLocaleString('id-ID')})`;
    }

    async function simulateIngest() {
        try {
            const res = await fetch("{{ route('ingest.post') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    issue_slug: 'makan-bergizi-gratis',
                    platform: 'x',
                    author_name: 'Warga Pemantau (Live)',
                    author_handle: '@warga_pantau_id',
                    content: 'Evaluasi lapangan pelaksanaan Makan Bergizi Gratis hari ini berjalan lancar, mohon kualitas distribusi tetap diawasi.'
                })
            });
            const data = await res.json();
            if (data.status === 'ok' && data.kpi) {
                applyKpiAndIssuesUpdate(data.kpi, data.issue ? [data.issue] : []);
                showToast("Tersimpan & dihitung ulang di PostgreSQL (ID #" + data.post.id + ")");
            }
        } catch (e) {
            showToast("Gagal menyimpan stream");
        }
    }

    function applyKpiAndIssuesUpdate(kpi, issuesList) {
        if (kpi) {
            document.getElementById('kpiTotalSampel').innerText = Number(kpi.total_posts).toLocaleString('id-ID');
            document.getElementById('kpiHumanPosts').innerText = Number(kpi.human_posts).toLocaleString('id-ID');
            document.getElementById('kpiBotPosts').innerText = Number(kpi.bot_posts).toLocaleString('id-ID');
            document.getElementById('kpiHumanPct').innerText = Number(kpi.human_pct).toFixed(1).replace('.', ',') + '%';
            document.getElementById('kpiBotPct').innerText = Number(kpi.bot_pct).toFixed(1).replace('.', ',') + '%';
            if (kpi.top_issue_title) {
                document.getElementById('kpiTopTitle').innerText = kpi.top_issue_title;
            }
            if (typeof kpi.top_issue_share !== 'undefined') {
                document.getElementById('kpiTopShare').innerText = Number(kpi.top_issue_share).toFixed(1).replace('.', ',') + '% porsi';
            }
        }

        if (Array.isArray(issuesList)) {
            issuesList.forEach(iss => {
                const row = document.querySelector(`.issue-item[data-issue-slug="${iss.slug}"]`);
                if (!row) return;
                const totalEl = row.querySelector('.js-total-posts');
                if (totalEl) totalEl.innerText = Number(iss.total_posts).toLocaleString('id-ID') + ' post';

                const hPctEl = row.querySelector('.js-human-pct');
                const bPctEl = row.querySelector('.js-bot-pct');
                if (hPctEl) hPctEl.innerText = `Orang ${Number(iss.human_pct).toFixed(1)}%`;
                if (bPctEl) bPctEl.innerText = `Bot ${Number(iss.bot_pct).toFixed(1)}%`;

                const hBar = row.querySelector('.js-human-bar');
                const bBar = row.querySelector('.js-bot-bar');
                if (hBar) hBar.style.width = `${iss.human_pct}%`;
                if (bBar) bBar.style.width = `${iss.bot_pct}%`;

                const posPctEl = row.querySelector('.js-pos-pct');
                const negPctEl = row.querySelector('.js-neg-pct');
                if (posPctEl) posPctEl.innerText = `+${Number(iss.pos_pct).toFixed(1)}% Pos`;
                if (negPctEl) negPctEl.innerText = `${Number(iss.neg_pct).toFixed(1)}% Neg`;

                const posBar = row.querySelector('.js-pos-bar');
                const negBar = row.querySelector('.js-neg-bar');
                if (posBar) posBar.style.width = `${iss.pos_pct}%`;
                if (negBar) negBar.style.width = `${iss.neg_pct}%`;
            });
        }
    }

    // Live Polling setiap 8 detik agar KPI & volume isu otomatis bertambah saat AI Agent Qwen mengirim data
    async function pollDashboardLiveStats() {
        try {
            const res = await fetch("{{ route('api.live-stats') }}", { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (data.status === 'ok') {
                applyKpiAndIssuesUpdate(data.kpi, data.issues || []);
            }
        } catch (err) {
            // Abaikan gangguan jaringan sementara
        }
    }

    setInterval(pollDashboardLiveStats, 8000);

    function filterIssues() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const items = document.querySelectorAll('#issuesList .issue-item');
        items.forEach(item => {
            const title = item.getAttribute('data-title') || '';
            item.style.display = title.includes(q) ? '' : 'none';
        });
    }

    // ==========================================
    // SCRAPE LIVE (REVERSE POLLING DENGAN JUPYTERHUB)
    // ==========================================
    let currentScrapeJobId = null;
    let scrapePollInterval = null;

    function openScrapeModal() {
        document.getElementById('modalScrapeLive').classList.add('is-active');
        const input = document.getElementById('scrapeTopic');
        if (input) {
            setTimeout(() => input.focus(), 100);
        }
    }

    function closeScrapeModal() {
        if (scrapePollInterval) {
            clearInterval(scrapePollInterval);
            scrapePollInterval = null;
        }
        document.getElementById('modalScrapeLive').classList.remove('is-active');
    }

    function handleScrapeBackdrop(e) {
        if (e.target.id === 'modalScrapeLive') {
            closeScrapeModal();
        }
    }

    async function submitScrapeJob() {
        const topic = document.getElementById('scrapeTopic').value.trim();
        const limit = parseInt(document.getElementById('scrapeLimit').value, 10) || 10;
        const btn = document.getElementById('btnSubmitScrape');
        const statusBox = document.getElementById('scrapeStatusBox');
        const statusText = document.getElementById('scrapeStatusText');
        const statusDetail = document.getElementById('scrapeStatusDetail');

        if (!topic) {
            alert('Silakan masukkan tema atau kata kunci isu terlebih dahulu.');
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Menunggu JupyterHub...';
        statusBox.style.display = 'block';
        statusBox.style.borderColor = 'var(--color-border)';
        statusText.innerHTML = '📡 <strong>Menunggu JupyterHub...</strong>';
        statusDetail.innerText = 'Tugas scraping dibuat di database, menunggu listener JupyterHub mengambil tugas.';

        try {
            const res = await fetch("{{ route('api.scrape-jobs.create') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ topic, limit })
            });

            const data = await res.json();
            if (res.ok && data.status === 'ok') {
                currentScrapeJobId = data.job_id;
                btn.innerText = 'Menunggu JupyterHub...';
                statusText.innerHTML = `📡 <strong>Menunggu JupyterHub...</strong>`;
                statusDetail.innerText = `Antrean #${data.job_id} aktif. JupyterHub akan mengecek antrean setiap beberapa detik.`;
                if (typeof showToast === 'function') {
                    showToast(`Tugas Scrape #${data.job_id} (${topic}) berhasil dibuat.`);
                }

                // Polling status tugas ke GET /api/scrape-jobs/{id} setiap 3 detik
                startPollingJobStatus(currentScrapeJobId);
            } else {
                throw new Error(data.message || 'Gagal membuat tugas scraping');
            }
        } catch (err) {
            btn.disabled = false;
            btn.innerText = '🚀 Mulai Scraping Live';
            statusText.innerHTML = '❌ <strong>Gagal membuat tugas:</strong> ' + err.message;
            statusDetail.innerText = 'Pastikan server web Laravel aktif.';
        }
    }

    function startPollingJobStatus(jobId) {
        if (scrapePollInterval) {
            clearInterval(scrapePollInterval);
        }

        let attempts = 0;
        const maxAttempts = 100; // 100 * 3s = 300 detik (5 menit)
        const btn = document.getElementById('btnSubmitScrape');
        const statusBox = document.getElementById('scrapeStatusBox');
        const statusText = document.getElementById('scrapeStatusText');
        const statusDetail = document.getElementById('scrapeStatusDetail');

        scrapePollInterval = setInterval(async () => {
            attempts++;
            try {
                // Endpoint GET /api/scrape-jobs/{id}
                const res = await fetch(`/api/scrape-jobs/${jobId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;

                const resData = await res.json();
                const job = resData.job || resData;
                const status = job.status || resData.job_status;
                const count = (typeof job.scraped_count !== 'undefined') ? job.scraped_count : (resData.scraped_count || 0);

                if (!status) return;

                if (status === 'pending') {
                    btn.disabled = true;
                    btn.innerText = 'Menunggu JupyterHub...';
                    statusBox.style.borderColor = 'var(--color-border)';
                    statusText.innerHTML = '📡 <strong>Menunggu JupyterHub...</strong>';
                    statusDetail.innerText = 'JupyterHub sedang memeriksa antrean, scraping akan segera dimulai...';
                } else if (status === 'running') {
                    btn.disabled = true;
                    btn.innerText = 'JupyterHub sedang menarik & menganalisis data...';
                    statusBox.style.borderColor = '#2563eb';
                    statusText.innerHTML = '⚙️ <strong>JupyterHub sedang menarik & menganalisis data...</strong>';
                    statusDetail.innerText = `Menarik hingga ${job.limit || 10} postingan YouTube/RSS dan menjalankan analisis sentimen & bot AI Qwen.`;
                } else if (status === 'completed') {
                    clearInterval(scrapePollInterval);
                    scrapePollInterval = null;
                    btn.disabled = false;
                    btn.innerText = `Selesai! ${count} data masuk`;
                    statusBox.style.borderColor = 'var(--color-teal)';
                    statusText.innerHTML = `🎉 <strong>Selesai! ${count} data masuk</strong>`;
                    statusDetail.innerText = 'Data berhasil disimpan ke database. Menutup modal & memperbarui dashboard...';
                    if (typeof showToast === 'function') {
                        showToast(`Selesai! ${count} data berhasil masuk.`);
                    }

                    // Otomatis tutup modal dan refresh halaman dashboard
                    setTimeout(() => {
                        closeScrapeModal();
                        location.reload();
                    }, 1500);
                } else if (status === 'failed') {
                    clearInterval(scrapePollInterval);
                    scrapePollInterval = null;
                    btn.disabled = false;
                    btn.innerText = 'Ulangi Scrape';
                    statusBox.style.borderColor = '#dc2626';
                    statusText.innerHTML = '❌ <strong>Proses di JupyterHub Gagal</strong>';
                    statusDetail.innerText = 'Periksa log JupyterHub untuk melihat kendala jaringan atau batas kuota.';
                }

                if (attempts >= maxAttempts) {
                    clearInterval(scrapePollInterval);
                    scrapePollInterval = null;
                    btn.disabled = false;
                    btn.innerText = 'Muat Ulang Halaman';
                    statusText.innerHTML = '⏱️ <strong>Batas waktu pemantauan tercapai</strong>';
                    statusDetail.innerText = 'Proses mungkin masih berlangsung di background JupyterHub. Silakan klik muat ulang setelah beberapa saat.';
                    btn.onclick = () => location.reload();
                }
            } catch (e) {
                // Abaikan gangguan jaringan sementara
            }
        }, 3000); // Polling setiap 3 detik
    }
</script>
@endsection

