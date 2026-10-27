@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{
    approveModalOpen: false,
    rejectModalOpen: false,
    suspendModalOpen: false,
    activeRequest: null,
    activeDoctorName: '',
    actionUrl: '',
    
    openApprove(id, name, url) {
        this.activeRequest = id;
        this.activeDoctorName = name;
        this.actionUrl = url;
        this.approveModalOpen = true;
    },
    openReject(id, name, url) {
        this.activeRequest = id;
        this.activeDoctorName = name;
        this.actionUrl = url;
        this.rejectModalOpen = true;
    },
    openSuspend(id, name, url) {
        this.activeRequest = id;
        this.activeDoctorName = name;
        this.actionUrl = url;
        this.suspendModalOpen = true;
    }
}">

    <!-- BREADCRUMBS & TOP HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/dashboard" class="hover:underline">Dashboard</a>
                <span>/</span>
                <a href="{{ route('doctors.index') }}" class="hover:underline">Doctors</a>
                <span>/</span>
                <span class="text-gray-500">Portal Requests</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2.5">
                <i class="fa-solid fa-user-check text-blue-600"></i>
                <span>Doctor Portal Registration Requests</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Review identity verification, approve portal accounts, or suspend access for existing hospital doctors.</p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('doctors.index') }}" class="inline-flex items-center px-3.5 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-xs">
                <i class="fa-solid fa-user-doctor mr-1.5 text-blue-600"></i>
                <span>Doctor Management</span>
            </a>
        </div>
    </div>

    <!-- FLASH NOTIFICATIONS -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
                <span class="font-semibold">{{ $errors->first() }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- STATUS METRIC CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3.5">
        <a href="{{ route('admin.doctor-requests.index', ['status' => 'all']) }}" class="p-3.5 rounded-xl border {{ $statusFilter === 'all' ? 'bg-blue-50/70 border-blue-400 shadow-sm' : 'bg-white border-gray-200 hover:border-gray-300' }} transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Total Requests</div>
            <div class="text-2xl font-black text-gray-800 mt-1">{{ $counts['all'] }}</div>
        </a>

        <a href="{{ route('admin.doctor-requests.index', ['status' => 'pending']) }}" class="p-3.5 rounded-xl border {{ $statusFilter === 'pending' ? 'bg-amber-50/70 border-amber-400 shadow-sm' : 'bg-white border-gray-200 hover:border-amber-300' }} transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pending Review</span>
                @if($counts['pending'] > 0)
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                @endif
            </div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ $counts['pending'] }}</div>
        </a>

        <a href="{{ route('admin.doctor-requests.index', ['status' => 'approved']) }}" class="p-3.5 rounded-xl border {{ $statusFilter === 'approved' ? 'bg-emerald-50/70 border-emerald-400 shadow-sm' : 'bg-white border-gray-200 hover:border-emerald-300' }} transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Approved</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $counts['approved'] }}</div>
        </a>

        <a href="{{ route('admin.doctor-requests.index', ['status' => 'rejected']) }}" class="p-3.5 rounded-xl border {{ $statusFilter === 'rejected' ? 'bg-rose-50/70 border-rose-400 shadow-sm' : 'bg-white border-gray-200 hover:border-rose-300' }} transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Rejected</div>
            <div class="text-2xl font-black text-rose-600 mt-1">{{ $counts['rejected'] }}</div>
        </a>

        <a href="{{ route('admin.doctor-requests.index', ['status' => 'suspended']) }}" class="p-3.5 rounded-xl border {{ $statusFilter === 'suspended' ? 'bg-slate-100 border-slate-400 shadow-sm' : 'bg-white border-gray-200 hover:border-slate-300' }} transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Suspended</div>
            <div class="text-2xl font-black text-slate-700 mt-1">{{ $counts['suspended'] }}</div>
        </a>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <!-- Status Tab Pills -->
        <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs">
            <a href="{{ route('admin.doctor-requests.index', array_merge(request()->query(), ['status' => 'all'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                All ({{ $counts['all'] }})
            </a>
            <a href="{{ route('admin.doctor-requests.index', array_merge(request()->query(), ['status' => 'pending'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Pending ({{ $counts['pending'] }})
            </a>
            <a href="{{ route('admin.doctor-requests.index', array_merge(request()->query(), ['status' => 'approved'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ $statusFilter === 'approved' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Approved ({{ $counts['approved'] }})
            </a>
            <a href="{{ route('admin.doctor-requests.index', array_merge(request()->query(), ['status' => 'rejected'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ $statusFilter === 'rejected' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Rejected ({{ $counts['rejected'] }})
            </a>
            <a href="{{ route('admin.doctor-requests.index', array_merge(request()->query(), ['status' => 'suspended'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ $statusFilter === 'suspended' ? 'bg-slate-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Suspended ({{ $counts['suspended'] }})
            </a>
        </div>

        <!-- Search Input -->
        <form action="{{ route('admin.doctor-requests.index') }}" method="GET" class="flex items-center space-x-2">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}"
                    placeholder="Search doctor, phone, PMDC..." 
                    class="w-full pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-300 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                >
            </div>
            @if(!empty($search))
                <a href="{{ route('admin.doctor-requests.index', ['status' => $statusFilter]) }}" class="px-2 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- MAIN REQUESTS TABLE -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Req # / Date</th>
                        <th class="py-3 px-4">Doctor & Profile Match</th>
                        <th class="py-3 px-4">Contact Details</th>
                        <th class="py-3 px-4">Identity & Verification</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Reviewer / Remarks</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($requests as $req)
                    <tr class="hover:bg-slate-50/80 transition">
                        <!-- Request ID & Date -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="font-bold text-gray-900">#REQ-{{ str_pad($req->id, 4, '0', STR_PAD_LEFT) }}</div>
                            <div class="text-[11px] text-gray-500 mt-0.5">
                                <i class="fa-regular fa-clock text-[10px] mr-1"></i>
                                {{ $req->created_at->format('d M Y, h:i A') }}
                            </div>
                        </td>

                        <!-- Doctor & Profile Match -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-900 flex items-center space-x-1.5">
                                <i class="fa-solid fa-user-doctor text-blue-600"></i>
                                <span>{{ $req->name }}</span>
                            </div>

                            @if($req->doctor)
                                <div class="mt-1 flex items-center space-x-1.5 text-[11px]">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-semibold">
                                        Doctor Profile #{{ $req->doctor->id }}
                                    </span>
                                    <span class="text-gray-500 truncate max-w-[150px]">{{ $req->doctor->specialization ?: 'Doctor' }}</span>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    Consultations: {{ $req->doctor->tokens()->count() }} tokens
                                </div>
                            @else
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-semibold">
                                        No Direct Match (Identifier: {{ $req->doctor_identifier ?: 'None' }})
                                    </span>
                                </div>
                            @endif
                        </td>

                        <!-- Contact Details -->
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-1 text-gray-800">
                                <i class="fa-regular fa-envelope text-gray-400 text-[11px]"></i>
                                <span>{{ $req->email }}</span>
                            </div>
                            <div class="flex items-center space-x-1 text-gray-600 mt-0.5 text-[11px]">
                                <i class="fa-solid fa-phone text-gray-400 text-[10px]"></i>
                                <span>{{ $req->phone }}</span>
                            </div>
                            @if($req->ip_address)
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    IP: {{ $req->ip_address }}
                                </div>
                            @endif
                        </td>

                        <!-- Verification Status -->
                        <td class="py-3 px-4">
                            <div class="font-semibold text-gray-800 text-[11px]">
                                License / ID: <span class="font-bold text-slate-900">{{ $req->license_number ?: 'N/A' }}</span>
                            </div>

                            @if($req->is_verified)
                                <div class="mt-1 inline-flex items-center space-x-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-200">
                                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                                    <span>Contact Verified</span>
                                </div>
                            @else
                                <div class="mt-1 inline-flex items-center space-x-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold border border-amber-200">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                                    <span>Manual Admin Review</span>
                                </div>
                            @endif

                            @if($req->verification_notes)
                                <div class="text-[10px] text-gray-500 mt-1 max-w-[200px] leading-tight">
                                    {{ $req->verification_notes }}
                                </div>
                            @endif
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($req->status === 'approved')
                                <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    <i class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i>
                                    <span>Approved</span>
                                </span>
                            @elseif($req->status === 'rejected')
                                <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                    <i class="fa-solid fa-circle-xmark text-rose-600 text-[10px]"></i>
                                    <span>Rejected</span>
                                </span>
                            @elseif($req->status === 'suspended')
                                <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-200 text-slate-800 border border-slate-300">
                                    <i class="fa-solid fa-ban text-slate-600 text-[10px]"></i>
                                    <span>Suspended</span>
                                </span>
                            @else
                                <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    <i class="fa-solid fa-clock text-amber-600 text-[10px]"></i>
                                    <span>Pending</span>
                                </span>
                            @endif
                        </td>

                        <!-- Reviewer / Remarks -->
                        <td class="py-3 px-4">
                            @if($req->reviewer)
                                <div class="text-[11px] font-semibold text-gray-800">
                                    By: {{ $req->reviewer->name }}
                                </div>
                                <div class="text-[10px] text-gray-400">
                                    {{ $req->reviewed_at ? $req->reviewed_at->format('d M Y, h:i A') : '' }}
                                </div>
                            @endif

                            @if($req->admin_remarks)
                                <div class="text-[10px] text-gray-600 mt-1 italic max-w-[180px]">
                                    "{{ $req->admin_remarks }}"
                                </div>
                            @endif

                            @if($req->rejection_reason)
                                <div class="text-[10px] text-rose-700 mt-1 font-semibold max-w-[180px]">
                                    Reason: {{ $req->rejection_reason }}
                                </div>
                            @endif
                        </td>

                        <!-- Action Buttons -->
                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end space-x-1.5">
                                @if($req->status === 'pending')
                                    <!-- Approve Action -->
                                    <button 
                                        type="button" 
                                        @click="openApprove('{{ $req->id }}', '{{ addslashes($req->name) }}', '{{ route('admin.doctor-requests.approve', $req->id) }}')"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition flex items-center space-x-1 cursor-pointer"
                                        title="Approve and Link Account"
                                    >
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                        <span>Approve</span>
                                    </button>

                                    <!-- Reject Action -->
                                    <button 
                                        type="button" 
                                        @click="openReject('{{ $req->id }}', '{{ addslashes($req->name) }}', '{{ route('admin.doctor-requests.reject', $req->id) }}')"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition flex items-center space-x-1 cursor-pointer"
                                        title="Reject Registration"
                                    >
                                        <i class="fa-solid fa-xmark text-[10px]"></i>
                                        <span>Reject</span>
                                    </button>

                                @elseif($req->status === 'approved')
                                    <!-- Suspend Action -->
                                    <button 
                                        type="button" 
                                        @click="openSuspend('{{ $req->id }}', '{{ addslashes($req->name) }}', '{{ route('admin.doctor-requests.suspend', $req->id) }}')"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-700 hover:bg-slate-800 text-white shadow-xs transition flex items-center space-x-1 cursor-pointer"
                                        title="Suspend Doctor Portal Access"
                                    >
                                        <i class="fa-solid fa-ban text-[10px]"></i>
                                        <span>Suspend</span>
                                    </button>

                                @elseif($req->status === 'suspended' || $req->status === 'rejected')
                                    <!-- Re-Approve / Reactivate -->
                                    <button 
                                        type="button" 
                                        @click="openApprove('{{ $req->id }}', '{{ addslashes($req->name) }}', '{{ route('admin.doctor-requests.approve', $req->id) }}')"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition flex items-center space-x-1 cursor-pointer"
                                        title="Reactivate Account"
                                    >
                                        <i class="fa-solid fa-rotate text-[10px]"></i>
                                        <span>Re-Approve</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-xl">
                                    <i class="fa-solid fa-inbox"></i>
                                </div>
                                <p class="text-sm font-semibold text-gray-700">No doctor registration requests found.</p>
                                <p class="text-xs text-gray-400">Requests submitted at /doctor-portal/register will appear here for verification.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="p-3 border-t border-gray-200">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    <!-- APPROVE MODAL -->
    <div 
        x-show="approveModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div 
            @click.away="approveModalOpen = false" 
            class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-gray-200"
        >
            <div class="flex items-center justify-between border-b pb-3 border-gray-100">
                <h3 class="text-base font-bold text-gray-900 flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>Approve Doctor Portal Account</span>
                </h3>
                <button @click="approveModalOpen = false" class="text-gray-400 hover:text-gray-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed">
                You are approving the portal account for <strong class="text-gray-900" x-text="activeDoctorName"></strong>. 
                This will activate their portal login, assign the doctor role, and link their existing hospital doctor record.
            </p>

            <form :action="actionUrl" method="POST" class="space-y-3.5">
                @csrf

                <div>
                    <label for="admin_remarks_approve" class="block text-xs font-bold text-gray-700 mb-1">
                        Optional Admin Remarks
                    </label>
                    <textarea 
                        name="admin_remarks" 
                        id="admin_remarks_approve" 
                        rows="2" 
                        placeholder="e.g. Identity verified via PMDC records; active consultant."
                        class="w-full px-3 py-2 text-xs bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                    ></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button 
                        type="button" 
                        @click="approveModalOpen = false" 
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition flex items-center space-x-1.5"
                    >
                        <i class="fa-solid fa-check"></i>
                        <span>Confirm Approval</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- REJECT MODAL -->
    <div 
        x-show="rejectModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div 
            @click.away="rejectModalOpen = false" 
            class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-gray-200"
        >
            <div class="flex items-center justify-between border-b pb-3 border-gray-100">
                <h3 class="text-base font-bold text-rose-900 flex items-center space-x-2">
                    <i class="fa-solid fa-circle-xmark text-rose-600"></i>
                    <span>Reject Doctor Registration</span>
                </h3>
                <button @click="rejectModalOpen = false" class="text-gray-400 hover:text-gray-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed">
                You are rejecting the portal registration for <strong class="text-gray-900" x-text="activeDoctorName"></strong>. 
                Please provide the reason for rejection for audit records.
            </p>

            <form :action="actionUrl" method="POST" class="space-y-3.5">
                @csrf

                <div>
                    <label for="rejection_reason" class="block text-xs font-bold text-gray-700 mb-1">
                        Rejection Reason <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="rejection_reason" 
                        id="rejection_reason" 
                        required
                        placeholder="e.g. Identity could not be verified / Not an authorized doctor"
                        class="w-full px-3 py-2 text-xs bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition"
                    >
                </div>

                <div>
                    <label for="admin_remarks_reject" class="block text-xs font-bold text-gray-700 mb-1">
                        Optional Internal Remarks
                    </label>
                    <textarea 
                        name="admin_remarks" 
                        id="admin_remarks_reject" 
                        rows="2" 
                        placeholder="e.g. Contacted doctor phone; doctor stated they did not initiate this request."
                        class="w-full px-3 py-2 text-xs bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition"
                    ></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button 
                        type="button" 
                        @click="rejectModalOpen = false" 
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition flex items-center space-x-1.5"
                    >
                        <i class="fa-solid fa-xmark"></i>
                        <span>Confirm Rejection</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SUSPEND MODAL -->
    <div 
        x-show="suspendModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div 
            @click.away="suspendModalOpen = false" 
            class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-gray-200"
        >
            <div class="flex items-center justify-between border-b pb-3 border-gray-100">
                <h3 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-ban text-slate-700"></i>
                    <span>Suspend Doctor Portal Access</span>
                </h3>
                <button @click="suspendModalOpen = false" class="text-gray-400 hover:text-gray-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed">
                Are you sure you want to suspend Doctor Portal access for <strong class="text-gray-900" x-text="activeDoctorName"></strong>? 
                This will immediately revoke their access to protected doctor routes, patient records, and clinical APIs.
            </p>

            <form :action="actionUrl" method="POST" class="space-y-3.5">
                @csrf

                <div>
                    <label for="admin_remarks_suspend" class="block text-xs font-bold text-gray-700 mb-1">
                        Suspension Reason / Remarks
                    </label>
                    <textarea 
                        name="admin_remarks" 
                        id="admin_remarks_suspend" 
                        rows="2" 
                        placeholder="e.g. Practice temporarily on leave; portal access suspended as per hospital admin."
                        class="w-full px-3 py-2 text-xs bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-slate-500 focus:border-slate-500 outline-none transition"
                    ></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button 
                        type="button" 
                        @click="suspendModalOpen = false" 
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 shadow-sm transition flex items-center space-x-1.5"
                    >
                        <i class="fa-solid fa-ban"></i>
                        <span>Confirm Suspension</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
