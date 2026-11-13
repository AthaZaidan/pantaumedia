@extends('layouts.app')

@section('title', 'Grafik Analitik & Monitoring Forensik — SOSPEM')

@section('styles')
<style>
    /* 1. Baris 4 Kartu KPI Analitik */
    .analytics-kpi-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
    }
    @media (max-width: 1024px) {
        .analytics-kpi-strip {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 600px) {
        .analytics-kpi-strip {
            grid-template-columns: 1fr;
        }
    }

    .analytic-kpi-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 16px 20px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .analytic-kpi-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--color-text-secondary);
    }

    .analytic-kpi-val {
        font-size: 24px;
        font-weight: 700;
        color: var(--color-text-primary);
        letter-spacing: -0.02em;
    }

    .analytic-kpi-meta {
        font-size: 12px;
        color: var(--color-text-secondary);
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 2px;
    }

    /* 2. Grid Grafik 2 Kolom & 3 Kolom */
    .charts-grid-2col {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 16px;
    }
    .charts-grid-3col {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    @media (max-width: 1080px) {
        .charts-grid-2col,
        .charts-grid-3col {
            grid-template-columns: 1fr;
        }
    }

    .chart-box-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 20px 24px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .chart-box-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--color-text-primary);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .filter-select-clean {
        padding: 6px 12px;
        font-size: 12.5px;
        font-weight: 500;
        border-radius: var(--radius-sm);
        border: 1px solid var(--color-border);
        background: var(--color-surface);
        color: var(--color-text-primary);
        cursor: pointer;
    }

    /* 3. Tabel Monitoring Kata Kunci & Top Akun Pemicu */
    .monitor-table-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .monitor-table-head {
        padding: 15px 20px;
        border-bottom: 1px solid var(--color-border);
        font-size: 13.5px;
        font-weight: 600;
        color: var(--color-text-primary);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .monitor-row {
        padding: 12px 20px;
        border-bottom: 1px solid var(--color-border-subtle);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .monitor-row:last-child {
        border-bottom: none;
    }
    .monitor-row:hover {
        background: var(--color-surface-subtle);
    }

    .mini-bar-track {
        width: 90px;
        height: 5px;
        border-radius: 999px;
        background: var(--color-slate-bar);
        overflow: hidden;
        display: flex;
    }
</style>
@endsection

@section('content')
    <header class="page-title-bar">
        <h1 class="page-heading">
            <span>Grafik Analitik &amp; Monitoring Forensik</span>
            <span class="page-heading-muted tabular">&middot; {{ number_format($chartData['total_analyzed'], 0, ',', '.') }} Postingan PostgreSQL</span>
        </h1>
        <div class="top-right-actions">
            <form action="{{ route('analytics') }}" method="GET" style="display: flex; align-items: center; gap: 8px;">
                <select name="issue" class="filter-select-clean" onchange="this.form.submit()">
                    <option value="all" {{ $selectedSlug === 'all' ? 'selected' : '' }}>Semua Isu ({{ number_format($chartData['total_analyzed'], 0, ',', '.') }} post)</option>
                    @foreach($issues as $iss)
                        <option value="{{ $iss['slug'] }}" {{ $selectedSlug === $iss['slug'] ? 'selected' : '' }}>
                            #{{ $iss['rank'] }} {{ $iss['title'] }} ({{ number_format($iss['total_posts'], 0, ',', '.') }} post)
                        </option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('dashboard') }}" class="btn-clean">&larr; Dashboard</a>
        </div>
    </header>

    <!-- 1. STRIP 4 KARTU KPI MONITORING ANALITIK -->
    <section class="analytics-kpi-strip">
        <div class="analytic-kpi-card">
            <span class="analytic-kpi-label">Total Postingan Dianalisis</span>
            <span class="analytic-kpi-val tabular">{{ number_format($chartData['total_analyzed'], 0, ',', '.') }}</span>
            <div class="analytic-kpi-meta tabular">
                <span style="color: var(--color-teal-dark); font-weight: 600;">Orang: {{ number_format($chartData['human_count'], 0, ',', '.') }}</span>
                <span>&middot;</span>
                <span style="color: var(--color-red-dark); font-weight: 600;">Bot: {{ number_format($chartData['bot_count'], 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="analytic-kpi-card">
            <span class="analytic-kpi-label">Rata-rata Skor Probabilitas Bot</span>
            <span class="analytic-kpi-val tabular">{{ number_format($chartData['avg_bot_score'], 1, ',', '.') }}%</span>
            <div class="analytic-kpi-meta">
                <span>Ambang batas botnet: &ge; 60,0%</span>
            </div>
        </div>

        <div class="analytic-kpi-card">
            <span class="analytic-kpi-label">Akurasi Klasifikasi Sentimen</span>
            <span class="analytic-kpi-val tabular" style="color: var(--color-teal-dark);">{{ number_format($chartData['avg_sentiment_conf'], 1, ',', '.') }}%</span>
            <div class="analytic-kpi-meta">
                <span>Keyakinan rata-rata IndoBERT</span>
            </div>
        </div>

        <div class="analytic-kpi-card">
            <span class="analytic-kpi-label">Total Interaksi (Likes &amp; Reposts)</span>
            <span class="analytic-kpi-val tabular">{{ number_format($chartData['total_engagement'], 0, ',', '.') }}</span>
            <div class="analytic-kpi-meta tabular">
                <span style="color: var(--color-teal-dark);">Warga: {{ number_format($chartData['human_engagement'], 0, ',', '.') }}</span>
                <span>&middot;</span>
                <span style="color: var(--color-red-dark);">Bot: {{ number_format($chartData['bot_engagement'], 0, ',', '.') }}</span>
            </div>
        </div>
    </section>

    <!-- 2. GRAFIK GARIS UTAMA: TREN VOLUME PER JAM (ORANG VS JARINGAN BOT) -->
    <section class="chart-box-card">
        <div class="chart-box-title">
            <span>Volume Postingan per Jam &amp; Titik Lonjakan ({{ $selectedIssue ? $selectedIssue['title'] : 'Seluruh Isu Terpantau' }})</span>
            @if($selectedIssue)
                @if($selectedIssue['trend'] === 'up')
                    <span class="status-pill is-up">▲ Naik</span>
                @elseif($selectedIssue['trend'] === 'down')
                    <span class="status-pill is-down">▼ Turun</span>
                @else
                    <span class="status-pill is-stable">━ Stabil</span>
                @endif
            @else
                <span class="status-pill is-up">▲ Naik</span>
            @endif
        </div>
        <div style="height: 290px;">
            <canvas id="chartHourlyVolume"></canvas>
        </div>
    </section>

    <!-- 3. BARIS 2 KOLOM: MATRIKS VOLUME 10 ISU & SPEKTRUM DISTRIBUSI SKOR BOT -->
    <div class="charts-grid-2col">
        <section class="chart-box-card">
            <div class="chart-box-title">
                <span>Komposisi Volume per Isu: Warga Organik vs Bot</span>
            </div>
            <div style="height: 310px;">
                <canvas id="chartIssueBreakdown"></canvas>
            </div>
        </section>

        <section class="chart-box-card">
            <div class="chart-box-title">
                <span>Distribusi Spektrum Skor Kemungkinan Bot</span>
            </div>
            <div style="height: 310px;">
                <canvas id="chartBotSpectrum"></canvas>
            </div>
        </section>
    </div>

    <!-- 4. BARIS 3 KOLOM: PERBANDINGAN SENTIMEN, PROPORSI PLATFORM & RADAR FORENSIK BOT -->
    <div class="charts-grid-3col">
        <section class="chart-box-card">
            <div class="chart-box-title">
                <span>Perbandingan Sentimen: Orang vs Bot</span>
            </div>
            <div style="height: 250px;">
                <canvas id="chartSentimentCompare"></canvas>
            </div>
        </section>

        <section class="chart-box-card">
            <div class="chart-box-title">
                <span>Proporsi Distribusi Platform</span>
            </div>
            <div style="height: 250px;">
                <canvas id="chartPlatformShare"></canvas>
            </div>
        </section>

        <section class="chart-box-card">
            <div class="chart-box-title">
                <span>Pola Anomali Perilaku: Bot vs Warga</span>
            </div>
            <div style="height: 250px;">
                <canvas id="chartForensicRadar"></canvas>
            </div>
        </section>
    </div>

    <!-- 5. PANEL MONITORING REAL-TIME: KATA KUNCI MASIF & TOP AKUN PEMICU LONJAKAN -->
    <div class="charts-grid-2col">
        <!-- Kiri: Monitoring Kata Kunci & Tagar Paling Masif -->
        <section class="monitor-table-card">
            <div class="monitor-table-head">
                <span>Monitoring Kata Kunci &amp; Amplifikasi Bot</span>
                <span style="font-size: 12px; font-weight: 500; color: var(--color-text-secondary);">Rasio Orang vs Bot</span>
            </div>

            @foreach($keywordMonitor as $km)
                <div class="monitor-row">
                    <div>
                        <a href="{{ route('feed.platform', ['issueSlug' => $km['issue_slug'], 'platform' => 'x']) }}"
                           style="font-size: 13.5px; font-weight: 600; color: var(--color-text-primary); text-decoration: none;">
                            "{{ $km['keyword'] }}"
                        </a>
                        <div style="font-size: 12px; color: var(--color-text-secondary);">
                            {{ $km['issue_title'] }} &middot; <span class="tabular">{{ number_format($km['posts_count'], 0, ',', '.') }} post</span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                            <div class="tabular" style="font-size: 11.5px; font-weight: 600;">
                                <span style="color: var(--color-teal-dark);">{{ number_format($km['human_pct'], 1) }}% Orang</span>
                                <span style="color: var(--color-text-muted);">/</span>
                                <span style="color: var(--color-red-dark);">{{ number_format($km['bot_pct'], 1) }}% Bot</span>
                            </div>
                            <div class="mini-bar-track">
                                <div style="width: {{ $km['human_pct'] }}%; background: var(--color-teal);"></div>
                                <div style="width: {{ $km['bot_pct'] }}%; background: var(--color-red);"></div>
                            </div>
                        </div>

                        @if($km['trend'] === 'up')
                            <span class="status-pill is-up">▲ Naik</span>
                        @elseif($km['trend'] === 'down')
                            <span class="status-pill is-down">▼ Turun</span>
                        @else
                            <span class="status-pill is-stable">━ Stabil</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        <!-- Kanan: Monitoring Top Akun Pemicu Engagement & Amplifikasi -->
        <section class="monitor-table-card">
            <div class="monitor-table-head">
                <span>Monitoring Akun Pemicu Interaksi Tertinggi</span>
                <span style="font-size: 12px; font-weight: 500; color: var(--color-text-secondary);">Total Interaksi</span>
            </div>

            @foreach($topTriggerAccounts as $acc)
                <div class="monitor-row">
                    <div>
                        <a href="{{ route('accounts', ['handle' => $acc['handle']]) }}"
                           style="font-size: 13.5px; font-weight: 600; color: var(--color-text-primary); text-decoration: underline;">
                            {{ $acc['name'] }}
                        </a>
                        <div class="tabular" style="font-size: 12px; color: var(--color-text-secondary);">
                            {{ $acc['handle'] }} &middot; {{ $acc['platform'] }} &middot; {{ $acc['posts_count'] }} post
                        </div>
                    </div>

                    <div class="tabular" style="display: flex; align-items: center; gap: 14px; font-size: 12.5px; font-weight: 600;">
                        <span>{{ number_format($acc['engagement'], 0, ',', '.') }} interaksi</span>
                        @if($acc['is_bot'])
                            <span style="color: var(--color-red-dark);">Bot ({{ $acc['bot_score'] }}%)</span>
                        @else
                            <span style="color: var(--color-teal-dark);">Orang ({{ round(100 - $acc['bot_score'], 1) }}%)</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>
    </div>

    <!-- 6. MONITORING SENTIMEN PER LEMBAGA PEMERINTAHAN (EKSTRAKSI AI QWEN JUPYTERHUB) -->
    <section class="monitor-table-card">
        <div class="monitor-table-head">
            <span>Monitoring Sentimen per Lembaga Pemerintahan (AI Qwen JupyterHub)</span>
            <span style="font-size: 12px; font-weight: 500; color: var(--color-text-secondary);">Rincian Positif / Negatif / Netral</span>
        </div>

        @forelse($institutionMonitor ?? [] as $inst)
            <div class="monitor-row">
                <div>
                    <div style="font-size: 13.5px; font-weight: 600; color: var(--color-text-primary);">
                        {{ $inst['lembaga'] }}
                        <span class="tabular" style="font-size: 12px; font-weight: 500; color: var(--color-text-secondary); margin-left: 6px;">
                            ({{ number_format($inst['total'], 0, ',', '.') }} sebutan)
                        </span>
                    </div>
                    <div style="font-size: 12px; color: var(--color-text-secondary);">
                        Ringkasan AI: {{ $inst['sample_reason'] }}
                    </div>
                </div>

                <div class="tabular" style="display: flex; align-items: center; gap: 14px; font-size: 12.5px; font-weight: 600;">
                    <span style="color: var(--color-teal-dark);">+{{ $inst['pos'] }} Positif</span>
                    <span style="color: var(--color-text-muted);">&middot;</span>
                    <span style="color: var(--color-red-dark);">-{{ $inst['neg'] }} Negatif</span>
                    @if($inst['netral'] > 0)
                        <span style="color: var(--color-text-muted);">&middot;</span>
                        <span style="color: var(--color-text-secondary);">{{ $inst['netral'] }} Netral</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="monitor-row" style="justify-content: center; color: var(--color-text-muted); font-size: 12.5px;">
                Belum ada rincian lembaga dari AI Qwen pada filter ini. Kirim parameter ai_institutions ke POST /api/ingest atau sinkronkan stream_analisis_sospem.json.
            </div>
        @endforelse
    </section>
@endsection

@section('scripts')
<script>
    const isLight = () => document.documentElement.getAttribute('data-theme') === 'light';
    const getTextColor = () => isLight() ? '#64748b' : '#9e9e9e';
    const getGridColor = () => isLight() ? '#e2e8f0' : '#262626';

    Chart.defaults.color = getTextColor();
    Chart.defaults.borderColor = getGridColor();
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, sans-serif";

    const hourlyLabels = @json($chartData['hourly_labels']);
    const hourlyHuman = @json($chartData['hourly_human']);
    const hourlyBot = @json($chartData['hourly_bot']);
    const sentimentPos = @json($chartData['sentiment_pos']);
    const sentimentNeg = @json($chartData['sentiment_neg']);
    const platformCounts = @json($chartData['platform_counts']);
    const issueLabels = @json($chartData['issue_labels']);
    const issueHumanCounts = @json($chartData['issue_human_counts']);
    const issueBotCounts = @json($chartData['issue_bot_counts']);
    const spectrumLabels = @json($chartData['spectrum_labels']);
    const spectrumCounts = @json($chartData['spectrum_counts']);
    const radarLabels = @json($chartData['radar_labels']);
    const radarBot = @json($chartData['radar_bot']);
    const radarHuman = @json($chartData['radar_human']);

    // 1. Grafik Garis Tren Volume per Jam (Dengan Gradasi Area Halus ala Stockbit)
    const ctxHourly = document.getElementById('chartHourlyVolume').getContext('2d');
    const gradGreen = ctxHourly.createLinearGradient(0, 0, 0, 280);
    gradGreen.addColorStop(0, 'rgba(24, 178, 111, 0.28)');
    gradGreen.addColorStop(1, 'rgba(24, 178, 111, 0.0)');

    const gradRed = ctxHourly.createLinearGradient(0, 0, 0, 280);
    gradRed.addColorStop(0, 'rgba(240, 68, 56, 0.18)');
    gradRed.addColorStop(1, 'rgba(240, 68, 56, 0.0)');

    const chartHourly = new Chart(ctxHourly, {
        type: 'line',
        data: {
            labels: hourlyLabels,
            datasets: [
                {
                    label: 'Orang (Warga Organik)',
                    data: hourlyHuman,
                    borderColor: '#21ce83',
                    backgroundColor: gradGreen,
                    borderWidth: 2.2,
                    pointRadius: 3.5,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#21ce83',
                    fill: true,
                    tension: 0.36
                },
                {
                    label: 'Jaringan Bot (Buzzer)',
                    data: hourlyBot,
                    borderColor: '#f04438',
                    backgroundColor: gradRed,
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#f04438',
                    fill: true,
                    tension: 0.36
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            }
        }
    });

    // 2. Grafik Stacked Horizontal Bar: Komposisi Volume 10 Isu (Orang vs Bot)
    const chartIssueBreakdown = new Chart(document.getElementById('chartIssueBreakdown'), {
        type: 'bar',
        data: {
            labels: issueLabels,
            datasets: [
                {
                    label: 'Warga Organik',
                    data: issueHumanCounts,
                    backgroundColor: '#18b26f',
                    borderRadius: 3
                },
                {
                    label: 'Jaringan Bot',
                    data: issueBotCounts,
                    backgroundColor: '#f04438',
                    borderRadius: 3
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true },
                y: { stacked: true }
            }
        }
    });

    // 3. Grafik Distribusi Spektrum Skor Bot (5 Rentang Forensik)
    const chartBotSpectrum = new Chart(document.getElementById('chartBotSpectrum'), {
        type: 'bar',
        data: {
            labels: spectrumLabels,
            datasets: [{
                label: 'Jumlah Postingan',
                data: spectrumCounts,
                backgroundColor: [
                    '#18b26f',
                    '#34d399',
                    '#737373',
                    '#fb7185',
                    '#f04438'
                ],
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });

    // 4. Grafik Perbandingan Sentimen Orang vs Bot
    const chartSentiment = new Chart(document.getElementById('chartSentimentCompare'), {
        type: 'bar',
        data: {
            labels: ['Orang (Warga Asli)', 'Jaringan Bot'],
            datasets: [
                {
                    label: 'Positif (%)',
                    data: sentimentPos,
                    backgroundColor: '#18b26f',
                    borderRadius: 4
                },
                {
                    label: 'Negatif (%)',
                    data: sentimentNeg,
                    backgroundColor: '#f04438',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { max: 100 }
            }
        }
    });

    // 5. Grafik Doughnut Proporsi Distribusi Platform
    const chartPlatform = new Chart(document.getElementById('chartPlatformShare'), {
        type: 'doughnut',
        data: {
            labels: ['Meta (IG & FB)', 'X & Threads', 'TikTok & YouTube'],
            datasets: [{
                data: platformCounts,
                backgroundColor: ['#18b26f', '#3b82f6', '#737373'],
                borderColor: isLight() ? '#ffffff' : '#181818',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%'
        }
    });

    // 6. Grafik Radar Pola Anomali Perilaku: Bot vs Warga
    const chartRadar = new Chart(document.getElementById('chartForensicRadar'), {
        type: 'radar',
        data: {
            labels: radarLabels,
            datasets: [
                {
                    label: 'Jaringan Bot',
                    data: radarBot,
                    borderColor: '#f04438',
                    backgroundColor: 'rgba(240, 68, 56, 0.20)',
                    pointBackgroundColor: '#f04438',
                    borderWidth: 2
                },
                {
                    label: 'Warga Organik',
                    data: radarHuman,
                    borderColor: '#21ce83',
                    backgroundColor: 'rgba(24, 178, 111, 0.20)',
                    pointBackgroundColor: '#21ce83',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    min: 0,
                    max: 100,
                    ticks: { display: false },
                    grid: { color: getGridColor() },
                    angleLines: { color: getGridColor() },
                    pointLabels: {
                        color: getTextColor(),
                        font: { size: 11 }
                    }
                }
            }
        }
    });

    // Sinkronisasi Warna Otomatis Saat Ganti Mode Malam / Mode Terang
    window.addEventListener('sospem-theme-changed', function() {
        const textColor = getTextColor();
        const gridColor = getGridColor();
        Chart.defaults.color = textColor;
        Chart.defaults.borderColor = gridColor;

        [chartHourly, chartIssueBreakdown, chartBotSpectrum, chartSentiment].forEach(c => {
            if (c.options.scales.x) {
                c.options.scales.x.ticks.color = textColor;
                c.options.scales.x.grid.color = gridColor;
            }
            if (c.options.scales.y) {
                c.options.scales.y.ticks.color = textColor;
                c.options.scales.y.grid.color = gridColor;
            }
            if (c.options.plugins && c.options.plugins.legend && c.options.plugins.legend.labels) {
                c.options.plugins.legend.labels.color = textColor;
            }
            c.update();
        });

        chartPlatform.data.datasets[0].borderColor = isLight() ? '#ffffff' : '#181818';
        chartPlatform.options.plugins.legend.labels.color = textColor;
        chartPlatform.update();

        chartRadar.options.scales.r.grid.color = gridColor;
        chartRadar.options.scales.r.angleLines.color = gridColor;
        chartRadar.options.scales.r.pointLabels.color = textColor;
        chartRadar.options.plugins.legend.labels.color = textColor;
        chartRadar.update();
    });
</script>
@endsection
