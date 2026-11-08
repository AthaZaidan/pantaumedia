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
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
    @media (max-width: 860px) {
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
            <button type="button" class="btn-clean btn-primary" onclick="simulateIngest()">+ Simulasi Stream</button>
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
            <article class="issue-item {{ $index === 0 ? 'is-lead is-expanded' : '' }}" data-title="{{ strtolower($issue['title'] . ' ' . $issue['subtitle']) }}">
                <div class="issue-row-header" onclick="toggleExpand(this)">
                    <div class="issue-avatar tabular">{{ $issue['rank'] }}</div>
                    <div class="issue-primary">
                        <div class="issue-name">{{ $issue['title'] }}</div>
                    </div>

                    <div class="bar-group-box hide-on-tablet">
                        <div class="bar-labels">
                            <span>Orang {{ number_format($issue['human_pct'], 1) }}%</span>
                            <span>Bot {{ number_format($issue['bot_pct'], 1) }}%</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill-teal" style="width: {{ $issue['human_pct'] }}%;"></div>
                            <div class="bar-fill-slate" style="width: {{ $issue['bot_pct'] }}%;"></div>
                        </div>
                    </div>

                    <div class="bar-group-box hide-on-tablet">
                        <div class="bar-labels">
                            <span>+{{ number_format($issue['pos_pct'], 1) }}% Pos</span>
                            <span>{{ number_format($issue['neg_pct'], 1) }}% Neg</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill-teal" style="width: {{ $issue['pos_pct'] }}%;"></div>
                            <div class="bar-fill-slate" style="width: {{ $issue['neg_pct'] }}%;"></div>
                        </div>
                    </div>

                    <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">
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
                document.getElementById('kpiTotalSampel').innerText = data.kpi.total_posts.toLocaleString('id-ID');
                document.getElementById('kpiHumanPosts').innerText = data.kpi.human_posts.toLocaleString('id-ID');
                document.getElementById('kpiBotPosts').innerText = data.kpi.bot_posts.toLocaleString('id-ID');
                document.getElementById('kpiHumanPct').innerText = data.kpi.human_pct.toFixed(1).replace('.', ',') + '%';
                document.getElementById('kpiBotPct').innerText = data.kpi.bot_pct.toFixed(1).replace('.', ',') + '%';
                showToast("Tersimpan & dihitung ulang di PostgreSQL (ID #" + data.post.id + ")");
            }
        } catch (e) {
            showToast("Gagal menyimpan stream");
        }
    }

    function filterIssues() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const items = document.querySelectorAll('#issuesList .issue-item');
        items.forEach(item => {
            const title = item.getAttribute('data-title') || '';
            item.style.display = title.includes(q) ? '' : 'none';
        });
    }
</script>
@endsection

