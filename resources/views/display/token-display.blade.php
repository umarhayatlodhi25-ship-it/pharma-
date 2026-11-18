@php
    $hospitalDisplayName = trim($profile->account_name ?? '');
    if (empty($hospitalDisplayName) || strtolower($hospitalDisplayName) === 'laravel') {
        $hospitalDisplayName = 'PHARMA SYSTEM';
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $hospitalDisplayName }} — Token Display</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@600;700;800;900&family=Noto+Nastaliq+Urdu:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #060a12;
            --bg-card: #0b1322;
            --border-card: #1e293b;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-main);
            color: #f8fafc;
            overflow: hidden;
            user-select: none;
        }
        .font-num {
            font-family: 'Chakra Petch', monospace, sans-serif;
        }
        .font-urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 1.8;
        }
        /* Glowing Hero Animation */
        @keyframes hero-glow {
            0%, 100% {
                box-shadow: 0 0 35px -5px rgba(20, 184, 166, 0.3), inset 0 0 20px -5px rgba(20, 184, 166, 0.2);
                border-color: rgba(20, 184, 166, 0.6);
            }
            50% {
                box-shadow: 0 0 55px 0px rgba(245, 158, 11, 0.4), inset 0 0 30px -5px rgba(245, 158, 11, 0.2);
                border-color: rgba(245, 158, 11, 0.7);
            }
        }
        .hero-pulse {
            animation: hero-glow 3s infinite ease-in-out;
        }
        /* Recall pulse */
        @keyframes recall-flash {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.85; }
        }
        .recall-badge-anim {
            animation: recall-flash 1.2s infinite ease-in-out;
        }
        /* Ticker Animation */
        .ticker-wrapper {
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
        }
        .ticker-inner {
            display: inline-block;
            padding-left: 100%;
            animation: scroll-ticker 40s linear infinite;
        }
        .ticker-wrapper:hover .ticker-inner {
            animation-play-state: paused;
        }
        @keyframes scroll-ticker {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-100%, 0, 0); }
        }
        /* Custom scrollbar for lists */
        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.6);
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(51, 65, 85, 0.8);
            border-radius: 4px;
        }
    </style>
