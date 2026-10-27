<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Registration Pending Approval - Doctor Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-3 sm:p-6 lg:p-10">

    <div class="w-full max-w-xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200/60 p-6 sm:p-10 text-center space-y-6">

        <!-- Icon Illustration -->
        <div class="relative inline-flex items-center justify-center">
            <div class="w-24 h-24 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-600 border-4 border-amber-100">
                <i class="fa-solid fa-hourglass-half text-4xl animate-pulse"></i>
            </div>
            <div class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-teal-600 text-white flex items-center justify-center shadow-md border-2 border-white">
                <i class="fa-solid fa-user-doctor text-xs"></i>
            </div>
        </div>

        <!-- Headings -->
        <div class="space-y-1.5">
            <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Pending Administrative Approval</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Registration Request Under Review</h1>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Thank you for submitting your Doctor Portal access request. Your credentials and doctor profile are currently being verified by the hospital administration.
            </p>
        </div>

        @if (session('success'))
            <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-left text-xs text-emerald-800 flex items-start space-x-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 shrink-0"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if (session('info'))
            <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-left text-xs text-amber-800 flex items-start space-x-2.5">
                <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0"></i>
                <div>{{ session('info') }}</div>
            </div>
        @endif

        <!-- Timeline / Workflow Steps -->
        <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200/80 text-left space-y-3.5">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-2">
                <i class="fa-solid fa-list-check text-teal-600"></i>
                <span>Verification Workflow</span>
            </h3>

            <div class="space-y-3">
                <!-- Step 1: Complete -->
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs shrink-0 mt-0.5 shadow-sm">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800">1. Registration Form Submitted</p>
                        <p class="text-[11px] text-slate-500">Your doctor details, registered contact, and credentials were securely recorded.</p>
                    </div>
                </div>

                <!-- Step 2: In Progress -->
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs shrink-0 mt-0.5 shadow-sm animate-pulse">
                        <i class="fa-solid fa-spinner animate-spin"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-amber-900">2. Hospital Identity Verification (Current Step)</p>
                        <p class="text-[11px] text-slate-600">The hospital administrator verifies your profile against official hospital doctor records.</p>
                    </div>
                </div>

                <!-- Step 3: Pending -->
                <div class="flex items-start space-x-3 opacity-60">
                    <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center text-xs shrink-0 mt-0.5 shadow-sm">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-700">3. Portal Access Granted</p>
                        <p class="text-[11px] text-slate-500">Upon approval, you will be able to log in to access your OPD Queue and clinical workspace.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Info Box -->
        <div class="p-3 bg-teal-50/70 border border-teal-200/80 rounded-xl text-left text-xs text-teal-900 flex items-start space-x-2.5">
            <i class="fa-solid fa-shield-halved text-teal-600 mt-0.5 shrink-0"></i>
            <div>
                <span class="font-bold">Need fast-track approval?</span>
                <p class="text-[11px] text-teal-700 mt-0.5">Please inform the hospital medical superintendent or IT administrator with your PMDC number.</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a 
                href="{{ route('doctor.login') }}" 
                class="w-full sm:w-auto px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-teal-700 hover:bg-teal-800 shadow-md transition flex items-center justify-center space-x-2"
            >
                <i class="fa-solid fa-right-to-bracket text-xs"></i>
                <span>Return to Doctor Login</span>
            </a>

            <a 
                href="/login" 
                class="w-full sm:w-auto px-5 py-2.5 rounded-xl font-bold text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition flex items-center justify-center space-x-2"
            >
                <i class="fa-solid fa-hospital text-xs"></i>
                <span>Staff & Admin Login</span>
            </a>
        </div>

    </div>

</body>
</html>
