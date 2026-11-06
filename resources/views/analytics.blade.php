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

    .chart-box-sub {
        font-size: 12px;
        color: var(--color-text-muted);
    }
</style>
@endsection

@section('content')
    <header class="page-title-bar">
        <h1 class="page-heading">
            <span>Grafik Analitik &amp; Deret Waktu</span>
            <span class="page-heading-muted">&middot; Perbandingan Orang vs Bot</span>
        </h1>
        <div class="top-right-actions">
            <a href="{{ route('dashboard') }}" class="btn-clean">&larr; Dashboard</a>
        </div>
    </header>

    <!-- 1. Grafik Garis Volume Postingan per Jam & Lonjakan Puncak -->
    <section class="chart-box-card">
        <div class="chart-box-title">
            <span>Volume Postingan per Jam &amp; Titik Lonjakan Puncak (Makan Bergizi Gratis)</span>
            <span class="status-pill is-up">▲ Naik</span>
        </div>
        <div style="height: 280px;">
            <canvas id="chartHourlyVolume"></canvas>
        </div>
    </section>

    <!-- 2. Grafik Perbandingan Sentimen Orang vs Bot & Proporsi Platform -->
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

    const chartHourly = new Chart(document.getElementById('chartHourlyVolume'), {
        type: 'line',
        data: {
            labels: ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '21:00'],
            datasets: [
                {
                    label: 'Orang (Warga Organik)',
                    data: [420, 280, 1100, 3900, 6200, 5400, 6800, 5100],
                    borderColor: '#18b26f',
                    backgroundColor: 'rgba(24, 178, 111, 0.14)',
                    fill: true,
                    tension: 0.35
                },
                {
                    label: 'Jaringan Bot (Buzzer)',
                    data: [60, 40, 290, 2400, 4900, 1800, 3100, 2200],
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
                    data: [31.4, 84.1],
                    backgroundColor: '#18b26f'
                },
                {
                    label: 'Negatif (%)',
                    data: [68.6, 15.9],
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
                data: [23400, 31200, 13800],
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

