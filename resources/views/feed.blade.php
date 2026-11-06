@extends('layouts.app')

@section('title', $issue['title'] . ' (' . $platform['name'] . ') — Split Feed SOSPEM')

@section('styles')
<style>
    /* Header Konteks Halaman Split Feed */
    .feed-context-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 18px 24px;
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .feed-context-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .breadcrumb-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        font-weight: 500;
        color: var(--color-text-secondary);
        text-decoration: none;
        margin-bottom: 4px;
    }
    .breadcrumb-back:hover {
        color: var(--color-text-primary);
    }

    .feed-context-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--color-text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .feed-toolbar-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding-top: 12px;
        border-top: 1px solid var(--color-border-subtle);
    }

    .platform-switch-pills {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .plat-pill-link {
        font-size: 12px;
        font-weight: 500;
        padding: 5px 12px;
        border-radius: 6px;
        border: 1px solid var(--color-border);
        background: var(--color-surface);
        color: var(--color-text-secondary);
        text-decoration: none;
        transition: all 0.15s;
    }
    .plat-pill-link:hover {
        background: var(--color-surface-subtle);
        color: var(--color-text-primary);
    }
    .plat-pill-link.is-active {
        background: var(--color-surface-pill);
        color: var(--color-text-primary);
        border-color: var(--color-border-strong);
        font-weight: 600;
    }

    /* ==========================================================================
       SPLIT SCREEN 2 KOLOM: KIRI (POSTINGAN ORANG) & KANAN (POSTINGAN BOT)
       Masing-masing memiliki container scroll independen.
       ========================================================================== */
    .split-feed-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        align-items: start;
    }

    @media (max-width: 960px) {
        .split-feed-grid {
            grid-template-columns: 1fr;
        }
    }

    .split-column-box {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-card);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 240px);
        min-height: 540px;
        overflow: hidden;
    }

    .split-col-header {
        padding: 14px 20px;
        border-bottom: 1px solid var(--color-border);
        background: var(--color-surface);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-shrink: 0;
    }

    .split-col-title {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--color-text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Independent Scroll Container per Kolom */
    .split-col-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: var(--color-surface-subtle);
    }

    .post-item-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .post-item-top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
    }

    .post-author-link {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--color-text-primary);
        text-decoration: underline;
    }
    .post-author-link:hover {
        color: var(--color-teal-dark);
    }

    .post-handle-text {
        font-size: 12px;
        color: var(--color-text-muted);
        margin-left: 4px;
    }

    .post-badges-group {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .post-body-text {
        font-size: 13.5px;
        line-height: 1.55;
        color: var(--color-text-primary);
    }

    .post-item-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: var(--color-text-muted);
        border-top: 1px solid var(--color-border-subtle);
        padding-top: 8px;
        margin-top: 2px;
    }

    .post-ext-link {
        font-weight: 600;
        color: var(--color-text-primary);
        text-decoration: none;
    }
    .post-ext-link:hover {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')
    <!-- HEADER KONTEKS ISU & PLATFORM -->
    <section class="feed-context-card">
        <div class="feed-context-top">
            <div>
                <a href="{{ route('dashboard') }}" class="breadcrumb-back">&larr; Kembali ke Matriks Isu</a>
                <h1 class="feed-context-title">
                    <span>{{ $issue['title'] }}</span>
                    <span style="color: var(--color-text-muted); font-weight: 400;">&middot;</span>
                    <span>{{ $platform['name'] }}</span>
                    <span class="tabular" style="font-size: 13px; font-weight: 500; color: var(--color-text-secondary);">({{ $platform['volume'] }})</span>
                </h1>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-clean" onclick="injectLiveHumanPost()">+ Simulasi Post Orang</button>
                <button type="button" class="btn-clean btn-primary" onclick="injectLiveBotPost()">+ Simulasi Post Bot</button>
            </div>
        </div>

        <div class="feed-toolbar-row">
            <!-- Pindah Platform untuk Isu yang Sama -->
            <div class="platform-switch-pills">
                <span style="font-size: 12px; color: var(--color-text-muted); margin-right: 4px;">Platform:</span>
                @foreach($issue['platforms'] as $pKey => $pData)
                    @if($pData['sufficient'])
                        <a href="{{ route('feed.platform', ['issueSlug' => $issue['slug'], 'platform' => $pKey, 'sentiment' => $sentimentFilter]) }}"
                           class="plat-pill-link {{ $platformKey === $pKey ? 'is-active' : '' }}">
                            {{ $pData['name'] }}
                        </a>
                    @endif
                @endforeach
            </div>

            <!-- Filter Sentimen -->
            <div class="platform-switch-pills">
                <span style="font-size: 12px; color: var(--color-text-muted); margin-right: 4px;">Filter Sentimen:</span>
                <a href="{{ route('feed.platform', ['issueSlug' => $issue['slug'], 'platform' => $platformKey, 'sentiment' => 'all']) }}"
                   class="plat-pill-link {{ $sentimentFilter === 'all' ? 'is-active' : '' }}">Semua</a>
                <a href="{{ route('feed.platform', ['issueSlug' => $issue['slug'], 'platform' => $platformKey, 'sentiment' => 'positive']) }}"
                   class="plat-pill-link {{ $sentimentFilter === 'positive' ? 'is-active' : '' }}">Positif</a>
                <a href="{{ route('feed.platform', ['issueSlug' => $issue['slug'], 'platform' => $platformKey, 'sentiment' => 'negative']) }}"
                   class="plat-pill-link {{ $sentimentFilter === 'negative' ? 'is-active' : '' }}">Negatif</a>
            </div>
        </div>
    </section>

    <!-- SPLIT SCREEN 2 KOLOM: KIRI (POSTINGAN ORANG) & KANAN (POSTINGAN BOT) -->
    <div class="split-feed-grid">

        <!-- KOLOM KIRI: POSTINGAN ORANG (WARGA ORGANIK) - SCROLL INDEPENDEN -->
        <section class="split-column-box">
            <div class="split-col-header">
                <div class="split-col-title">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-teal);"></span>
                    <span>Postingan Orang (Warga Organik)</span>
                </div>
                <div class="tabular" style="font-size: 12px; font-weight: 600;">
                    <span style="color: var(--color-teal-dark);">+{{ number_format($platform['human_pos'], 1) }}% Pos</span>
                    <span style="color: var(--color-text-muted);">/</span>
                    <span style="color: var(--color-red-dark);">-{{ number_format($platform['human_neg'], 1) }}% Neg</span>
                </div>
            </div>

            <div class="split-col-scroll" id="humanFeedScroll">
                @forelse($humanPosts as $post)
                    <article class="post-item-card">
                        <div class="post-item-top">
                            <div>
                                <a href="{{ route('accounts', ['handle' => $post['handle']]) }}" class="post-author-link">{{ $post['author'] }}</a>
                                <span class="post-handle-text tabular">{{ $post['handle'] }} &middot; {{ $post['time'] }}</span>
                            </div>
                            <div class="post-badges-group tabular" style="font-size: 12px; font-weight: 600; gap: 10px;">
                                <span style="color: var(--color-teal-dark);">Orang ({{ 100 - $post['bot_score'] }}%)</span>
                                <span style="color: var(--color-text-muted);">&middot;</span>
                                @if($post['sentiment'] === 'positive')
                                    <span style="color: var(--color-teal-dark);">+ Positif ({{ $post['sentiment_conf'] }}%)</span>
                                @else
                                    <span style="color: var(--color-red-dark);">- Negatif ({{ $post['sentiment_conf'] }}%)</span>
                                @endif
                            </div>
                        </div>

                        <p class="post-body-text">{{ $post['text'] }}</p>

                        <div class="post-item-footer tabular">
                            <span>{{ number_format($post['likes'], 0, ',', '.') }} suka &middot; {{ number_format($post['reposts'], 0, ',', '.') }} repost</span>
                            <a href="{{ $post['url'] }}" target="_blank" rel="noopener" class="post-ext-link">Buka postingan asli ↗</a>
                        </div>
                    </article>
                @empty
                    <div style="padding: 24px; text-align: center; color: var(--color-text-muted); font-size: 13px;">
                        Tidak ada postingan warga untuk filter sentimen ini.
                    </div>
                @endforelse
            </div>
        </section>

        <!-- KOLOM KANAN: POSTINGAN BOT (JARINGAN TERKOORDINASI) - SCROLL INDEPENDEN -->
        <section class="split-column-box">
            <div class="split-col-header">
                <div class="split-col-title">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-slate-dark);"></span>
                    <span>Postingan Jaringan Bot (Buzzer)</span>
                </div>
                <div class="tabular" style="font-size: 12px; font-weight: 600;">
                    <span style="color: var(--color-teal-dark);">+{{ number_format($platform['bot_pos'], 1) }}% Pos</span>
                    <span style="color: var(--color-text-muted);">/</span>
                    <span style="color: var(--color-red-dark);">-{{ number_format($platform['bot_neg'], 1) }}% Neg</span>
                </div>
            </div>

            <div class="split-col-scroll" id="botFeedScroll">
                @forelse($botPosts as $post)
                    <article class="post-item-card">
                        <div class="post-item-top">
                            <div>
                                <a href="{{ route('accounts', ['handle' => $post['handle']]) }}" class="post-author-link">{{ $post['author'] }}</a>
                                <span class="post-handle-text tabular">{{ $post['handle'] }} &middot; {{ $post['time'] }}</span>
                            </div>
                            <div class="post-badges-group tabular" style="font-size: 12px; font-weight: 600; gap: 10px;">
                                <span style="color: var(--color-text-secondary);">Bot ({{ $post['bot_score'] }}%)</span>
                                <span style="color: var(--color-text-muted);">&middot;</span>
                                @if($post['sentiment'] === 'positive')
                                    <span style="color: var(--color-teal-dark);">+ Positif ({{ $post['sentiment_conf'] }}%)</span>
                                @else
                                    <span style="color: var(--color-red-dark);">- Negatif ({{ $post['sentiment_conf'] }}%)</span>
                                @endif
                            </div>
                        </div>

                        <p class="post-body-text">{{ $post['text'] }}</p>

                        <div class="post-item-footer tabular">
                            <span>{{ number_format($post['likes'], 0, ',', '.') }} suka &middot; {{ number_format($post['reposts'], 0, ',', '.') }} repost</span>
                            <a href="{{ $post['url'] }}" target="_blank" rel="noopener" class="post-ext-link">Buka postingan asli ↗</a>
                        </div>
                    </article>
                @empty
                    <div style="padding: 24px; text-align: center; color: var(--color-text-muted); font-size: 13px;">
                        Tidak ada postingan bot untuk filter sentimen ini.
                    </div>
                @endforelse
            </div>
        </section>

    </div>
