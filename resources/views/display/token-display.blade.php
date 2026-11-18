<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $profile->account_name ?? 'Hospital' }} — Token Display</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts for Urdu & Clean Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@600;700;800&family=Noto+Nastaliq+Urdu:wght@500;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #090d16;
            color: #f8fafc;
            overflow-x: hidden;
        }
        .font-mono-num {
            font-family: 'Chakra Petch', monospace, sans-serif;
        }
        .font-urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.03); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
        .pulse-card {
            animation: pulse-ring 2.5s infinite ease-in-out;
        }
        .ticker-wrap {
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
        }
        .ticker-content {
            display: inline-block;
            padding-left: 100%;
            animation: ticker 35s linear infinite;
        }
        @keyframes ticker {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-100%, 0, 0); }
        }
    </style>
</head>
<body class="h-screen flex flex-col justify-between selection:bg-teal-500 selection:text-white">

    <!-- AUDIO PERMISSION OVERLAY (Dismisses on first click) -->
    <div id="audioOverlay" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center cursor-pointer transition-opacity duration-300">
        <div class="bg-slate-900 border border-teal-500/40 p-8 rounded-3xl max-w-lg shadow-2xl space-y-4">
            <div class="w-16 h-16 rounded-full bg-teal-500/20 text-teal-400 text-3xl mx-auto flex items-center justify-center animate-bounce">
                <i class="fa-solid fa-volume-high"></i>
            </div>
            <h2 class="text-2xl font-black text-white">Enable Voice Announcements</h2>
            <p class="text-sm text-slate-300">
                Click anywhere on this screen to activate voice calling for OPD tokens. Required by browser audio policies.
            </p>
            <div class="pt-2">
                <button type="button" class="px-6 py-3 bg-gradient-to-r from-teal-500 to-emerald-500 text-slate-950 font-black text-sm rounded-xl shadow-lg hover:scale-105 transition">
                    <i class="fa-solid fa-play mr-2"></i> ACTIVATE AUDIO DISPLAY
                </button>
            </div>
        </div>
    </div>

    <!-- HEADER BAR -->
    <header class="bg-slate-900/90 border-b border-slate-800 px-6 py-3.5 flex items-center justify-between backdrop-blur-sm">
        <!-- Hospital Branding -->
        <div class="flex items-center space-x-4">
            @if($profile->hasLogo())
                <img src="{{ $profile->logo_url }}" alt="Logo" class="h-12 w-auto object-contain rounded-lg bg-white/5 p-1">
            @else
                <div class="w-12 h-12 rounded-xl bg-teal-500/20 border border-teal-500/30 flex items-center justify-center text-teal-400 text-2xl font-black">
                    <i class="fa-solid fa-hospital"></i>
                </div>
            @endif
            <div>
                <h1 class="text-xl md:text-2xl font-black tracking-tight text-white uppercase">
                    {{ $profile->account_name ?? 'HOSPITAL CLINIC' }}
                </h1>
                <p class="text-xs text-teal-400 font-bold tracking-wider flex items-center gap-2">
                    <span>OPD TOKEN CALLING SYSTEM</span>
                    <span class="text-slate-600">•</span>
                    <span class="text-emerald-400 flex items-center gap-1.5" id="lanStatusBadge">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span id="lanStatusText">LAN SERVER ONLINE</span>
                    </span>
                </p>
            </div>
        </div>

        <!-- Controls & Digital Clock -->
        <div class="flex items-center space-x-6">
            <!-- Voice Mute / Audio Toggle -->
            <button id="muteToggleBtn" type="button" onclick="toggleMute()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold border border-slate-700 flex items-center gap-2 transition" title="Toggle Voice Announcements">
                <i class="fa-solid fa-volume-high text-teal-400" id="muteIcon"></i>
                <span id="muteLabel">Voice: ON</span>
            </button>

            <!-- Test Chime Button -->
            <button type="button" onclick="testAudio()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-xl text-xs font-semibold border border-slate-700 transition" title="Test Voice Output">
                <i class="fa-solid fa-bell"></i>
            </button>

            <!-- Live Clock Display -->
            <div class="text-right pl-3 border-l border-slate-800">
                <div id="liveClock" class="font-mono-num text-2xl md:text-3xl font-extrabold text-amber-400 tracking-wider">
                    --:--:--
                </div>
                <div id="liveDate" class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    {{ now()->format('l, d M Y') }}
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN DISPLAY BODY (SPLIT 60/40) -->
    <main class="flex-1 p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 overflow-hidden">
        
        <!-- LEFT COLUMN: NOW CALLING / NOW SERVING HERO (7 COLS) -->
        <div class="lg:col-span-7 flex flex-col justify-between space-y-4">
            
            <!-- Primary Calling Card -->
            <div id="heroCard" class="flex-1 bg-gradient-to-br from-slate-900 via-slate-900 to-teal-950/80 border-2 border-teal-500/50 rounded-3xl p-6 md:p-8 flex flex-col justify-between shadow-2xl relative overflow-hidden pulse-card">
                
                <div class="absolute -right-8 -top-8 text-teal-500/5 text-9xl pointer-events-none">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>

                <!-- Top Card Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-400 animate-ping"></span>
                        <span class="text-sm md:text-base font-extrabold uppercase tracking-widest text-emerald-400" id="heroBadge">
                            NOW CALLING / تشریف لائیں
                        </span>
                    </div>
                    <span id="heroCallCount" class="px-3 py-1 bg-white/10 text-slate-300 text-xs font-bold rounded-full">
                        Call #1
                    </span>
                </div>

                <!-- Giant Token Number -->
                <div class="text-center my-auto py-4">
                    <div class="text-xs uppercase font-extrabold text-teal-300/80 tracking-widest mb-1">
                        PATIENT TOKEN NO
                    </div>
                    <div id="heroTokenNumber" class="font-mono-num text-7xl sm:text-8xl md:text-9xl font-black text-amber-300 tracking-tight drop-shadow-md">
                        @if($activeTokens->first())
                            {{ $activeTokens->first()->formatted_token_number }}
                        @else
                            ---
                        @endif
                    </div>
                </div>

                <!-- Room & Doctor Banner -->
                <div class="bg-slate-950/70 border border-white/10 rounded-2xl p-5 grid grid-cols-2 gap-4">
                    <div class="border-r border-white/10 pr-4">
                        <div class="text-xs font-bold uppercase text-slate-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-door-open text-teal-400"></i>
                            <span>Room / کمرہ</span>
                        </div>
                        <div id="heroRoomNumber" class="text-2xl md:text-3xl font-extrabold text-white mt-1">
                            @if($activeTokens->first())
                                {{ $activeTokens->first()->room_number ?? $activeTokens->first()->doctor?->room_number ?? 'OPD Room' }}
                            @else
                                Desk Available
                            @endif
                        </div>
                    </div>
                    <div class="pl-2">
                        <div class="text-xs font-bold uppercase text-slate-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-user-doctor text-teal-400"></i>
                            <span>Attending Doctor</span>
                        </div>
                        <div id="heroDoctorName" class="text-lg md:text-2xl font-bold text-teal-200 mt-1 truncate">
                            @if($activeTokens->first() && $activeTokens->first()->doctor)
                                Dr. {{ $activeTokens->first()->doctor->name }}
                            @else
                                Please wait
                            @endif
                        </div>
                        <div id="heroDepartment" class="text-xs font-medium text-slate-400 mt-0.5">
                            {{ $activeTokens->first()?->doctor?->specialization ?? 'General OPD' }}
                        </div>
                    </div>
                </div>

            </div>

            <!-- Secondary Active Rooms Bar (Multi-Doctor Display) -->
            <div id="secondaryRoomsContainer" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <!-- Injected dynamically via JS or rendered from initial -->
            </div>

        </div>

        <!-- RIGHT COLUMN: UPCOMING QUEUE (5 COLS) -->
        <div class="lg:col-span-5 bg-slate-900/70 border border-slate-800 rounded-3xl p-6 flex flex-col justify-between shadow-xl">
            
            <div>
                <!-- Queue Box Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-users text-teal-400 text-lg"></i>
                        <h2 class="text-base md:text-lg font-black uppercase text-white tracking-wide">
                            UPCOMING TOKENS / آنے والے مریض
                        </h2>
                    </div>
                    <span id="waitingCountBadge" class="px-2.5 py-1 bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold rounded-lg font-mono">
                        {{ $waitingTokens->count() }} Waiting
                    </span>
                </div>

                <!-- Upcoming Token Cards Grid -->
                <div id="upcomingTokensList" class="space-y-2.5 mt-4">
                    @forelse($waitingTokens as $wait)
                        <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3 flex items-center justify-between transition hover:border-slate-700">
                            <div class="flex items-center space-x-3">
                                <span class="font-mono-num text-xl font-black text-amber-300 bg-slate-900 border border-slate-700 w-12 h-12 rounded-xl flex items-center justify-center shadow-xs">
                                    {{ $wait->formatted_token_number }}
                                </span>
                                <div>
                                    <div class="text-sm font-bold text-white">
                                        {{ $wait->doctor ? 'Dr. ' . $wait->doctor->name : 'OPD Doctor' }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ $wait->doctor?->specialization ?? 'OPD' }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-2.5 py-1 rounded-md bg-teal-500/10 text-teal-300 text-xs font-bold border border-teal-500/20">
                                    Room {{ $wait->room_number ?? $wait->doctor?->room_number ?? '1' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-500 text-sm">
                            <i class="fa-solid fa-mug-hot text-3xl mb-2 text-slate-600 block"></i>
                            <span>No waiting patients in queue.</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Queue Footer Note -->
            <div class="pt-4 border-t border-slate-800/80 text-center text-xs text-slate-400 flex items-center justify-center gap-2">
                <i class="fa-solid fa-circle-info text-teal-400"></i>
                <span>Please proceed promptly to your designated room when called.</span>
            </div>

        </div>

    </main>

    <!-- FOOTER TICKER -->
    <footer class="bg-slate-950 border-t border-slate-800 py-2.5 px-4">
        <div class="ticker-wrap flex items-center">
            <div class="px-3 py-1 bg-teal-600 text-slate-950 font-black text-xs uppercase tracking-wider rounded-md mr-4 shrink-0">
                NOTICE / اعلان
            </div>
            <div id="tickerText" class="ticker-content text-sm md:text-base font-bold text-slate-200">
                {{ $profile->lcd_message_ticker ?: 'Welcome to Healthcare OPD Clinic • Please keep your token slip ready and proceed to your doctor room upon announcement • تمام مریض برائے مہربانی اپنا ٹوکن سنبھال کر رکھیں اور آواز پر متعلقہ کمرے میں تشریف لے جائیں' }}
            </div>
        </div>
    </footer>

    <!-- VOICE ENGINE & REAL-TIME LOCAL POLLER SCRIPT -->
    <script>
        // State variables
        let audioAllowed = false;
        let isMuted = false;
        let pollIntervalMs = {{ ($profile->lcd_refresh_interval_sec ?? 3) * 1000 }};
        let pollTimer = null;
        let announcedLogIds = new Set();
        let announcementQueue = [];
        let isAnnouncing = false;

        // Load announced logs from sessionStorage to prevent re-announcing on page reload
        try {
            const saved = sessionStorage.getItem('announced_token_logs');
            if (saved) {
                JSON.parse(saved).forEach(id => announcedLogIds.add(id));
            }
        } catch (e) {
            console.warn('SessionStorage load error', e);
        }

        // Web Audio API Synthesizer Chime
        let audioCtx = null;
        function playChime() {
            try {
                if (!audioCtx) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }

                const now = audioCtx.currentTime;
                // Two-tone hospital bell chime (E5 -> B5)
                const osc1 = audioCtx.createOscillator();
                const gain1 = audioCtx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(659.25, now); // E5
                gain1.gain.setValueAtTime(0.3, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                osc1.connect(gain1);
                gain1.connect(audioCtx.destination);
                osc1.start(now);
                osc1.stop(now + 0.5);

                const osc2 = audioCtx.createOscillator();
                const gain2 = audioCtx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(987.77, now + 0.25); // B5
                gain2.gain.setValueAtTime(0.3, now + 0.25);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
                osc2.connect(gain2);
                gain2.connect(audioCtx.destination);
                osc2.start(now + 0.25);
                osc2.stop(now + 0.9);
            } catch (err) {
                console.warn('Audio chime synthesis error:', err);
            }
        }

        // Audio Activation
        document.getElementById('audioOverlay').addEventListener('click', function() {
            audioAllowed = true;
            this.classList.add('opacity-0');
            setTimeout(() => this.classList.add('hidden'), 300);
            playChime();
        });

        function toggleMute() {
            isMuted = !isMuted;
            document.getElementById('muteIcon').className = isMuted ? 'fa-solid fa-volume-xmark text-rose-400' : 'fa-solid fa-volume-high text-teal-400';
            document.getElementById('muteLabel').innerText = isMuted ? 'Voice: OFF' : 'Voice: ON';
        }

        function testAudio() {
            audioAllowed = true;
            document.getElementById('audioOverlay').classList.add('hidden');
            queueVoiceAnnouncement({
                log_id: 'test_' + Date.now(),
                formatted_token_number: 'OPD-001',
                doctor_name: 'Dr. Test Consultant',
                room_number: 'Room 1'
            }, true);
        }

        // Voice Announcer
        function queueVoiceAnnouncement(item, force = false) {
            if (isMuted) return;
            if (!force && announcedLogIds.has(item.log_id)) return;

            announcedLogIds.add(item.log_id);
            try {
                sessionStorage.setItem('announced_token_logs', JSON.stringify(Array.from(announcedLogIds).slice(-100)));
            } catch (e) {}

            announcementQueue.push(item);
            processAnnouncementQueue();
        }

        function processAnnouncementQueue() {
            if (isAnnouncing || announcementQueue.length === 0) return;
            isAnnouncing = true;

            const item = announcementQueue.shift();
            playChime();

            setTimeout(() => {
                const synth = window.speechSynthesis;
                if (!synth) {
                    isAnnouncing = false;
                    return;
                }

                // Clean doctor name and room
                const docName = item.doctor_name || 'the doctor';
                const roomNum = item.room_number || 'the consultation room';
                const tokenStr = item.formatted_token_number || ('Token ' + item.token_number);

                // English Utterance
                const enText = `Attention please. Token number ${tokenStr}. Please proceed to ${roomNum}, ${docName}.`;
                const enUtterance = new SpeechSynthesisUtterance(enText);
                enUtterance.lang = 'en-US';
                enUtterance.rate = 0.9;
                enUtterance.pitch = 1.0;

                // Urdu Utterance (phonetic Urdu phrasing recognized by Urdu/Hindi voice synthesizers)
                const urText = `توجہ فرمائیں۔ ٹوکن نمبر ${tokenStr}، برائے مہربانی ${roomNum} میں تشریف لے جائیں۔`;
                const urUtterance = new SpeechSynthesisUtterance(urText);
                urUtterance.lang = 'ur-PK';
                urUtterance.rate = 0.85;

                // Select preferred voices if available
                const voices = synth.getVoices();
                const urVoice = voices.find(v => v.lang.startsWith('ur') || v.lang.startsWith('hi'));
                if (urVoice) urUtterance.voice = urVoice;

                enUtterance.onend = () => {
                    // Follow with Urdu
                    setTimeout(() => {
                        synth.speak(urUtterance);
                    }, 400);
                };

                urUtterance.onend = () => {
                    setTimeout(() => {
                        isAnnouncing = false;
                        processAnnouncementQueue();
                    }, 500);
                };

                urUtterance.onerror = enUtterance.onerror = (e) => {
                    console.warn('Speech error', e);
                    isAnnouncing = false;
                    processAnnouncementQueue();
                };

                synth.speak(enUtterance);
            }, 700);
        }

        // Live LAN Polling Loop
        async function fetchLiveData() {
            try {
                const response = await fetch('/api/token-display/live');
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();

                // Update Network Status Badge
                document.getElementById('lanStatusBadge').innerHTML = `
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>LAN SERVER ONLINE</span>
                `;

                // Update Clock
                if (data.server_time) {
                    document.getElementById('liveClock').innerText = data.server_time;
                }
                if (data.server_date) {
                    document.getElementById('liveDate').innerText = data.server_date;
                }

                // Update Hero Card (First active token)
                const active = data.active_tokens || [];
                if (active.length > 0) {
                    const primary = active[0];
                    document.getElementById('heroTokenNumber').innerText = primary.formatted_token_number;
                    document.getElementById('heroRoomNumber').innerText = primary.room_number || 'OPD Room';
                    document.getElementById('heroDoctorName').innerText = primary.doctor_name || 'Attending Doctor';
                    document.getElementById('heroDepartment').innerText = primary.department || 'General OPD';
                    document.getElementById('heroBadge').innerText = (primary.status === 'in_consultation')
                        ? 'NOW SERVING / زیر معائنہ'
                        : 'NOW CALLING / تشریف لائیں';
                    document.getElementById('heroCallCount').innerText = `Call #${primary.call_count || 1}`;
                } else {
                    document.getElementById('heroTokenNumber').innerText = '---';
                    document.getElementById('heroRoomNumber').innerText = 'Desk Available';
                    document.getElementById('heroDoctorName').innerText = 'Please wait';
                    document.getElementById('heroDepartment').innerText = 'General OPD';
                    document.getElementById('heroBadge').innerText = 'DESK READY / فارغ ہے';
                    document.getElementById('heroCallCount').innerText = 'Ready';
                }

                // Secondary Rooms (tokens 1..3 if multi-doctor)
                const secContainer = document.getElementById('secondaryRoomsContainer');
                if (active.length > 1) {
                    let secHtml = '';
                    for (let i = 1; i < active.length; i++) {
                        const sec = active[i];
                        secHtml += `
                            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-3 flex items-center justify-between">
                                <div>
                                    <span class="font-mono-num text-xl font-black text-amber-300">#${sec.formatted_token_number}</span>
                                    <div class="text-[11px] font-bold text-white truncate max-w-[120px]">${sec.doctor_name}</div>
                                </div>
                                <span class="px-2 py-1 bg-white/5 text-teal-300 text-xs font-bold rounded-lg">${sec.room_number || 'Room'}</span>
                            </div>
                        `;
                    }
                    secContainer.innerHTML = secHtml;
                } else {
                    secContainer.innerHTML = '';
                }

                // Update Upcoming Tokens List
                const waiting = data.waiting_tokens || [];
                document.getElementById('waitingCountBadge').innerText = `${data.total_waiting || waiting.length} Waiting`;
                const waitContainer = document.getElementById('upcomingTokensList');
                if (waiting.length > 0) {
                    let waitHtml = '';
                    waiting.forEach(w => {
                        waitHtml += `
                            <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3 flex items-center justify-between transition hover:border-slate-700">
                                <div class="flex items-center space-x-3">
                                    <span class="font-mono-num text-xl font-black text-amber-300 bg-slate-900 border border-slate-700 w-12 h-12 rounded-xl flex items-center justify-center shadow-xs">
                                        ${w.formatted_token_number}
                                    </span>
                                    <div>
                                        <div class="text-sm font-bold text-white">${w.doctor_name}</div>
                                        <div class="text-xs text-slate-400">${w.department}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2.5 py-1 rounded-md bg-teal-500/10 text-teal-300 text-xs font-bold border border-teal-500/20">
                                        Room ${w.room_number || '1'}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    waitContainer.innerHTML = waitHtml;
                } else {
                    waitContainer.innerHTML = `
                        <div class="py-12 text-center text-slate-500 text-sm">
                            <i class="fa-solid fa-mug-hot text-3xl mb-2 text-slate-600 block"></i>
                            <span>No waiting patients in queue.</span>
                        </div>
                    `;
                }

                // Check for voice announcement trigger
                if (data.latest_announcement && audioAllowed) {
                    queueVoiceAnnouncement(data.latest_announcement);
                }

            } catch (err) {
                // Show offline indicator without crashing
                document.getElementById('lanStatusBadge').innerHTML = `
                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                    <span class="text-rose-400">LAN RECONNECTING...</span>
                `;
            }
        }

        // Start Live Poller
        setInterval(fetchLiveData, pollIntervalMs);
        fetchLiveData();

        // Local clock fallback
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour12: true });
            document.getElementById('liveClock').innerText = timeStr;
        }, 1000);
    </script>
</body>
</html>

