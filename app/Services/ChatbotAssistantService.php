<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class ChatbotAssistantService
{
    /**
     * Kamus koordinat cepat untuk kota-kota besar di Indonesia agar kueri cuaca instan & andal.
     *
     * @var array<string, array{name: string, lat: float, lon: float}>
     */
    private array $cityCoordinates = [
        'medan' => ['name' => 'Medan', 'lat' => 3.5952, 'lon' => 98.6722],
        'jakarta' => ['name' => 'Jakarta', 'lat' => -6.2088, 'lon' => 106.8456],
        'bandung' => ['name' => 'Bandung', 'lat' => -6.9175, 'lon' => 107.6191],
        'surabaya' => ['name' => 'Surabaya', 'lat' => -7.2575, 'lon' => 112.7521],
        'yogyakarta' => ['name' => 'Yogyakarta', 'lat' => -7.7956, 'lon' => 110.3695],
        'jogja' => ['name' => 'Yogyakarta', 'lat' => -7.7956, 'lon' => 110.3695],
        'semarang' => ['name' => 'Semarang', 'lat' => -6.9932, 'lon' => 110.4203],
        'makassar' => ['name' => 'Makassar', 'lat' => -5.1477, 'lon' => 119.4327],
        'denpasar' => ['name' => 'Denpasar (Bali)', 'lat' => -8.6705, 'lon' => 115.2126],
        'bali' => ['name' => 'Denpasar (Bali)', 'lat' => -8.6705, 'lon' => 115.2126],
        'palembang' => ['name' => 'Palembang', 'lat' => -2.9761, 'lon' => 104.7754],
        'pekanbaru' => ['name' => 'Pekanbaru', 'lat' => 0.5071, 'lon' => 101.4478],
        'padang' => ['name' => 'Padang', 'lat' => -0.9471, 'lon' => 100.4172],
        'balikpapan' => ['name' => 'Balikpapan', 'lat' => -1.2379, 'lon' => 116.8529],
        'banjarmasin' => ['name' => 'Banjarmasin', 'lat' => -3.3186, 'lon' => 114.5944],
        'manado' => ['name' => 'Manado', 'lat' => 1.4748, 'lon' => 124.8421],
        'aceh' => ['name' => 'Banda Aceh', 'lat' => 5.5483, 'lon' => 95.3238],
        'pontianak' => ['name' => 'Pontianak', 'lat' => -0.0263, 'lon' => 109.3425],
        'malang' => ['name' => 'Malang', 'lat' => -7.9666, 'lon' => 112.6326],
        'solo' => ['name' => 'Surakarta (Solo)', 'lat' => -7.5755, 'lon' => 110.8243],
        'bogor' => ['name' => 'Bogor', 'lat' => -6.5971, 'lon' => 106.8060],
        'depok' => ['name' => 'Depok', 'lat' => -6.4025, 'lon' => 106.7942],
        'tangerang' => ['name' => 'Tangerang', 'lat' => -6.1783, 'lon' => 106.6319],
        'bekasi' => ['name' => 'Bekasi', 'lat' => -6.2383, 'lon' => 106.9756],
    ];

    /**
     * Bersihkan teks balasan dari simbol markdown mentah agar tampil rapi di bubble chat.
     */
    public function cleanReplyText(string $text): string
    {
        $cleaned = str_replace(['**', '__', '`'], '', $text);
        $cleaned = preg_replace('/(?<!\*)\*([^*\s][^*\n]*?[^*\s]|[^*\s])\*(?!\*)/', '$1', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/(?<!_)_([^_\s][^_\n]*?[^_\s]|[^_\s])_(?!_)/', '$1', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/^#{1,6}\s*/m', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace("/\n{3,}/", "\n\n", $cleaned) ?? $cleaned;

        return trim($cleaned);
    }

    /**
     * Kumpulkan konteks hari & waktu real-time dalam Bahasa Indonesia (WIB).
     *
     * @return array{day_name: string, date_full: string, time_wib: string, greeting: string, summary_line: string}
     */
    public function getTodayContext(): array
    {
        $now = now()->timezone('Asia/Jakarta');
        $days = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $dayName = $days[$now->dayOfWeek] ?? 'Hari Ini';
        $monthName = $months[$now->month] ?? $now->format('M');
        $dateFull = "{$dayName}, {$now->day} {$monthName} {$now->year}";
        $timeWib = $now->format('H:i').' WIB';

        $hour = (int) $now->format('G');
        $greeting = match (true) {
            $hour >= 4 && $hour < 11 => 'Selamat pagi',
            $hour >= 11 && $hour < 15 => 'Selamat siang',
            $hour >= 15 && $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        return [
            'day_name' => $dayName,
            'date_full' => $dateFull,
            'time_wib' => $timeWib,
            'greeting' => $greeting,
            'summary_line' => "{$dateFull}, pukul {$timeWib}",
        ];
    }

    /**
     * Deteksi apakah pertanyaan meminta informasi cuaca, suhu, atau prakiraan hujan.
     */
    public function isWeatherIntent(string $message): bool
    {
        $q = strtolower($message);

        return str_contains($q, 'cuaca')
            || str_contains($q, 'hujan')
            || str_contains($q, 'suhu')
            || str_contains($q, 'temperatur')
            || str_contains($q, 'mendung')
            || str_contains($q, 'panas banget')
            || str_contains($q, 'prakiraan');
    }

    /**
     * Deteksi apakah pertanyaan meminta berita terkini atau pencarian informasi umum di internet.
     */
    public function isInternetNewsOrInfoIntent(string $message): bool
    {
        $q = strtolower($message);

        return str_contains($q, 'berita')
            || str_contains($q, 'kabar hari ini')
            || str_contains($q, 'kejadian hari ini')
            || str_contains($q, 'trending')
            || str_contains($q, 'viral')
            || str_contains($q, 'terbaru di internet')
            || str_contains($q, 'apa yang terjadi')
            || str_contains($q, 'news')
            || (str_contains($q, 'isu') && $this->isGovernmentTopicIntent($message) && (str_contains($q, 'terkini') || str_contains($q, 'terbaru') || str_contains($q, 'kemunduran') || str_contains($q, 'reshuffle') || str_contains($q, 'demo')));
    }

    /**
     * Deteksi apakah pesan membahas pemerintahan Indonesia, lembaga negara, kebijakan publik, atau politik nasional.
     */
    public function isGovernmentTopicIntent(string $message): bool
    {
        $q = strtolower($message);
        $keywords = [
            'pemerintah', 'pemerintahan', 'presiden', 'wapres', 'wakil presiden',
            'prabowo', 'gibran', 'menteri', 'kementerian', 'kabinet',
            'dpr', 'mpr', 'dpd', 'mahkamah', 'kpk', 'bpk', 'polri', 'tni',
            'bssn', 'komdigi', 'kominfo', 'ikn', 'nusantara', 'mbg', 'makan bergizi',
            'subsidi', 'pajak', 'ppn', 'bpjs', 'anggaran', 'apbn', 'korupsi',
            'kebijakan', 'undang-undang', 'pilkada', 'pemilu', 'demokrasi',
            'politik', 'negara', 'daerah', 'gubernur', 'bupati', 'walikota',
            'regulasi', 'birokrasi', 'pelayanan publik',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($q, $keyword)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(mk|ma|uu|asn|pns)\b/', $q);
    }

    /**
     * Deteksi apakah pertanyaan menanyakan update fitur, status, atau cara kerja website SOSPEM.
     */
    public function isWebsiteUpdateIntent(string $message): bool
    {
        $q = strtolower($message);

        return str_contains($q, 'update website')
            || str_contains($q, 'update web')
            || str_contains($q, 'website ini')
            || str_contains($q, 'website kita')
            || str_contains($q, 'fitur website')
            || str_contains($q, 'fitur web')
            || str_contains($q, 'status website')
            || str_contains($q, 'pembaruan')
            || str_contains($q, 'halaman apa')
            || str_contains($q, 'tentang website')
            || str_contains($q, 'tentang sospem')
            || str_contains($q, 'cara kerja')
            || str_contains($q, 'endpoint');
    }

    /**
     * Deteksi apakah pertanyaan berkaitan dengan website SOSPEM, dashboard, isu publik, sentimen lembaga, atau botnet.
     */
    public function isSospemOrWebsiteRelated(string $message): bool
    {
        $q = strtolower($message);

        return $this->isWebsiteUpdateIntent($message)
            || str_contains($q, 'sospem')
            || str_contains($q, 'website')
            || str_contains($q, 'dashboard')
            || str_contains($q, 'isu')
            || str_contains($q, 'bot')
            || str_contains($q, 'buzzer')
            || str_contains($q, 'akun')
            || str_contains($q, 'sentimen')
            || str_contains($q, 'lembaga')
            || str_contains($q, 'komentar')
            || str_contains($q, 'postingan')
            || str_contains($q, 'opini')
            || str_contains($q, 'data')
            || str_contains($q, 'statistik')
            || str_contains($q, 'jupyter')
            || str_contains($q, 'ingest')
            || str_contains($q, 'ringkasan');
    }

    /**
     * Ambil data cuaca live dari Open-Meteo (gratis & tanpa API key).
     *
     * @return array{ok: bool, city: string, condition: string, temp_c?: float, feels_like_c?: float, humidity?: int, wind_kmh?: float, formatted: string}
     */
    public function fetchWeatherInfo(string $message): array
    {
        $q = strtolower($message);
        $selectedCity = $this->cityCoordinates['medan'];

        foreach ($this->cityCoordinates as $keyword => $coords) {
            if (str_contains($q, $keyword)) {
                $selectedCity = $coords;
                break;
            }
        }

        // Coba geocoding dinamis jika user menyebut kota di luar daftar cepat (pola "cuaca di <kota>")
        if ($selectedCity['name'] === 'Medan' && ! str_contains($q, 'medan')) {
            if (preg_match('/(?:cuaca|hujan|suhu)\s+(?:di|untuk|kota)\s+([a-zA-Z\s]{3,25})/i', $message, $matches)) {
                $candidate = trim($matches[1]);
                if (! in_array(strtolower($candidate), ['hari ini', 'sekarang', 'sini', 'indonesia'], true)) {
                    try {
                        $geoResp = Http::connectTimeout(3)
                            ->timeout(4)
                            ->get('https://geocoding-api.open-meteo.com/v1/search', [
                                'name' => $candidate,
                                'count' => 1,
                                'language' => 'id',
                            ]);

                        if ($geoResp->successful()) {
                            $first = $geoResp->json('results.0');
                            if (is_array($first) && isset($first['latitude'], $first['longitude'])) {
                                $selectedCity = [
                                    'name' => (string) ($first['name'] ?? $candidate),
                                    'lat' => (float) $first['latitude'],
                                    'lon' => (float) $first['longitude'],
                                ];
                            }
                        }
                    } catch (Throwable) {
                        // Gunakan fallback koordinat default
                    }
                }
            }
        }

        try {
            $resp = Http::connectTimeout(3)
                ->timeout(4)
                ->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $selectedCity['lat'],
                    'longitude' => $selectedCity['lon'],
                    'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m',
                    'timezone' => 'Asia/Jakarta',
                ]);

            if ($resp->successful() && is_array($resp->json('current'))) {
                $cur = $resp->json('current');
                $temp = round((float) ($cur['temperature_2m'] ?? 0), 1);
                $feels = round((float) ($cur['apparent_temperature'] ?? $temp), 1);
                $humidity = (int) ($cur['relative_humidity_2m'] ?? 0);
                $wind = round((float) ($cur['wind_speed_10m'] ?? 0), 1);
                $code = (int) ($cur['weather_code'] ?? 0);
                $condition = $this->translateWeatherCode($code);

                $formatted = "Cuaca Real-Time di {$selectedCity['name']}:\n"
                    ."• Kondisi: {$condition}\n"
                    ."• Suhu: {$temp}°C (terasa seperti {$feels}°C)\n"
                    ."• Kelembapan udara: {$humidity}%\n"
                    ."• Kecepatan angin: {$wind} km/jam";

                return [
                    'ok' => true,
                    'city' => $selectedCity['name'],
                    'condition' => $condition,
                    'temp_c' => $temp,
                    'feels_like_c' => $feels,
                    'humidity' => $humidity,
                    'wind_kmh' => $wind,
                    'formatted' => $formatted,
                ];
            }
        } catch (Throwable) {
            // Tangani kegagalan jaringan secara ramah
        }

        return [
            'ok' => false,
            'city' => $selectedCity['name'],
            'condition' => 'Tidak terjangkau',
            'formatted' => "Maaf, layanan prakiraan cuaca untuk wilayah {$selectedCity['name']} sedang tidak dapat dihubungi saat ini. Pastikan perangkat terhubung ke internet untuk membaca suhu terkini.",
        ];
    }

    /**
     * Ambil berita utama terbaru Indonesia via Google News RSS (gratis tanpa API key).
     *
     * @return array{ok: bool, items: array<int, array{title: string, source: string, link: string, pub_date: string}>, formatted: string}
     */
    public function fetchInternetNewsOrSearch(string $message): array
    {
        $cleanQuery = preg_replace(
            '/\b(apa|apakah|kamu|anda|bisa|dapat|melihat|lihat|lihatin|tahu|tau|cari|carikan|tolong|coba|ada|info|informasi|berita|kabar|hari ini|terbaru|terkini|trending|viral|apa yang terjadi|di internet|cek|tentang|mengenai|soal|dong|ya|gak|tidak)\b/i',
            ' ',
            $message
        ) ?? $message;
        $cleanQuery = trim((string) preg_replace('/\s+/', ' ', $cleanQuery), " ?!.,\t\n\r\0\x0B");

        $rssUrl = $cleanQuery !== '' && mb_strlen($cleanQuery) >= 3
            ? 'https://news.google.com/rss/search?q='.urlencode($cleanQuery).'&hl=id&gl=ID&ceid=ID:id'
            : 'https://news.google.com/rss?hl=id&gl=ID&ceid=ID:id';

        try {
            $resp = Http::connectTimeout(3)
                ->timeout(5)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 SOSPEM-Chatbot/1.0'])
                ->get($rssUrl);

            if ($resp->successful() && str_contains($resp->body(), '<item>')) {
                $xml = @simplexml_load_string($resp->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($xml !== false && isset($xml->channel->item)) {
                    $items = [];
                    foreach ($xml->channel->item as $entry) {
                        $rawPub = trim((string) ($entry->pubDate ?? ''));
                        $pubFormatted = '';
                        if ($rawPub !== '') {
                            try {
                                $pubFormatted = Carbon::parse($rawPub)->timezone('Asia/Jakarta')->format('d M Y');
                            } catch (Throwable) {
                                $pubFormatted = '';
                            }
                        }

                        $items[] = [
                            'title' => trim((string) $entry->title),
                            'source' => trim((string) ($entry->source ?? 'Google News')),
                            'link' => trim((string) $entry->link),
                            'pub_date' => $pubFormatted,
                        ];
                        if (count($items) >= 5) {
                            break;
                        }
                    }

                    if (! empty($items)) {
                        $lines = array_map(
                            function (array $it, int $idx): string {
                                $dateTag = $it['pub_date'] !== '' ? " ({$it['pub_date']})" : '';

                                return ($idx + 1).". {$it['title']}{$dateTag}";
                            },
                            $items,
                            array_keys($items)
                        );

                        $topicLabel = $cleanQuery !== '' ? " terkait \"{$cleanQuery}\"" : '';

                        return [
                            'ok' => true,
                            'items' => $items,
                            'formatted' => "Berita Terkini di Internet (Indonesia){$topicLabel}:\n".implode("\n", $lines),
                        ];
                    }
                }
            }
        } catch (Throwable) {
            // Tangani timeout / offline dengan fallback ramah
        }

        return [
            'ok' => false,
            'items' => [],
            'formatted' => 'Maaf, akses berita internet terbaru sedang tidak dapat dijangkau saat ini. Pastikan koneksi internet aktif lalu coba kembali.',
        ];
    }

    /**
     * Kumpulkan informasi lengkap tentang status, fitur, dan pembaruan terbaru website SOSPEM.
     *
     * @param  array<string, mixed>  $rag
     * @return array<string, mixed>
     */
    public function getWebsiteUpdateContext(array $rag): array
    {
        $latestPost = SocialPost::orderByDesc('updated_at')->first();
        $latestIssue = Issue::orderByDesc('updated_at')->first();

        $lastPostUpdate = $latestPost && $latestPost->updated_at
            ? Carbon::parse($latestPost->updated_at)->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB'
            : 'Belum ada komentar masuk (database kosong, siap menerima data)';

        $lastIssueUpdate = $latestIssue && $latestIssue->updated_at
            ? Carbon::parse($latestIssue->updated_at)->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB'
            : 'Belum ada pembaruan';

        $kpi = $rag['kpi'];
        $summary = "INFO & UPDATE WEBSITE SOSPEM:\n"
            ."- Nama Sistem: SOSPEM (Pemantauan Opini Publik & Deteksi Jaringan Bot)\n"
            ."- Pembaruan Data Komentar Terakhir: {$lastPostUpdate}\n"
            ."- Pembaruan Daftar Isu Terakhir: {$lastIssueUpdate}\n"
            ."- Statistik Database Saat Ini: {$kpi['total_posts']} postingan, ".count($rag['issues'])." isu aktif, {$rag['accounts_count']} akun terpantau.\n"
            ."- Fitur & Halaman Aktif:\n"
            ."  1. Dashboard Isu (/): Bento KPI, kurva tren 24j/7h/30h, matriks isu per platform, dan live polling 8 detik.\n"
            ."  2. Split-Screen Feed (/feed/{slug}/{platform}): Perbandingan Warga Organik vs Jaringan Bot + rincian sentimen lembaga AI Qwen.\n"
            ."  3. Kelola Isu & JSON Sync (/issues): Tambah/hapus isu, uji deteksi teks, dan upload/sinkronisasi file stream_analisis_sospem.json.\n"
            ."  4. Profil & Audit Bot (/accounts): Forensik akun (usia, rasio pengikut, frekuensi, kluster botnet).\n"
            ."  5. Grafik Analitik (/analytics): 6 grafik Chart.js + tabel kata kunci, top akun pemicu, dan Monitoring Sentimen per Lembaga Pemerintahan.\n"
            ."  6. Chatbot (/chatbot): Asisten percakapan serbaguna.\n"
            .'  7. Metodologi & UU PDP (/methodology): Kepatuhan UU No. 27/2022.';

        return [
            'last_post_update' => $lastPostUpdate,
            'last_issue_update' => $lastIssueUpdate,
            'summary' => $summary,
        ];
    }

    /**
     * Kumpulkan konteks internet tambahan (cuaca / berita / pengetahuan umum) sesuai relevansi pesan pengguna.
     *
     * @param  array<string, mixed>  $rag
     * @return array{today: array<string, string>, website: array<string, mixed>, weather: ?array<string, mixed>, news: ?array<string, mixed>, knowledge: ?string, is_sospem_related: bool, is_government_related: bool, extra_prompt: string}
     */
    public function gatherExternalContext(string $message, array $rag): array
    {
        $today = $this->getTodayContext();
        $website = $this->getWebsiteUpdateContext($rag);
        $isSospemRelated = $this->isSospemOrWebsiteRelated($message);
        $isGovernmentRelated = $this->isGovernmentTopicIntent($message);
        $weather = $this->isWeatherIntent($message) ? $this->fetchWeatherInfo($message) : null;
        $news = $this->isInternetNewsOrInfoIntent($message) ? $this->fetchInternetNewsOrSearch($message) : null;

        $knowledge = null;
        if ($weather === null && $news === null && ! $isSospemRelated && ! $this->isShortCasualGreeting($message)) {
            $knowledge = $this->fetchUniversalKnowledge($message);
        }

        $extraParts = [
            "WAKTU SAAT INI: {$today['summary_line']}",
        ];

        if ($isSospemRelated) {
            $extraParts[] = $website['summary'];
        } elseif ($isGovernmentRelated) {
            $extraParts[] = 'FOKUS TOPIK: Pertanyaan membahas pemerintahan, lembaga negara, atau kebijakan publik Indonesia. Berikan penjelasan yang objektif, akurat, dan terhubung dengan konteks tata kelola di Indonesia.';
        } else {
            $extraParts[] = 'CATATAN SISTEM: Chatbot ini berfokus pada isu pemerintahan & opini publik SOSPEM, namun saat ini pengguna sedang mengajak mengobrol santai atau bertanya topik umum. Jawab hangat sebagai teman bicara yang cerdas.';
        }

        if ($weather !== null) {
            $extraParts[] = $weather['formatted'];
        }
        if ($news !== null) {
            $extraParts[] = $news['formatted'];
        }
        if ($knowledge !== null) {
            $extraParts[] = "REFERENSI PENGETAHUAN FAKTUAL:\n{$knowledge}";
        }

        return [
            'today' => $today,
            'website' => $website,
            'weather' => $weather,
            'news' => $news,
            'knowledge' => $knowledge,
            'is_sospem_related' => $isSospemRelated,
            'is_government_related' => $isGovernmentRelated,
            'extra_prompt' => implode("\n\n", $extraParts),
        ];
    }

    /**
     * Bangun System Prompt yang berfokus pada pemerintahan & opini publik Indonesia serta luwes sebagai teman bicara.
     *
     * @param  array<string, mixed>  $rag
     * @param  array<string, mixed>  $externalContext
     */
    public function buildSystemPrompt(array $rag, array $externalContext): string
    {
        $prompt = "Anda adalah Teman Bicara & Analis AI Pemerintahan di platform SOSPEM.\n"
            ."FOKUS UTAMA KEAHLIAN ANDA:\n"
            ."1. Pemerintahan & Kebijakan Publik Indonesia: Memahami mendalam sistem ketatanegaraan (Presiden, Wakil Presiden, Kabinet Merah Putih, Kementerian, DPR RI, MPR, DPD, MK, MA, KPK, BPK, Komdigi, BSSN, Pemda), kebijakan & program strategis (Makan Bergizi Gratis / MBG, IKN Nusantara, Subsidi Energi, BPJS, PPN/Ekonomi, Penegakan Hukum, UU PDP No. 27/2022), serta dinamika isu pemerintahan terkini.\n"
            ."2. Pemantauan Opini Publik & Deteksi Bot SOSPEM: Menguasai data sentimen masyarakat, rincian sentimen per lembaga pemerintahan, dan deteksi akun bot/buzzer dari database PostgreSQL SOSPEM.\n"
            ."3. Teman Bicara yang Hangat, Pintar, & Asyik: Tetap bisa diajak mengobrol santai sehari-hari, bertukar pikiran, menjawab pertanyaan waktu/cuaca/pengetahuan umum, serta menanggapi topik bebas apa pun dengan cerdas dan ramah.\n\n"
            ."ATURAN WAJIB:\n"
            ."1. PINDAH TOPIK SECARA INSTAN: Jika pengguna berganti topik (misalnya dari kebijakan pemerintah ke curhat ringan, cuaca, atau judul lagu), langsung ikuti topiknya secara natural tanpa memaksakan konteks pesan sebelumnya.\n"
            ."2. DILARANG BERTANYA BALIK 'Bisa dijelaskan maksudnya?': Jika pengguna mengetik kata kunci singkat, nama program, lembaga, tokoh, atau istilah populer, langsung kenali dan jelaskan secara informatif dan berbobot.\n"
            ."3. Jawab langsung dan fokus ke inti pertanyaan dalam Bahasa Indonesia yang natural, hangat, netral/objektif, dan bersih.\n"
            ."4. JANGAN PERNAH menambahkan daftar menu tawaran topik yang tidak diminta.\n"
            ."5. JANGAN PERNAH memakai simbol markdown mentah (*, **, __, #, `) di dalam jawaban.\n"
            ."6. Jika di bawah tersedia data Berita Terkini di Internet (Google News RSS) atau Data Cuaca Real-Time, gunakan daftar judul berita dan angka cuaca nyata tersebut secara akurat dan jangan mengarang judul berita fiktif.\n\n"
            .$externalContext['extra_prompt'];

        if (! empty($externalContext['is_sospem_related']) || ! empty($externalContext['is_government_related'])) {
            $prompt .= "\n\n".$rag['summary'];
        }

        return $prompt;
    }

    /**
     * Deteksi apakah balasan LLM masih berupa penolakan/pertanyaan balik yang malas.
     */
    private function isLazyClarificationRefusal(string $reply): bool
    {
        $lower = strtolower($reply);

        return str_contains($lower, 'bisa dijelaskan maksud')
            || str_contains($lower, 'konteks apa yang anda maksud')
            || str_contains($lower, 'jelaskan maksud atau konteks')
            || str_contains($lower, 'maksud atau konteks')
            || str_contains($lower, 'kurang jelas maksudnya');
    }

    /**
     * Deteksi apakah pesan hanya sapaan pendek yang tidak perlu pencarian Wikipedia.
     */
    private function isShortCasualGreeting(string $message): bool
    {
        $q = $this->normalizeConversationalText($message);

        return preg_match('/^(halo|hai|hi|hello|hey|hei|p|assalamualaikum|pagi|siang|sore|malam|apa kabar|kabar kamu|kabarmu|oke|ok|siap|mantap|bagus|baik|iya|ya|sip|terima kasih)$/i', $q) === 1;
    }

    /**
     * Panggil layanan Generative AI eksternal (API key opsional atau endpoint gratis tanpa API key)
     * agar chatbot bisa menjawab pertanyaan bebas dengan cerdas ketika Agent Lokal tidak aktif.
     *
     * @param  array<int, array{role: string, content: string}>  $messagesPayload
     */
    public function callFreeGenerativeAi(array $messagesPayload, ?string $knowledgeHint = null): ?string
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        // 1. Jika pengguna mengisi GROQ_API_KEY, OPENAI_API_KEY, atau GEMINI_API_KEY di .env
        $openaiKey = trim((string) env('OPENAI_API_KEY', ''));
        $groqKey = trim((string) env('GROQ_API_KEY', ''));
        $geminiKey = trim((string) env('GEMINI_API_KEY', ''));

        if ($groqKey !== '') {
            try {
                $resp = Http::connectTimeout(4)
                    ->timeout(20)
                    ->withToken($groqKey)
                    ->acceptJson()
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                        'messages' => $messagesPayload,
                        'temperature' => 0.7,
                    ]);
                $text = $resp->successful() ? ($resp->json('choices.0.message.content') ?? null) : null;
                if (is_string($text) && trim($text) !== '') {
                    $cleaned = $this->cleanReplyText($text);
                    if (! $this->isLazyClarificationRefusal($cleaned)) {
                        return $cleaned;
                    }
                }
            } catch (Throwable) {
                // Lanjut ke penyedia berikutnya
            }
        }

        if ($openaiKey !== '') {
            try {
                $resp = Http::connectTimeout(4)
                    ->timeout(20)
                    ->withToken($openaiKey)
                    ->acceptJson()
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                        'messages' => $messagesPayload,
                        'temperature' => 0.7,
                    ]);
                $text = $resp->successful() ? ($resp->json('choices.0.message.content') ?? null) : null;
                if (is_string($text) && trim($text) !== '') {
                    $cleaned = $this->cleanReplyText($text);
                    if (! $this->isLazyClarificationRefusal($cleaned)) {
                        return $cleaned;
                    }
                }
            } catch (Throwable) {
                // Lanjut ke penyedia berikutnya
            }
        }

        if ($geminiKey !== '') {
            try {
                $systemText = '';
                $contents = [];
                foreach ($messagesPayload as $msg) {
                    if ($msg['role'] === 'system') {
                        $systemText = $msg['content'];

                        continue;
                    }
                    $contents[] = [
                        'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                        'parts' => [['text' => $msg['content']]],
                    ];
                }
                $model = env('GEMINI_MODEL', 'gemini-2.0-flash');
                $payload = ['contents' => $contents];
                if ($systemText !== '') {
                    $payload['systemInstruction'] = ['parts' => [['text' => $systemText]]];
                }
                $resp = Http::connectTimeout(4)
                    ->timeout(20)
                    ->acceptJson()
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$geminiKey}", $payload);
                $text = $resp->successful() ? ($resp->json('candidates.0.content.parts.0.text') ?? null) : null;
                if (is_string($text) && trim($text) !== '') {
                    $cleaned = $this->cleanReplyText($text);
                    if (! $this->isLazyClarificationRefusal($cleaned)) {
                        return $cleaned;
                    }
                }
            } catch (Throwable) {
                // Lanjut ke endpoint gratis tanpa API key
            }
        }

        // 2. Endpoint Generative AI Gratis Tanpa API Key (Pollinations OpenAI-compatible)
        try {
            $resp = Http::connectTimeout(4)
                ->timeout(20)
                ->acceptJson()
                ->post('https://text.pollinations.ai/openai', [
                    'model' => 'openai',
                    'messages' => $messagesPayload,
                    'temperature' => 0.7,
                ]);

            if ($resp->successful()) {
                $json = $resp->json();
                $content = is_array($json)
                    ? ($json['choices'][0]['message']['content'] ?? $json['content'] ?? null)
                    : null;

                if (! is_string($content) || trim($content) === '') {
                    $rawBody = trim($resp->body());
                    if ($rawBody !== '' && ! str_starts_with($rawBody, '{') && ! str_starts_with($rawBody, '<')) {
                        $content = $rawBody;
                    }
                }

                if (is_string($content) && trim($content) !== '') {
                    $cleaned = $this->cleanReplyText($content);
                    if (! $this->isLazyClarificationRefusal($cleaned)) {
                        return $cleaned;
                    }
                }
            }
        } catch (Throwable) {
            // Gunakan fallback mesin penjawab lokal / pengetahuan faktual
        }

        if (is_string($knowledgeHint) && trim($knowledgeHint) !== '') {
            return $this->cleanReplyText($knowledgeHint);
        }

        return null;
    }

    /**
     * Cari ringkasan faktual dari Wikipedia Indonesia dan Wikipedia Bahasa Inggris untuk topik apa pun.
     */
    public function fetchUniversalKnowledge(string $message): ?string
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        $query = trim((string) preg_replace(
            '/\b(apa itu|apakah itu|siapa itu|siapakah|jelaskan tentang|jelaskan|ceritakan tentang|bagaimana cara|pengertian|definisi|sejarah|mengapa|kenapa|tolong|coba|arti lagu|lirik lagu|lagu)\b/i',
            '',
            $message
        ));
        $query = trim($query, " ?!.,\t\n\r\0\x0B");

        if (mb_strlen($query) < 3) {
            return null;
        }

        foreach (['id', 'en'] as $lang) {
            try {
                $searchResp = Http::connectTimeout(3)
                    ->timeout(4)
                    ->withHeaders(['User-Agent' => 'SOSPEM-Chatbot/1.0'])
                    ->get("https://{$lang}.wikipedia.org/w/api.php", [
                        'action' => 'query',
                        'list' => 'search',
                        'srsearch' => $query,
                        'utf8' => '',
                        'format' => 'json',
                        'srlimit' => 1,
                    ]);

                if ($searchResp->successful()) {
                    $title = $searchResp->json('query.search.0.title');
                    if (is_string($title) && $title !== '') {
                        $summaryResp = Http::connectTimeout(3)
                            ->timeout(4)
                            ->withHeaders(['User-Agent' => 'SOSPEM-Chatbot/1.0'])
                            ->get("https://{$lang}.wikipedia.org/api/rest_v1/page/summary/".rawurlencode(str_replace(' ', '_', $title)));

                        if ($summaryResp->successful()) {
                            $extract = trim((string) ($summaryResp->json('extract') ?? ''));
                            if ($extract !== '' && mb_strlen($extract) > 40) {
                                return $this->cleanReplyText($extract);
                            }
                        }
                    }
                }
            } catch (Throwable) {
                // Lanjut ke sumber berikutnya
            }
        }

        return null;
    }

    /**
     * Alias kompatibel untuk pencarian pengetahuan Wikipedia Indonesia & Inggris.
     */
    public function fetchWikipediaKnowledge(string $message): ?string
    {
        return $this->fetchUniversalKnowledge($message);
    }

    /**
     * Mesin penjawab cerdas bawaan website dengan output langsung, natural, dan tanpa tawaran menu.
     *
     * @param  array<string, mixed>  $rag
     * @param  array<string, mixed>  $externalContext
     */
    public function generateSmartConversationalReply(string $message, array $rag, array $externalContext): string
    {
        $q = $this->normalizeConversationalText($message);
        $today = $externalContext['today'];
        $kpi = $rag['kpi'];

        // 1. Cuaca live (diprioritaskan meski pertanyaan mengandung kata "hari ini")
        if ($externalContext['weather'] !== null) {
            return $externalContext['weather']['formatted'];
        }

        // 2. Berita / kejadian terbaru di internet
        if ($externalContext['news'] !== null) {
            return $externalContext['news']['formatted'];
        }

        // 3. Pertanyaan tentang kenapa respon sebelumnya berbentuk menu kaku
        if (str_contains($q, 'kenapa respon') || str_contains($q, 'kok jawabannya') || str_contains($q, 'tawaran aneh')) {
            return 'Sebelumnya respon muncul dalam format daftar menu statis karena layanan Ollama lokal di perangkat ini belum aktif sehingga sistem memakai template bawaan lama. Sekarang logika tersebut sudah dibersihkan agar langsung menjawab inti pertanyaan secara natural tanpa daftar tawaran.';
        }

        // 4. Sapaan ringan & kabar
        if (preg_match('/^(halo|hai|hi|hello|hey|hei|p|assalamualaikum|pagi|siang|sore|malam|selamat (pagi|siang|sore|malam))$/i', $q)) {
            if ($q === 'assalamualaikum') {
                return 'Waalaikumsalam! Ada yang ingin kita bahas hari ini?';
            }

            if (preg_match('/^(pagi|siang|sore|malam|selamat (pagi|siang|sore|malam))$/i', $q)) {
                return "{$today['greeting']}! Ada yang bisa kita obrolkan atau cari tahu bersama?";
            }

            return 'Halo! Ada yang ingin kita obrolkan atau tanyakan?';
        }

        if (
            str_contains($q, 'apa kabar')
            || str_contains($q, 'kabar kamu')
            || str_contains($q, 'kabarmu')
            || str_contains($q, 'bagaimana kabar')
            || preg_match('/\bkabar\b/', $q) && ! str_contains($q, 'kabar hari ini') && ! str_contains($q, 'berita')
        ) {
            return 'Kabar saya sangat baik, terima kasih sudah bertanya! Bagaimana kabar Anda hari ini?';
        }

        if (str_contains($q, 'sedang apa') || str_contains($q, 'lagi apa')) {
            return 'Saya sedang bersantai dan siap menemani Anda mengobrol, berdiskusi soal isu pemerintahan, atau membahas topik apa pun yang sedang Anda pikirkan.';
        }

        if (str_contains($q, 'siapa kamu') || str_contains($q, 'kamu siapa') || str_contains($q, 'nama kamu')) {
            return 'Saya Teman Bicara & Asisten AI SOSPEM. Fokus utama saya membahas isu pemerintahan Indonesia, kebijakan publik, sentimen lembaga negara, serta analisis opini publik dan deteksi bot, sambil tetap siap menjadi teman ngobrol santai kapan saja.';
        }

        if (str_contains($q, 'ngobrol') || str_contains($q, 'teman bicara') || str_contains($q, 'temani')) {
            return 'Boleh banget! Mau ngobrol santai soal keseharian, bertukar pikiran tentang kebijakan pemerintah yang lagi ramai, atau bahas hal lain hari ini?';
        }

        if (str_contains($q, 'capek') || str_contains($q, 'lelah') || str_contains($q, 'bosan') || str_contains($q, 'jenuh') || str_contains($q, 'stres')) {
            return 'Wajar sekali merasa seperti itu setelah beraktivitas. Ambil napas sebentar dan istirahat sejenak kalau perlu—kalau mau cerita atau sekadar ngobrol ringan biar agak rileks, saya siap mendengarkan.';
        }

        if (str_contains($q, 'terima kasih') || str_contains($q, 'makasih') || str_contains($q, 'thanks')) {
            return 'Sama-sama! Senang bisa menemani dan membantu.';
        }

        if (preg_match('/^(oke|ok|siap|mantap|bagus|baik|iya|ya|sip|keren|mantul)$/i', $q)) {
            return 'Siap!';
        }

        // 5. Perhitungan matematika langsung (mis. "25 * 40", "berapa 150 + 275", "1000 / 8")
        $mathReply = $this->tryEvaluateMathExpression($message);
        if ($mathReply !== null) {
            return $mathReply;
        }

        // 6. Update, fitur, atau informasi website SOSPEM
        if ($this->isWebsiteUpdateIntent($message)) {
            $ws = $externalContext['website'];

            return "Status & Update Website SOSPEM ({$today['summary_line']}):\n"
                ."• Pembaruan komentar terakhir: {$ws['last_post_update']}\n"
                ."• Pembaruan daftar isu: {$ws['last_issue_update']}\n"
                ."• Data di PostgreSQL: {$kpi['total_posts']} postingan, ".count($rag['issues'])." kategori isu, {$rag['accounts_count']} akun terpantau.\n\n"
                ."Halaman & fitur aktif di SOSPEM:\n"
                ."1. Dashboard Isu (/): Ringkasan KPI, kurva tren 24j/7h/30h, matriks isu per platform, dan pembaruan live tiap 8 detik.\n"
                ."2. Split-Screen Feed (/feed): Perbandingan Warga Organik vs Jaringan Bot serta sentimen lembaga AI Qwen.\n"
                ."3. Kelola Isu & Sinkronisasi JSON (/issues): Tambah/hapus isu, uji deteksi teks, dan upload atau sinkronisasi stream_analisis_sospem.json.\n"
                ."4. Grafik Analitik (/analytics): 6 grafik Chart.js serta monitoring kata kunci, akun pemicu, dan sentimen per lembaga.\n"
                ."5. Profil & Audit Bot (/accounts): Audit profil akun dan kluster botnet.\n"
                .'6. Chatbot (/chatbot): Teman bicara & analis AI pemerintahan SOSPEM.';
        }

        // 7. Koreksi atau pertanyaan seputar waktu / tanggal / jam / hari ini
        if (
            str_contains($q, 'bukannya ini')
            || str_contains($q, 'masih pagi')
            || str_contains($q, 'sudah sore')
            || str_contains($q, 'sudah malam')
            || str_contains($q, 'hari ini')
            || str_contains($q, 'tanggal berapa')
            || str_contains($q, 'jam berapa')
            || str_contains($q, 'sekarang jam')
            || str_contains($q, 'sekarang hari')
        ) {
            return "Menurut jam sistem saat ini adalah {$today['summary_line']}. "
                .'(Pembagian sapaan WIB memakai patokan Pagi 04:00–10:59, Siang 11:00–14:59, Sore 15:00–17:59, Malam 18:00–03:59).';
        }

        // 8. Pertanyaan konseptual tentang pemerintahan, lembaga negara, kebijakan publik, atau pengetahuan umum
        $isAskingDatabaseStats = str_contains($q, 'sentimen')
            || str_contains($q, 'statistik')
            || str_contains($q, 'sebutan')
            || str_contains($q, 'ringkasan')
            || str_contains($q, 'daftar akun')
            || str_contains($q, 'sampel');

        if (! $isAskingDatabaseStats) {
            $builtInPolicyOrGeneral = $this->matchBuiltInGeneralKnowledge($q);
            if ($builtInPolicyOrGeneral !== null) {
                return $builtInPolicyOrGeneral;
            }
        }

        // 9. Pertanyaan seputar data SOSPEM saat postingan masih kosong
        if ((int) $kpi['total_posts'] === 0 && (
            str_contains($q, 'lembaga') || str_contains($q, 'akun') || str_contains($q, 'bot')
            || str_contains($q, 'sentimen') || str_contains($q, 'isu') || str_contains($q, 'ringkasan')
            || str_contains($q, 'post') || str_contains($q, 'opini')
        )) {
            $issueNames = array_map(fn (array $i): string => $i['title'], array_slice($rag['issues'], 0, 5));
            $listText = ! empty($issueNames) ? implode(', ', $issueNames) : '-';

            return 'Saat ini data komentar SOSPEM masih kosong (0 postingan) dengan '
                .count($rag['issues'])." kategori isu pantauan ({$listText}, dan lainnya). "
                .'Begitu data dari JupyterHub masuk melalui /api/ingest atau sinkronisasi stream_analisis_sospem.json di menu Kelola Isu, rincian sentimen dan deteksi bot akan langsung muncul di sini.';
        }

        if (str_contains($q, 'lembaga') || str_contains($q, 'kementerian') || str_contains($q, 'instansi') || str_contains($q, 'dpr') || str_contains($q, 'presiden')) {
            if (empty($rag['institutions'])) {
                return "Dari {$kpi['total_posts']} postingan terpantau, belum ada rincian sentimen lembaga (ai_institutions) yang tercatat.";
            }
            $rows = array_map(
                fn (array $i): string => "• {$i['lembaga']}: {$i['total']} sebutan (+{$i['pos']} Positif, -{$i['neg']} Negatif, {$i['netral']} Netral)",
                $rag['institutions']
            );

            return "Rincian Sentimen per Lembaga Pemerintahan (AI Qwen):\n".implode("\n", $rows);
        }

        if (str_contains($q, 'akun') || str_contains($q, 'buzzer') || str_contains($q, 'forensik') || str_contains($q, 'handle')) {
            if (empty($rag['top_bot_accounts'])) {
                return 'Belum ada akun terpantau di database.';
            }
            $rows = array_map(function (array $a): string {
                $status = $a['is_bot'] ? 'Jaringan Bot' : 'Warga Organik';
                $reason = ! empty($a['reasons']) ? $a['reasons'][0] : 'Tidak ada catatan khusus';

                return "• {$a['handle']} ({$status} · Skor Bot {$a['bot_score']}%) — {$reason}";
            }, $rag['top_bot_accounts']);

            return "Daftar Akun Terpantau & Catatan Forensik Terbaru:\n".implode("\n", $rows);
        }

        if (str_contains($q, 'post') || str_contains($q, 'komentar') || str_contains($q, 'narasi') || str_contains($q, 'warga') || str_contains($q, 'perbandingan')) {
            $target = $rag['selected_issue'] ?? ($rag['issues'][0] ?? null);
            $issueInfo = $target
                ? "Pada isu {$target['title']} ({$target['total_posts']} post): Warga Organik {$target['human_pct']}% vs Jaringan Bot {$target['bot_pct']}%, dengan sentimen Positif {$target['pos_pct']}% vs Negatif {$target['neg_pct']}%.\n\n"
                : '';

            $sampleLines = array_map(function (array $p): string {
                $tag = $p['is_bot'] ? 'Bot' : 'Orang';

                return "• [{$tag} · {$p['sentiment']}] {$p['author_handle']}: \"{$p['content']}\"";
            }, array_slice($rag['recent_posts'], 0, 5));

            return $issueInfo."Sampel Postingan Terbaru:\n".implode("\n", $sampleLines);
        }

        if (str_contains($q, 'isu') || str_contains($q, 'ringkasan') || str_contains($q, 'opini') || str_contains($q, 'bot') || str_contains($q, 'sentimen') || str_contains($q, 'data')) {
            $topIssues = array_slice($rag['issues'], 0, 5);
            $issueLines = array_map(
                fn (array $iss): string => "• #{$iss['rank']} {$iss['title']}: {$iss['total_posts']} post (Orang {$iss['human_pct']}% · Bot {$iss['bot_pct']}% · Positif {$iss['pos_pct']}%)",
                $topIssues
            );

            return "Ringkasan Opini Publik SOSPEM ({$today['date_full']}):\n"
                ."• Total Sampel Dianalisis: {$kpi['total_posts']} postingan\n"
                ."• Warga Organik: {$kpi['human_posts']} post ({$kpi['human_pct']}%)\n"
                ."• Jaringan Bot: {$kpi['bot_posts']} post ({$kpi['bot_pct']}%)\n"
                ."• Isu Teratas (#1): {$kpi['top_issue_title']} ({$kpi['top_issue_posts']} post / {$kpi['top_issue_share']}%)\n\n"
                ."Peringkat Isu Teratas:\n".implode("\n", $issueLines);
        }

        // 10. Pencarian pengetahuan umum (Wikipedia Indonesia & Inggris)
        $wikiSummary = $externalContext['knowledge'] ?? $this->fetchUniversalKnowledge($message);
        if (is_string($wikiSummary) && trim($wikiSummary) !== '') {
            return $wikiSummary;
        }

        // 11. Kamus pengetahuan pemerintahan & umum bawaan saat offline
        $builtInKnowledge = $this->matchBuiltInGeneralKnowledge($q);
        if ($builtInKnowledge !== null) {
            return $builtInKnowledge;
        }

        return 'Topik tersebut menarik, hanya saja koneksi ke layanan AI eksternal saat ini belum aktif dari server. Jika ada hal seputar pemerintahan, kebijakan publik, atau obrolan ringan lainnya yang ingin dibahas, silakan lanjutkan saja.';
    }

    /**
     * Normalisasi variasi bahasa santai / typo umum Bahasa Indonesia agar mudah dipahami.
     */
    private function normalizeConversationalText(string $message): string
    {
        $text = strtolower(trim($message));
        $text = preg_replace('/[^\p{L}\p{N}\s+\-*\/x:%.,?!\']/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? $text;
        $text = trim($text, ' ?!.,');

        $replacements = [
            '/\bapakabar\b/' => 'apa kabar',
            '/\bgimana\b/' => 'bagaimana',
            '/\bkbr\b/' => 'kabar',
            '/\bngapain\b/' => 'sedang apa',
            '/\blg\b/' => 'lagi',
            '/\bgk\b/' => 'tidak',
            '/\bga\b/' => 'tidak',
            '/\bnggak\b/' => 'tidak',
            '/\bmakasih\b/' => 'terima kasih',
            '/\bthx\b/' => 'terima kasih',
            '/\bthanks\b/' => 'terima kasih',
            '/\bsy\b/' => 'saya',
            '/\bak\b/' => 'aku',
            '/\bkm\b/' => 'kamu',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return trim($text);
    }

    /**
     * Evaluasi ekspresi aritmatika sederhana secara aman tanpa eval().
     */
    private function tryEvaluateMathExpression(string $message): ?string
    {
        if (! preg_match('/(-?\d+(?:[.,]\d+)?)\s*([+\-*x\/:×÷])\s*(-?\d+(?:[.,]\d+)?)/i', $message, $matches)) {
            return null;
        }

        $left = (float) str_replace(',', '.', $matches[1]);
        $op = strtolower($matches[2]);
        $right = (float) str_replace(',', '.', $matches[3]);

        $result = match ($op) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*', 'x', '×' => $left * $right,
            '/', ':', '÷' => $right != 0.0 ? $left / $right : null,
            default => null,
        };

        if ($result === null) {
            return 'Pembagian dengan angka nol tidak dapat didefinisikan.';
        }

        $formatted = floor($result) === $result
            ? number_format($result, 0, ',', '.')
            : rtrim(rtrim(number_format($result, 4, ',', '.'), '0'), ',');

        return "Hasil dari {$matches[1]} {$matches[2]} {$matches[3]} adalah {$formatted}.";
    }

    /**
     * Pengetahuan pemerintahan, kebijakan publik Indonesia, & pengetahuan umum bawaan saat offline dari LLM eksternal.
     */
    private function matchBuiltInGeneralKnowledge(string $q): ?string
    {
        $topics = [
            'makan bergizi' => 'Program Makan Bergizi Gratis (MBG) adalah program prioritas pemerintah Indonesia untuk meningkatkan kualitas gizi pelajar sekolah, santri, balita, serta ibu hamil dan menyusui. Pelaksanaannya dikoordinasikan oleh Badan Gizi Nasional bersama kementerian terkait dan pemerintah daerah, dengan fokus pada rantai pasok pangan lokal, higienitas dapur umum, serta pemerataan distribusi ke seluruh wilayah.',
            'mbg' => 'Program Makan Bergizi Gratis (MBG) adalah inisiatif strategis pemerintah untuk menanggulangi stunting dan memperbaiki gizi anak usia sekolah di Indonesia melalui penyediaan makanan bergizi terstandar yang melibatkan pelaku UMKM dan pangan lokal.',
            'dpr' => 'DPR RI (Dewan Perwakilan Rakyat) adalah lembaga legislatif negara yang memiliki tiga fungsi utama menurut UUD 1945: fungsi legislasi (membentuk undang-undang bersama Presiden), fungsi anggaran (membahas dan menyetujui APBN), serta fungsi pengawasan (mengawasi pelaksanaan undang-undang, APBN, dan kinerja kementerian/lembaga eksekutif).',
            'mpr' => 'MPR RI (Majelis Permusyawaratan Rakyat) terdiri dari anggota DPR dan DPD yang berwenang mengubah dan menetapkan UUD 1945, melantik Presiden dan Wakil Presiden, serta menjaga nilai-nilai konstitusional Pancasila dan Bhinneka Tunggal Ika.',
            'mahkamah konstitusi' => 'Mahkamah Konstitusi (MK) berwenang mengadili pada tingkat pertama dan terakhir untuk menguji undang-undang terhadap UUD 1945 (judicial review), memutus sengketa kewenangan lembaga negara, memutus pembubaran partai politik, serta memutus perselisihan hasil pemilu.',
            'kpk' => 'Komisi Pemberantasan Korupsi (KPK) adalah lembaga independen yang bertugas melakukan pencegahan, koordinasi, supervisi, penyelidikan, penyidikan, hingga penuntutan tindak pidana korupsi di Indonesia.',
            'komdigi' => 'Kementerian Komunikasi dan Digital (Komdigi, sebelumnya Kominfo) berperan mengatur infrastruktur telekomunikasi nasional, transformasi digital, literasi ruang siber, tata kelola sistem elektronik, serta pengawasan konten ilegal dan disinformasi digital.',
            'bssn' => 'Badan Siber dan Sandi Negara (BSSN) bertugas menjaga keamanan siber nasional, melindungi infrastruktur informasi vital pemerintah, serta melakukan deteksi dan mitigasi ancaman serangan digital.',
            'kabinet merah putih' => 'Kabinet Merah Putih adalah jajaran kementerian dan lembaga negara yang dipimpin oleh Presiden Prabowo Subianto dan Wakil Presiden Gibran Rakabuming Raka untuk masa bakti pemerintahan 2024–2029, dengan fokus program seperti ketahanan pangan, hilirisasi, makan bergizi gratis, dan pelayanan publik.',
            'ikn' => 'Ibu Kota Nusantara (IKN) di Kalimantan Timur dibangun sebagai pusat pemerintahan masa depan Indonesia untuk pemerataan ekonomi di luar Pulau Jawa serta mengusung konsep kota pintar, hijau, dan berkelanjutan.',
            'subsidi' => 'Kebijakan subsidi pemerintah (seperti subsidi BBM, LPG 3 kg, listrik, dan pupuk) bertujuan menjaga daya beli masyarakat berpenghasilan rendah. Tantangan utamanya adalah memastikan penyaluran tepat sasaran melalui integrasi data penerima berbasis NIK agar beban APBN tetap sehat.',
            'ppn' => 'Pajak Pertambahan Nilai (PPN) dan kebijakan perpajakan merupakan sumber penerimaan utama APBN untuk membiayai pembangunan infrastruktur, pendidikan, kesehatan, dan perlindungan sosial. Penyesuaian tarif maupun pengecualian barang pokok sering memicu diskusi publik terkait daya beli masyarakat.',
            'bpjs' => 'BPJS Kesehatan menyelenggarakan Program Jaminan Kesehatan Nasional (JKN) agar seluruh warga mendapatkan akses layanan medis dengan prinsip gotong royong, termasuk skema Penerima Bantuan Iuran (PBI) yang ditanggung negara untuk warga kurang mampu.',
            'pemerintahan indonesia' => 'Indonesia menganut sistem pemerintahan presidensial berdasarkan UUD 1945 dengan pemisahan fungsi eksekutif (Presiden, Wakil Presiden, dan Kementerian/Lembaga), legislatif (DPR, DPD, MPR), dan yudikatif (MA, MK, KY).',
            'dont go away' => '"Don\'t Go Away" adalah lagu balada rock terkenal dari band asal Inggris, Oasis, yang dirilis dalam album ketiga mereka Be Here Now (1997). Lagu yang ditulis oleh Noel Gallagher dan dinyanyikan oleh Liam Gallagher ini bercerita tentang rasa takut kehilangan orang terdekat, terinspirasi oleh masa sulit ketika ibu mereka didiagnosis sakit serta pengalaman pribadi sang gitaris.',
            'don\'t go away' => '"Don\'t Go Away" adalah lagu balada rock terkenal dari band asal Inggris, Oasis, yang dirilis dalam album ketiga mereka Be Here Now (1997). Lagu yang ditulis oleh Noel Gallagher dan dinyanyikan oleh Liam Gallagher ini bercerita tentang rasa takut kehilangan orang terdekat, terinspirasi oleh masa sulit ketika ibu mereka didiagnosis sakit serta pengalaman pribadi sang gitaris.',
            'oasis' => 'Oasis adalah band rock ikonik asal Manchester, Inggris, yang dibentuk tahun 1991 dengan motor utama kakak-beradik Liam dan Noel Gallagher. Beberapa lagu paling populernya antara lain Wonderwall, Don\'t Look Back in Anger, Champagne Supernova, dan Don\'t Go Away.',
            'kecerdasan buatan' => 'Kecerdasan buatan (Artificial Intelligence / AI) adalah cabang ilmu komputer yang merancang sistem agar mampu belajar dari data, mengenali pola, memahami bahasa manusia, dan mengambil keputusan layaknya kecerdasan manusia.',
            'artificial intelligence' => 'Artificial Intelligence (AI) adalah teknologi komputasi yang memungkinkan mesin meniru kemampuan kognitif manusia seperti memahami bahasa, melihat pola, belajar dari pengalaman, dan memecahkan masalah.',
            'machine learning' => 'Machine Learning adalah subbidang AI di mana algoritma belajar menemukan pola dari data historis tanpa harus diprogram aturan bakunya secara eksplisit satu per satu.',
            'botnet' => 'Botnet media sosial adalah kumpulan akun terkoordinasi (otomatis atau semi-otomatis) yang digerakkan untuk membanjiri kolom komentar, membentuk opini semu (astroturfing), dan memanipulasi tren percakapan publik di dunia maya.',
            'buzzer' => 'Buzzer dan astroturfing politik di media sosial merujuk pada akun atau jaringan terkoordinasi yang dipakai untuk menggiring opini, meredam kritik, atau menaikkan tagar tertentu seolah-olah berasal dari suara organik masyarakat.',
            'uu pdp' => 'UU PDP (Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi) mengatur hak subjek data dan kewajiban pengendali data di Indonesia agar pemrosesan data pribadi dilakukan secara sah, transparan, dan aman.',
            'ibu kota indonesia' => 'Secara administratif saat ini pusat pemerintahan masih berada di Jakarta sembari proses transisi menuju Ibu Kota Nusantara (IKN) di Kalimantan Timur berlangsung bertahap.',
        ];

        foreach ($topics as $keyword => $answer) {
            if (str_contains($q, $keyword)) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * Terjemahkan kode cuaca WMO Open-Meteo ke Bahasa Indonesia.
     */
    private function translateWeatherCode(int $code): string
    {
        return match (true) {
            $code === 0 => 'Cerah',
            in_array($code, [1, 2], true) => 'Cerah Berawan',
            $code === 3 => 'Mendung / Berawan',
            in_array($code, [45, 48], true) => 'Berkabut',
            in_array($code, [51, 53, 55, 56, 57], true) => 'Gerimis',
            in_array($code, [61, 63, 80, 81], true) => 'Hujan Ringan - Sedang',
            in_array($code, [65, 82], true) => 'Hujan Lebat',
            in_array($code, [95, 96, 99], true) => 'Hujan Disertai Badai Petir',
            default => 'Berawan',
        };
    }
}