@endsection

@section('scripts')
<script>
    function injectLiveHumanPost() {
        const container = document.getElementById('humanFeedScroll');
        const card = document.createElement('article');
        card.className = 'post-item-card';
        card.style.borderColor = 'var(--color-teal)';
        card.innerHTML = `
            <div class="post-item-top">
                <div>
                    <a href="{{ route('accounts', ['handle' => '@rizalfauzi_econ']) }}" class="post-author-link">Warga Lapangan (Live)</a>
                    <span class="post-handle-text tabular">@suara_warga_live &middot; Baru saja</span>
                </div>
                <div class="post-badges-group tabular" style="font-size: 12px; font-weight: 600; gap: 10px;">
                    <span style="color: var(--color-teal-dark);">Orang (96.2%)</span>
                    <span style="color: var(--color-text-muted);">&middot;</span>
                    <span style="color: var(--color-red-dark);">- Negatif (91.4%)</span>
                </div>
            </div>
            <p class="post-body-text">[LIVE STREAM] Laporan langsung warga terkait {{ $issue['title'] }} di {{ $platform['name'] }}: mohon pengawasan kualitas di tingkat kelurahan diperketat.</p>
            <div class="post-item-footer tabular">
                <span>1 suka &middot; 0 repost</span>
                <a href="https://x.com" target="_blank" class="post-ext-link">Buka postingan asli ↗</a>
            </div>
        `;
        container.insertBefore(card, container.firstChild);
        showToast("1 postingan warga organik masuk di kolom kiri");
    }

    function injectLiveBotPost() {
        const container = document.getElementById('botFeedScroll');
        const card = document.createElement('article');
        card.className = 'post-item-card';
        card.style.borderColor = 'var(--color-red)';
        card.innerHTML = `
            <div class="post-item-top">
                <div>
                    <a href="{{ route('accounts', ['handle' => '@sahabat_negeri77']) }}" class="post-author-link">Bot Amplifier #409</a>
                    <span class="post-handle-text tabular">@bot_cluster_409 &middot; Baru saja</span>
                </div>
                <div class="post-badges-group tabular" style="font-size: 12px; font-weight: 600; gap: 10px;">
                    <span style="color: var(--color-text-secondary);">Bot (97.8%)</span>
                    <span style="color: var(--color-text-muted);">&middot;</span>
                    <span style="color: var(--color-teal-dark);">+ Positif (98.9%)</span>
                </div>
            </div>
            <p class="post-body-text">[LIVE STREAM] Dukungan penuh 100% untuk {{ $issue['title'] }}! Bersama menuju Indonesia Maju! #SukseskanProgramNasional</p>
            <div class="post-item-footer tabular">
                <span>120 suka &middot; 85 repost</span>
                <a href="https://x.com" target="_blank" class="post-ext-link">Buka postingan asli ↗</a>
            </div>
        `;
        container.insertBefore(card, container.firstChild);
        showToast("1 postingan jaringan bot masuk di kolom kanan");
    }
</script>
@endsection