</head>
<body class="h-screen w-screen flex flex-col justify-between antialiased">

    <!-- AUDIO PERMISSION ACTIVATION MODAL / OVERLAY -->
    <div id="audioUnlockOverlay" class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center cursor-pointer transition-all duration-300">
        <div class="bg-gradient-to-b from-slate-900 to-slate-950 border border-teal-500/50 p-8 rounded-3xl max-w-md shadow-2xl space-y-4">
            <div class="w-18 h-18 rounded-full bg-teal-500/20 text-teal-400 text-3xl mx-auto flex items-center justify-center animate-bounce shadow-lg shadow-teal-500/20">
                <i class="fa-solid fa-volume-high"></i>
            </div>
            <h2 class="text-2xl font-black text-white tracking-wide">CLICK TO ACTIVATE SOUND</h2>
            <p class="text-xs text-slate-300 leading-relaxed font-urdu">
                آواز اور اعلانات سننے کے لیے اسکرین پر کہیں بھی کلک کریں۔
            </p>
            <p class="text-[11px] text-slate-400">
                Browser audio autoplay requires user interaction before voice announcements can play.
            </p>
            <div class="pt-2">
                <button type="button" class="w-full py-3 bg-gradient-to-r from-teal-500 to-emerald-500 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl shadow-lg hover:scale-105 transition">
                    <i class="fa-solid fa-play mr-1.5"></i> START AUDIO DISPLAY / آواز شروع کریں
                </button>
            </div>
        </div>
    </div>

    <!-- 1. TOP HEADER (~10% HEIGHT) -->
    <header class="h-[10vh] min-h-[70px] max-h-[85px] bg-slate-900/90 border-b border-slate-800/80 px-6 flex items-center justify-between backdrop-blur-md shrink-0">
        <!-- Hospital Brand -->
        <div class="flex items-center space-x-3.5">
            @if($profile->hasLogo())
                <img src="{{ $profile->logo_url }}" alt="Logo" class="h-11 w-auto object-contain rounded-lg bg-white/5 p-1 border border-white/10">
            @else
                <div class="w-11 h-11 rounded-xl bg-teal-500/20 border border-teal-500/30 flex items-center justify-center text-teal-400 text-2xl font-black">
                    <i class="fa-solid fa-hospital"></i>
                </div>
            @endif
            <div>
                <h1 class="text-lg md:text-xl font-black tracking-tight text-white uppercase truncate max-w-sm lg:max-w-md">
                    {{ $hospitalDisplayName }}
                </h1>
                <div class="flex items-center space-x-2 text-[11px] font-bold tracking-wider text-teal-400">
                    <span>OPD TOKEN CALLING SYSTEM</span>
                    <span class="text-slate-600">•</span>
                    <span class="text-slate-400 font-urdu">مرکزی ٹوکن ڈسپلے</span>
                </div>
            </div>
        </div>

        <!-- Live Status Indicators (LAN + Internet + Audio Status) -->
        <div class="hidden sm:flex items-center space-x-3 text-xs font-bold">
            <!-- Real Server Status -->
            <div id="lanStatusBadge" class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>SERVER: ONLINE</span>
            </div>

            <!-- Real Internet Status -->
            <div id="internetStatusBadge" class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 border border-slate-700 flex items-center gap-1.5">
                <span id="internetStatusDot" class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span id="internetStatusText">NET: CHECKING</span>
            </div>

            <!-- Voice Controls: Test Buttons & Toggle -->
            <div class="flex items-center space-x-1.5 bg-slate-800/90 p-1 rounded-xl border border-slate-700">
                <!-- Test English -->
                <button type="button" onclick="testVoice('en')" class="px-2 py-1 text-[10px] font-bold bg-slate-700/80 hover:bg-teal-600 text-slate-200 hover:text-white rounded-lg transition" title="Test English Audio Announcement">
                    <i class="fa-solid fa-volume-high mr-1 text-teal-400"></i>EN Test
                </button>

                <!-- Test Urdu -->
                <button type="button" onclick="testVoice('ur')" class="px-2 py-1 text-[10px] font-bold bg-slate-700/80 hover:bg-teal-600 text-slate-200 hover:text-white rounded-lg transition" title="Test Urdu Audio Announcement">
                    <i class="fa-solid fa-volume-high mr-1 text-amber-400"></i>UR Test
                </button>

                <!-- Voice ON/OFF Toggle -->
                <button type="button" id="voiceToggleBtn" onclick="toggleVoice()" class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition flex items-center gap-1 bg-teal-500/20 text-teal-300 border border-teal-500/30">
                    <i id="voiceToggleIcon" class="fa-solid fa-microphone"></i>
                    <span id="voiceToggleLabel">VOICE: ON</span>
                </button>
            </div>
        </div>

        <!-- Live Clock & Date -->
        <div class="text-right pl-3 border-l border-slate-800 shrink-0">
            <div id="liveClockDisplay" class="font-num text-2xl md:text-3xl font-extrabold text-amber-400 tracking-wider">
                --:--:--
            </div>
            <div id="liveDateDisplay" class="text-[10px] md:text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                {{ now()->format('l, d M Y') }}
            </div>
        </div>
    </header>

    <!-- 2. MAIN QUEUE AREA (~78% HEIGHT, 3-COLUMN LAYOUT) -->
    <main class="flex-1 px-5 py-4 grid grid-cols-1 lg:grid-cols-12 gap-5 overflow-hidden min-h-0">
        
        <!-- COLUMN 1: NOW SERVING (HERO AREA, 5 COLS) -->
        <section class="lg:col-span-5 flex flex-col justify-between h-full min-h-0">
            <div id="heroCard" class="h-full bg-gradient-to-b from-slate-900 via-slate-900 to-teal-950/70 border-2 border-teal-500/60 rounded-3xl p-6 flex flex-col justify-between shadow-2xl relative overflow-hidden hero-pulse transition-all duration-300">
                
                <!-- Watermark Icon -->
                <div class="absolute -right-6 -top-6 text-teal-500/5 text-9xl pointer-events-none select-none">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>

                <!-- Top Status Header of Hero -->
                <div class="flex items-center justify-between z-10">
                    <div class="flex items-center space-x-2.5">
                        <span id="heroPingDot" class="w-3.5 h-3.5 rounded-full {{ $activeToken ? 'bg-emerald-400 animate-ping' : 'bg-gray-500' }}"></span>
                        <div>
                            <span id="heroServingTitle" class="text-xs md:text-sm font-black tracking-widest uppercase {{ $activeToken ? 'text-emerald-400' : 'text-slate-400' }}">
                                {{ $activeToken ? 'NOW SERVING' : 'DESK AVAILABLE' }}
                            </span>
                            <span class="text-[10px] text-emerald-400/80 font-urdu block -mt-0.5">
                                {{ $activeToken ? 'زیر معائنہ مریض' : 'فارغ ہے' }}
                            </span>
                        </div>
                    </div>

                    <!-- RECALL BADGE (Prominent Alert when Recalled) -->
                    <div id="recallIndicator" class="{{ ($activeToken && ($activeToken->call_count > 1)) ? '' : 'hidden' }} px-3 py-1 bg-gradient-to-r from-amber-500 to-rose-600 text-white font-black text-xs uppercase tracking-wider rounded-full shadow-lg recall-badge-anim flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>RECALL / دوبارہ طلب</span>
                    </div>

                    <!-- Call Count -->
                    <span id="heroCallBadge" class="px-2.5 py-0.5 rounded-full bg-white/10 text-slate-300 font-bold text-[11px] font-mono">
                        {{ $activeToken ? 'Call #' . $activeToken->call_count : 'Ready' }}
                    </span>
                </div>

                <!-- Giant Central Token Number -->
                <div class="my-auto text-center py-2 z-10">
                    <div class="text-xs md:text-sm font-extrabold text-teal-300/80 uppercase tracking-widest mb-1">
                        PATIENT TOKEN NO / مریض کا ٹوکن نمبر
                    </div>
                    <div id="heroTokenNumber" class="font-num text-7xl sm:text-8xl md:text-9xl font-black text-amber-300 tracking-tight drop-shadow-[0_10px_20px_rgba(245,158,11,0.25)]">
                        {{ $activeToken ? $activeToken->formatted_token_number : '---' }}
                    </div>
                    <div id="heroEmptyMessage" class="{{ $activeToken ? 'hidden' : '' }} text-xs md:text-sm text-slate-400 font-semibold mt-2">
                        Currently no token is being called.
                        <div class="font-urdu text-xs text-slate-500 mt-0.5">اس وقت کوئی ٹوکن کال نہیں ہو رہا</div>
                    </div>
                </div>

                <!-- Doctor & Room Banner Footer of Hero -->
                <div class="bg-slate-950/80 border border-white/10 rounded-2xl p-4 grid grid-cols-2 gap-4 z-10 shadow-inner">
                    <!-- Room -->
                    <div class="border-r border-white/10 pr-3">
                        <div class="text-[11px] font-bold uppercase text-slate-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-door-open text-teal-400"></i>
                            <span>ROOM / کمرہ</span>
                        </div>
                        <div id="heroRoomNumber" class="text-xl md:text-2xl font-black text-white mt-1 truncate">
                            {{ $activeToken ? ($activeToken->room_number ?? $activeToken->doctor?->room_number ?? 'OPD Room') : '—' }}
                        </div>
                    </div>
                    <!-- Doctor -->
                    <div class="pl-1">
                        <div class="text-[11px] font-bold uppercase text-slate-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-user-doctor text-teal-400"></i>
                            <span>DOCTOR / معالج</span>
                        </div>
                        <div id="heroDoctorName" class="text-base md:text-xl font-bold text-teal-200 mt-1 truncate">
                            {{ $activeToken && $activeToken->doctor ? $activeToken->doctor->formatted_name : 'Please Wait' }}
                        </div>
                        <div id="heroDepartment" class="text-[11px] text-slate-400 truncate">
                            {{ $activeToken?->doctor?->specialization ?? 'General OPD' }}
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- COLUMN 2: UPCOMING TOKENS (NEXT 5 WAITING, 4 COLS) -->
        <section class="lg:col-span-4 flex flex-col justify-between h-full min-h-0 bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-400 shrink-0">
                        <i class="fa-solid fa-user-clock text-sm"></i>
                    </div>
                    <div class="truncate">
                        <h2 class="text-xs md:text-sm font-black uppercase text-white tracking-wider">
                            UPCOMING QUEUE
                        </h2>
                        <div class="text-[10px] text-teal-400 font-urdu -mt-0.5">آنے والے مریض</div>
                    </div>
                </div>
                <span id="waitingCountBadge" class="px-2.5 py-1 rounded-lg bg-teal-500/10 text-teal-300 font-mono text-xs font-bold border border-teal-500/20 shrink-0">
                    {{ $waitingTokens->count() }} Waiting
                </span>
            </div>

            <!-- List Container -->
            <div id="upcomingTokensList" class="my-auto space-y-2.5 py-2 overflow-y-auto custom-scroll pr-1">
                @forelse($waitingTokens as $wait)
                    <div class="bg-slate-950/60 border border-slate-800 hover:border-teal-500/40 rounded-xl p-3 flex items-center justify-between transition gap-3">
                        <div class="flex items-center space-x-3 min-w-0 flex-1">
                            <span class="font-num text-xl font-black text-amber-300 bg-slate-900 border border-slate-700 w-12 h-12 rounded-xl flex items-center justify-center shadow-xs shrink-0">
                                {{ $wait->formatted_token_number }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold text-white truncate">
                                    {{ $wait->doctor ? $wait->doctor->formatted_name : 'OPD Doctor' }}
                                </div>
                                <div class="text-xs text-slate-400 truncate">
                                    {{ $wait->doctor?->specialization ?? 'General' }}
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-2.5 py-1 rounded-md bg-teal-500/10 text-teal-300 text-xs font-bold border border-teal-500/20 block">
                                {{ $wait->room_number ?? $wait->doctor?->room_number ?? 'Room 1' }}
                            </span>
                            <span class="text-[10px] text-amber-400/80 font-semibold block mt-0.5">Waiting</span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-500 text-xs">
                        <i class="fa-solid fa-mug-hot text-3xl mb-2 text-slate-600 block"></i>
                        <span>No waiting patients in queue.</span>
                        <div class="font-urdu text-[11px] text-slate-500 mt-1">انتظار گاہ میں کوئی مریض باقی نہیں ہے۔</div>
                    </div>
                @endforelse
            </div>

            <!-- Footer note -->
            <div class="pt-2.5 border-t border-slate-800/80 text-center text-[10px] text-slate-400">
                Next in queue • اپنی باری پر کمرے میں تشریف لائیں
            </div>
        </section>

        <!-- COLUMN 3: PREVIOUSLY CALLED TOKENS (3 COLS) -->
        <section class="lg:col-span-3 flex flex-col justify-between h-full min-h-0 bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                        <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                    </div>
                    <div class="truncate">
                        <h2 class="text-xs md:text-sm font-black uppercase text-white tracking-wider">
                            RECENT CALLS
                        </h2>
                        <div class="text-[10px] text-indigo-300 font-urdu -mt-0.5">پچھلے کال شدہ ٹوکن</div>
                    </div>
                </div>
                <span id="previousCountBadge" class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 font-mono text-xs font-bold border border-slate-700 shrink-0">
                    {{ $previousTokens->count() }}
                </span>
            </div>

            <!-- List Container -->
            <div id="previousTokensList" class="my-auto space-y-2 py-1 overflow-y-auto custom-scroll pr-1">
                @forelse($previousTokens as $prev)
                    @php
                        $st = strtolower($prev->status ?? 'called');
                        $badgeClass = match($st) {
                            'completed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                            'skipped' => 'bg-purple-500/10 text-purple-400 border-purple-500/30',
                            'hold' => 'bg-orange-500/10 text-orange-400 border-orange-500/30',
                            default => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                        };
                        $statusLabel = match($st) {
                            'completed' => 'Done',
                            'skipped' => 'Skipped',
                            'hold' => 'Hold',
                            default => 'Called',
                        };
                    @endphp
                    <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-2.5 flex items-center justify-between gap-2.5">
                        <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                            <span class="font-num text-sm font-black text-slate-300 bg-slate-900 border border-slate-700 w-10 h-10 rounded-lg flex items-center justify-center shrink-0">
                                {{ $prev->formatted_token_number }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold text-slate-200 truncate">
                                    {{ $prev->doctor ? $prev->doctor->formatted_name : 'OPD Doctor' }}
                                </div>
                                <div class="text-[10px] text-slate-400 truncate">
                                    {{ $prev->room_number ?? $prev->doctor?->room_number ?? 'Room' }}
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $badgeClass }}">
                                {{ $statusLabel }}
                            </span>
                            <span class="text-[10px] text-slate-500 block font-mono mt-0.5">
                                {{ format_app_datetime($prev->completed_at ?? $prev->called_at ?? $prev->updated_at, 'h:i A') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-500 text-xs">
                        <i class="fa-solid fa-clipboard-list text-3xl mb-2 text-slate-600 block"></i>
                        <span>No previous token calls.</span>
                        <div class="font-urdu text-[11px] text-slate-500 mt-1">کوئی پچھلا ٹوکن ریکارڈ موجود نہیں ہے۔</div>
                    </div>
                @endforelse
            </div>

            <!-- Footer note -->
            <div class="pt-2.5 border-t border-slate-800/80 text-center text-[10px] text-slate-400">
                Recent consultations history • حالیہ ریکارڈ
            </div>
        </section>

    </main>

    <!-- 3. BOTTOM ANNOUNCEMENT TICKER (~12% HEIGHT) -->
    <footer class="h-[12vh] min-h-[60px] max-h-[75px] bg-slate-950 border-t border-slate-800 flex items-center px-6 shrink-0">
        <div class="px-3.5 py-1.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-slate-950 font-black text-xs uppercase tracking-wider rounded-lg mr-4 shrink-0 shadow-md">
            ANNOUNCEMENT / اعلان
        </div>
        <div class="ticker-wrapper flex-1 overflow-hidden">
            <div id="tickerTextContent" class="ticker-inner text-sm md:text-base font-bold text-slate-200 flex items-center space-x-6">
                <span>Please wait for your token and proceed to your designated doctor room when announced.</span>
                <span class="text-teal-400">★</span>
                <span class="font-urdu text-base md:text-lg text-amber-200" dir="rtl">مہربانی فرما کر اپنے ٹوکن کا انتظار کریں اور اعلان پر متعلقہ ڈاکٹر کے کمرے میں تشریف لے جائیں۔</span>
                <span class="text-teal-400">★</span>
                <span>{{ $profile->lcd_message_ticker ?? 'Please keep your token receipt ready for consultation.' }}</span>
            </div>
        </div>
    </footer>

    <!-- ROBUST REAL-TIME ENGINE + DUAL-LANGUAGE WEB SPEECH ENGINE -->
    <script>
        // State variables
        let audioActivated = false;
        let isVoiceEnabled = true;
        let pollIntervalMs = {{ ($profile->lcd_refresh_interval_sec ?? 3) * 1000 }};
        let pollTimer = null;
        let announcedLogIds = new Set();
        let announcementQueue = [];
        let isSpeaking = false;
        let availableVoices = [];

        // Load announced logs from sessionStorage to prevent repeating upon page reload
        try {
            const saved = sessionStorage.getItem('hospital_announced_token_ids');
            if (saved) {
                JSON.parse(saved).forEach(id => announcedLogIds.add(id));
            }
        } catch (e) {
            console.warn('Storage error:', e);
        }

        // Initialize voices asynchronously (Crucial fix for Chrome/Edge/Windows)
        function initBrowserVoices() {
            if ('speechSynthesis' in window) {
                availableVoices = window.speechSynthesis.getVoices();
            }
        }
        if ('speechSynthesis' in window) {
            initBrowserVoices();
            if (window.speechSynthesis.onvoiceschanged !== undefined) {
                window.speechSynthesis.onvoiceschanged = initBrowserVoices;
            }
        }

        // Web Audio API Synthesized Hospital Tone Chime (E5 -> B5 chord)
        let audioContext = null;
        function playChimeAlert() {
            try {
                if (!audioContext) {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }

                const now = audioContext.currentTime;
                // High tone (E5: 659.25Hz)
                const osc1 = audioContext.createOscillator();
                const gain1 = audioContext.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(659.25, now);
                gain1.gain.setValueAtTime(0.35, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
                osc1.connect(gain1);
                gain1.connect(audioContext.destination);
                osc1.start(now);
                osc1.stop(now + 0.45);

                // Deep bell tone (B5: 987.77Hz)
                const osc2 = audioContext.createOscillator();
                const gain2 = audioContext.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(987.77, now + 0.22);
                gain2.gain.setValueAtTime(0.35, now + 0.22);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.85);
                osc2.connect(gain2);
                gain2.connect(audioContext.destination);
                osc2.start(now + 0.22);
                osc2.stop(now + 0.85);
            } catch (err) {
                console.warn('Web Audio Chime warning:', err);
            }
        }

        // Audio Activation on first click
        function activateAudio() {
            audioActivated = true;
            const overlay = document.getElementById('audioUnlockOverlay');
            if (overlay) {
                overlay.classList.add('opacity-0', 'pointer-events-none');
                setTimeout(() => overlay.classList.add('hidden'), 350);
            }
            playChimeAlert();

            // Warm up speech synthesis
            if ('speechSynthesis' in window) {
                window.speechSynthesis.resume();
            }
        }
        document.getElementById('audioUnlockOverlay').addEventListener('click', activateAudio);
        window.addEventListener('click', () => {
            if (!audioActivated) activateAudio();
        }, { once: true });

        // Voice Toggle
        function toggleVoice() {
            isVoiceEnabled = !isVoiceEnabled;
            const btn = document.getElementById('voiceToggleBtn');
            const icon = document.getElementById('voiceToggleIcon');
            const label = document.getElementById('voiceToggleLabel');

            if (isVoiceEnabled) {
                btn.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg transition flex items-center gap-1 bg-teal-500/20 text-teal-300 border border-teal-500/30';
                icon.className = 'fa-solid fa-microphone';
                label.innerText = 'VOICE: ON';
            } else {
                btn.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg transition flex items-center gap-1 bg-rose-500/20 text-rose-300 border border-rose-500/30';
                icon.className = 'fa-solid fa-microphone-slash';
                label.innerText = 'VOICE: OFF';
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }
                announcementQueue = [];
                isSpeaking = false;
            }
        }

        // Voice Synthesizer Finder
        function findVoice(langPrefix) {
            initBrowserVoices();
            if (!availableVoices || availableVoices.length === 0) return null;

            // 1. Exact match
            let found = availableVoices.find(v => v.lang.toLowerCase() === langPrefix.toLowerCase());
            if (found) return found;

            // 2. Starts with prefix
            found = availableVoices.find(v => v.lang.toLowerCase().startsWith(langPrefix.toLowerCase()));
            if (found) return found;

            // 3. For Urdu fallback to Hindi voices (e.g. hi-IN or Google हिन्दी) which speak perfect phonetic Urdu
            if (langPrefix === 'ur') {
                found = availableVoices.find(v => v.lang.toLowerCase().startsWith('hi') || v.name.toLowerCase().includes('urdu') || v.name.toLowerCase().includes('hindi'));
                if (found) return found;
            }

            return null;
        }

        // Test Voice function
        function testVoice(lang) {
            activateAudio();
            if (!isVoiceEnabled) toggleVoice();

            const testItem = {
                log_id: 'test_' + Date.now(),
                formatted_token_number: 'OPD-002',
                token_number: 2,
                doctor_name: 'Dr. Ahmed',
                room_number: 'Room 1',
                action: 'call',
                testMode: lang
            };

            queueAnnouncement(testItem, true);
        }

        // Queue Announcement Engine
        function queueAnnouncement(item, force = false) {
            if (!isVoiceEnabled) return;
            if (!force && announcedLogIds.has(item.log_id)) return;

            announcedLogIds.add(item.log_id);
            try {
                sessionStorage.setItem('hospital_announced_token_ids', JSON.stringify(Array.from(announcedLogIds).slice(-100)));
            } catch (e) {}

            announcementQueue.push(item);
            processNextAnnouncement();
        }

        function processNextAnnouncement() {
            if (isSpeaking || announcementQueue.length === 0 || !isVoiceEnabled) return;

            const item = announcementQueue.shift();
            isSpeaking = true;

            playChimeAlert();

            setTimeout(() => {
                const synth = window.speechSynthesis;
                if (!synth) {
                    isSpeaking = false;
                    return;
                }

                synth.resume();

                const isRecall = item.action === 'recall';
                const tokenStr = item.formatted_token_number || ('Token ' + item.token_number);
                const roomStr = item.room_number || 'Consultation Room';
                const docStr = item.doctor_name || 'the doctor';

                // English Phrasing
                const enIntro = isRecall ? "Recall announcement." : "Attention please.";
                const enText = `${enIntro} Token ${tokenStr}. Please proceed to ${roomStr}, ${docStr}.`;

                // Urdu Phrasing
                const urIntro = isRecall ? "دوبارہ اعلان۔" : "توجہ فرمائیں۔";
                const urText = `${urIntro} ٹوکن نمبر ${tokenStr}۔ ${docStr} کے ${roomStr} میں تشریف لے جائیں۔`;

                const testMode = item.testMode; // 'en', 'ur', or null (both)

                // Build utterances
                let enUtterance = new SpeechSynthesisUtterance(enText);
                enUtterance.rate = 0.88;
                enUtterance.pitch = 1.0;
                enUtterance.lang = 'en-US';
                const enVoice = findVoice('en-US') || findVoice('en');
                if (enVoice) enUtterance.voice = enVoice;

                let urUtterance = new SpeechSynthesisUtterance(urText);
                urUtterance.rate = 0.82;
                urUtterance.pitch = 1.0;
                urUtterance.lang = 'ur-PK';
                const urVoice = findVoice('ur') || findVoice('hi');
                if (urVoice) urUtterance.voice = urVoice;

                // Watchdog to prevent permanent speech hang in Chrome
                const speechTimeout = setTimeout(() => {
                    if (isSpeaking) {
                        synth.cancel();
                        isSpeaking = false;
                        processNextAnnouncement();
                    }
                }, 10000);

                if (testMode === 'en') {
                    enUtterance.onend = enUtterance.onerror = () => {
                        clearTimeout(speechTimeout);
                        isSpeaking = false;
                    };
                    synth.speak(enUtterance);
                } else if (testMode === 'ur') {
                    urUtterance.onend = urUtterance.onerror = () => {
                        clearTimeout(speechTimeout);
                        isSpeaking = false;
                    };
                    synth.speak(urUtterance);
                } else {
                    // Bilingual announcement: English followed by Urdu
                    enUtterance.onend = () => {
                        setTimeout(() => {
                            if (!isVoiceEnabled) {
                                clearTimeout(speechTimeout);
                                isSpeaking = false;
                                return;
                            }
                            urUtterance.onend = urUtterance.onerror = () => {
                                clearTimeout(speechTimeout);
                                isSpeaking = false;
                                processNextAnnouncement();
                            };
                            synth.speak(urUtterance);
                        }, 400);
                    };
                    enUtterance.onerror = () => {
                        clearTimeout(speechTimeout);
                        isSpeaking = false;
                        processNextAnnouncement();
                    };
                    synth.speak(enUtterance);
                }
            }, 650);
        }

        // Clean doctor name helper for Javascript
        function formatDoctorName(name) {
            if (!name) return 'Attending Doctor';
            const trimmed = name.trim();
            return /^dr\.?\s+/i.test(trimmed) ? trimmed : 'Dr. ' + trimmed;
        }

        // Live Real-Time Polling Engine (Sequential non-blocking poller)
        let isPollingQueue = false;

        async function fetchLiveQueueData() {
            if (isPollingQueue) return;
            isPollingQueue = true;

            try {
                let response = null;
                try {
                    response = await fetch('/token-display/live', { cache: 'no-store' });
                } catch (e1) {
                    // Fallback to /api/token-display/live if route differs
                    response = await fetch('/api/token-display/live', { cache: 'no-store' });
                }

                if (!response || !response.ok) {
                    throw new Error('HTTP ' + (response ? response.status : 'no response'));
                }

                const data = await response.json();

                // 1. Server Status: Online
                const lanBadge = document.getElementById('lanStatusBadge');
                if (lanBadge) {
                    lanBadge.className = 'px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5';
                    lanBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span><span>SERVER: ONLINE</span>';
                }

                // 2. Server Clock
                if (data.server_time) {
                    document.getElementById('liveClockDisplay').innerText = data.server_time;
                }
                if (data.server_date) {
                    document.getElementById('liveDateDisplay').innerText = data.server_date;
                }

                // 3. Update Currently Serving Hero
                const active = data.active_token;
                const heroCard = document.getElementById('heroCard');
                const heroToken = document.getElementById('heroTokenNumber');
                const heroEmpty = document.getElementById('heroEmptyMessage');
                const heroTitle = document.getElementById('heroServingTitle');
                const heroRoom = document.getElementById('heroRoomNumber');
                const heroDoc = document.getElementById('heroDoctorName');
                const heroDept = document.getElementById('heroDepartment');
                const heroCall = document.getElementById('heroCallBadge');
                const heroPing = document.getElementById('heroPingDot');
                const recallInd = document.getElementById('recallIndicator');

                if (active) {
                    heroToken.innerText = active.formatted_token_number;
                    heroEmpty.classList.add('hidden');
                    heroTitle.innerText = active.status === 'in_consultation' ? 'NOW SERVING' : 'NOW CALLING';
                    heroTitle.className = 'text-xs md:text-sm font-black tracking-widest uppercase text-emerald-400';
                    heroRoom.innerText = active.room_number || 'Consultation Desk';
                    heroDoc.innerText = formatDoctorName(active.doctor_name);
                    heroDept.innerText = active.department || 'General OPD';
                    heroCall.innerText = `Call #${active.call_count || 1}`;
                    heroPing.className = 'w-3.5 h-3.5 rounded-full bg-emerald-400 animate-ping';
                    heroCard.classList.add('hero-pulse');

                    if (active.is_recall) {
                        recallInd.classList.remove('hidden');
                    } else {
                        recallInd.classList.add('hidden');
                    }
                } else {
                    heroToken.innerText = '---';
                    heroEmpty.classList.remove('hidden');
                    heroTitle.innerText = 'DESK AVAILABLE';
                    heroTitle.className = 'text-xs md:text-sm font-black tracking-widest uppercase text-slate-400';
                    heroRoom.innerText = '—';
                    heroDoc.innerText = 'Please Wait';
                    heroDept.innerText = 'General OPD';
                    heroCall.innerText = 'Ready';
                    heroPing.className = 'w-3.5 h-3.5 rounded-full bg-slate-500';
                    heroCard.classList.remove('hero-pulse');
                    recallInd.classList.add('hidden');
                }

                // 4. Update Upcoming Waiting Tokens List
                const waiting = data.waiting_tokens || [];
                const waitBadge = document.getElementById('waitingCountBadge');
                if (waitBadge) waitBadge.innerText = `${data.total_waiting || waiting.length} Waiting`;
                const waitList = document.getElementById('upcomingTokensList');

                if (waitList) {
                    if (waiting.length > 0) {
                        let waitHtml = '';
                        waiting.forEach(w => {
                            const docName = formatDoctorName(w.doctor_name);
                            waitHtml += `
                                <div class="bg-slate-950/60 border border-slate-800 hover:border-teal-500/40 rounded-xl p-3 flex items-center justify-between transition gap-3">
                                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                                        <span class="font-num text-xl font-black text-amber-300 bg-slate-900 border border-slate-700 w-12 h-12 rounded-xl flex items-center justify-center shadow-xs shrink-0">
                                            ${w.formatted_token_number}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-bold text-white truncate">${docName}</div>
                                            <div class="text-xs text-slate-400 truncate">${w.department || 'General'}</div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="px-2.5 py-1 rounded-md bg-teal-500/10 text-teal-300 text-xs font-bold border border-teal-500/20 block">
                                            ${w.room_number || 'Room 1'}
                                        </span>
                                        <span class="text-[10px] text-amber-400/80 font-semibold block mt-0.5">Waiting</span>
                                    </div>
                                </div>
                            `;
                        });
                        waitList.innerHTML = waitHtml;
                    } else {
                        waitList.innerHTML = `
                            <div class="py-12 text-center text-slate-500 text-xs">
                                <i class="fa-solid fa-mug-hot text-3xl mb-2 text-slate-600 block"></i>
                                <span>No waiting patients in queue.</span>
                                <div class="font-urdu text-[11px] text-slate-500 mt-1">انتظار گاہ میں کوئی مریض باقی نہیں ہے۔</div>
                            </div>
                        `;
                    }
                }

                // 5. Update Previously Called Tokens List
                const previous = data.previous_tokens || [];
                const prevBadge = document.getElementById('previousCountBadge');
                if (prevBadge) prevBadge.innerText = previous.length;
                const prevList = document.getElementById('previousTokensList');

                if (prevList) {
                    if (previous.length > 0) {
                        let prevHtml = '';
                        previous.forEach(p => {
                            const st = (p.status || '').toLowerCase();
                            let badgeClass = 'bg-blue-500/10 text-blue-400 border-blue-500/30';
                            let label = 'Called';
                            if (st === 'completed') {
                                badgeClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
                                label = 'Done';
                            } else if (st === 'skipped') {
                                badgeClass = 'bg-purple-500/10 text-purple-400 border-purple-500/30';
                                label = 'Skipped';
                            } else if (st === 'hold') {
                                badgeClass = 'bg-orange-500/10 text-orange-400 border-orange-500/30';
                                label = 'Hold';
                            }

                            const docName = formatDoctorName(p.doctor_name);

                            prevHtml += `
                                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-2.5 flex items-center justify-between gap-2.5">
                                    <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                                        <span class="font-num text-sm font-black text-slate-300 bg-slate-900 border border-slate-700 w-10 h-10 rounded-lg flex items-center justify-center shrink-0">
                                            ${p.formatted_token_number}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-200 truncate">${docName}</div>
                                            <div class="text-[10px] text-slate-400 truncate">${p.room_number || 'Room'}</div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border ${badgeClass}">
                                            ${label}
                                        </span>
                                        <span class="text-[10px] text-slate-500 block font-mono mt-0.5">
                                            ${p.time_formatted || '—'}
                                        </span>
                                    </div>
                                </div>
                            `;
                        });
                        prevList.innerHTML = prevHtml;
                    } else {
                        prevList.innerHTML = `
                            <div class="py-12 text-center text-slate-500 text-xs">
                                <i class="fa-solid fa-clipboard-list text-3xl mb-2 text-slate-600 block"></i>
                                <span>No previous token calls.</span>
                                <div class="font-urdu text-[11px] text-slate-500 mt-1">کوئی پچھلا ٹوکن ریکارڈ موجود نہیں ہے۔</div>
                            </div>
                        `;
                    }
                }

                // 6. Check for Voice Announcement Event
                if (data.latest_announcement && audioActivated && isVoiceEnabled) {
                    queueAnnouncement(data.latest_announcement);
                }

            } catch (err) {
                console.warn('Queue poller error:', err);
                const lanBadge = document.getElementById('lanStatusBadge');
                if (lanBadge) {
                    lanBadge.className = 'px-2.5 py-1 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/30 flex items-center gap-1.5';
                    lanBadge.innerHTML = '<span class="w-2 h-2 rounded-full bg-rose-400 animate-ping"></span><span>SERVER: RECONNECTING</span>';
                }
            } finally {
                isPollingQueue = false;
                setTimeout(fetchLiveQueueData, pollIntervalMs);
            }
        }

        // Real Internet Connectivity Monitoring
        function checkInternetConnectivity() {
            const badge = document.getElementById('internetStatusBadge');
            const dot = document.getElementById('internetStatusDot');
            const text = document.getElementById('internetStatusText');

            if (navigator.onLine) {
                badge.className = 'px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5';
                dot.className = 'w-2 h-2 rounded-full bg-emerald-400';
                text.innerText = 'NET: ONLINE';
            } else {
                badge.className = 'px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center gap-1.5';
                dot.className = 'w-2 h-2 rounded-full bg-amber-400';
                text.innerText = 'NET: OFFLINE';
            }
        }
        window.addEventListener('online', checkInternetConnectivity);
        window.addEventListener('offline', checkInternetConnectivity);
        checkInternetConnectivity();

        // Local Clock fallback
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour12: true });
            document.getElementById('liveClockDisplay').innerText = timeStr;
        }, 1000);

        // Start Live Queue Poller (Recursive non-blocking loop)
        fetchLiveQueueData();
    </script>
</body>
</html>
