@extends('layouts.app')

@section('title', 'Grafik Analitik & Deret Waktu — SOSPEM')

@section('styles')
<style>
    .charts-grid-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    @media (max-width: 960px) {
        .charts-grid-2col {
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
        gap: 12px;
    }

    .chart-box-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--color-text-primary);
        display: flex;
        align-items: center;
        justify-content: space-between;
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
</style>
@endsection

@section('content')
    <header class="page-title-bar">
        <h1 class="page-heading">
            <span>Grafik Analitik &amp; Deret Waktu</span>
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

    <!-- 1. Grafik Garis Volume Postingan per Jam dari PostgreSQL -->
    <section class="chart-box-card">
        <div class="chart-box-title">
            <span>Volume Postingan per Jam ({{ $selectedIssue ? $selectedIssue['title'] : 'Seluruh Isu Terpantau' }})</span>
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
        <div style="height: 280px;">
            <canvas id="chartHourlyVolume"></canvas>
        </div>
    </section>

    <!-- 2. Grafik Perbandingan Sentimen Orang vs Bot & Proporsi Platform dari PostgreSQL -->
    <div class="charts-grid-2col">
        <section class="chart-box-card">
            <div class="chart-box-title">Perbandingan Sentimen: Orang vs Bot</div>
            <div style="height: 240px;">
                <canvas id="chartSentimentCompare"></canvas>
            </div>
        </section>

        <section class="chart-box-card">
            <div class="chart-box-title">Proporsi Distribusi Platform</div>
            <div style="height: 240px;">
                <canvas id="chartPlatformShare"></canvas>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
<script>
    const isLight = () => document.documentElement.getAttribute('data-theme') === 'light';
    Chart.defaults.color = isLight() ? '#64748b' : '#9e9e9e';
    Chart.defaults.borderColor = isLight() ? '#e2e8f0' : '#262626';

    const hourlyLabels = @json($chartData['hourly_labels']);
    const hourlyHuman = @json($chartData['hourly_human']);
    const hourlyBot = @json($chartData['hourly_bot']);
    const sentimentPos = @json($chartData['sentiment_pos']);
    const sentimentNeg = @json($chartData['sentiment_neg']);
    const platformCounts = @json($chartData['platform_counts']);

    const chartHourly = new Chart(document.getElementById('chartHourlyVolume'), {
        type: 'line',
        data: {
            labels: hourlyLabels,
            datasets: [
                {
                    label: 'Orang (Warga Organik)',
                    data: hourlyHuman,
                    borderColor: '#18b26f',
                    backgroundColor: 'rgba(24, 178, 111, 0.14)',
                    fill: true,
                    tension: 0.35
                },
                {
                    label: 'Jaringan Bot (Buzzer)',
                    data: hourlyBot,
                    borderColor: '#f04438',
                    borderDash: [5, 5],
                    tension: 0.35
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    const chartSentiment = new Chart(document.getElementById('chartSentimentCompare'), {
        type: 'bar',
        data: {
            labels: ['Orang (Warga Asli)', 'Jaringan Bot'],
            datasets: [
                {
                    label: 'Positif (%)',
                    data: sentimentPos,
                    backgroundColor: '#18b26f'
                },
                {
                    label: 'Negatif (%)',
                    data: sentimentNeg,
                    backgroundColor: '#f04438'
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
            maintainAspectRatio: false
        }
    });

    window.addEventListener('sospem-theme-changed', function() {
        const textColor = isLight() ? '#64748b' : '#9e9e9e';
        const gridColor = isLight() ? '#e2e8f0' : '#262626';
        Chart.defaults.color = textColor;
        Chart.defaults.borderColor = gridColor;

        [chartHourly, chartSentiment].forEach(c => {
            c.options.scales.x.ticks.color = textColor;
            c.options.scales.y.ticks.color = textColor;
            c.options.scales.x.grid.color = gridColor;
            c.options.scales.y.grid.color = gridColor;
            c.options.plugins.legend.labels.color = textColor;
            c.update();
        });

        chartPlatform.data.datasets[0].borderColor = isLight() ? '#ffffff' : '#181818';
        chartPlatform.options.plugins.legend.labels.color = textColor;
        chartPlatform.update();
    });
</script>
@endsection
