@extends('layouts.app')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Users Management
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Manage pharmacy system users, roles, and permissions safely.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.settings.roles.index') }}"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition shadow-sm">
                <i class="fa-solid fa-user-shield mr-2"></i> Manage Roles
            </a>
            <a href="{{ route('admin.settings.users.create') }}"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                <i class="fa-solid fa-user-plus mr-2"></i> + Add User
            </a>
        </div>
    </div>

    {{-- FLASH MESSAGES --}}
    @if(session('message'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-green-600"></i>
                <span>{{ session('message') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- STATUS TABS & DISPLAY OPTIONS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-3">
        <div class="flex items-center space-x-2 overflow-x-auto">
            <a href="{{ route('admin.settings.users.index', ['status' => 'active', 'per_page' => $perPage ?? 'all']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center space-x-2 {{ ($status ?? 'active') === 'active' ? 'bg-blue-600 text-white font-semibold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                <span>Active Users</span>
                <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ ($status ?? 'active') === 'active' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700' }}">
                    {{ $counts['active'] ?? 0 }}
                </span>
            </a>

            <a href="{{ route('admin.settings.users.index', ['status' => 'trashed', 'per_page' => $perPage ?? 'all']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center space-x-2 {{ ($status ?? '') === 'trashed' ? 'bg-amber-600 text-white font-semibold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                <i class="fa-solid fa-trash-can text-xs"></i>
                <span>Trashed Users</span>
                <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ ($status ?? '') === 'trashed' ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-800' }}">
                    {{ $counts['trashed'] ?? 0 }}
                </span>
            </a>

            <a href="{{ route('admin.settings.users.index', ['status' => 'all', 'per_page' => $perPage ?? 'all']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center space-x-2 {{ ($status ?? '') === 'all' ? 'bg-gray-800 text-white font-semibold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                <span>All Accounts</span>
                <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ ($status ?? '') === 'all' ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-700' }}">
                    {{ $counts['all'] ?? 0 }}
                </span>
            </a>
        </div>

        {{-- PER PAGE SELECTOR --}}
        <div class="flex items-center space-x-2 text-xs text-gray-600 shrink-0">
            <span class="font-medium">Show Rows:</span>
            <form method="GET" action="{{ route('admin.settings.users.index') }}" class="inline">
                <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
                <select name="per_page" onchange="this.form.submit()" class="bg-white border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ ($perPage ?? 'all') === 'all' ? 'selected' : '' }}>Show All (Unlimited)</option>
                    <option value="10" {{ ($perPage ?? '') == '10' ? 'selected' : '' }}>10 Per Page</option>
                    <option value="25" {{ ($perPage ?? '') == '25' ? 'selected' : '' }}>25 Per Page</option>
                    <option value="50" {{ ($perPage ?? '') == '50' ? 'selected' : '' }}>50 Per Page</option>
                    <option value="100" {{ ($perPage ?? '') == '100' ? 'selected' : '' }}>100 Per Page</option>
                </select>
            </form>
        </div>
    </div>

    {{-- USERS TABLE --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-900">
                    {{ ($status ?? 'active') === 'trashed' ? 'Trashed User Accounts' : (($status ?? '') === 'all' ? 'All User Accounts' : 'Active System Users') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Showing {{ $users->count() }} of {{ $users->total() }} user records
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-white border-b border-gray-200 text-gray-600">
                        <th class="px-5 py-3 text-left">#</th>
                        <th class="px-5 py-3 text-left">User</th>
                        <th class="px-5 py-3 text-left">Email</th>
                        <th class="px-5 py-3 text-left">Role</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Created</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50/80 transition {{ $user->trashed() ? 'bg-red-50/30' : '' }}">
                            <td class="px-5 py-4 text-gray-500 font-mono text-xs">
                                {{ $users->firstItem() + $loop->index }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm shadow-xs border border-blue-200">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-800 flex items-center space-x-2">
                                            <span>{{ $user->name }}</span>
                                            @if(auth()->id() === $user->id)
                                                <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-full">You</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 text-gray-600 font-mono text-xs">
                                {{ $user->email }}
                            </td>

                            <td class="px-5 py-4">
                                @php
                                    $roleClasses = match($user->role) {
                                        'admin' => 'bg-red-50 text-red-700 border-red-200',
                                        'pharmacist' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'cashier' => 'bg-green-50 text-green-700 border-green-200',
                                        default => 'bg-gray-50 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <span class="inline-flex px-2.5 py-1 rounded-full border text-xs font-semibold {{ $roleClasses }}">
                                    {{ ucfirst($user->role ?? 'user') }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                @if($user->trashed())
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold">
                                        <i class="fa-solid fa-trash-can text-[10px] mr-1"></i> Trashed
                                    </span>
                                @else
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">
                                        <i class="fa-solid fa-circle-check text-[10px] mr-1"></i> Active
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-gray-500 text-xs">
                                {{ $user->created_at?->format('d M Y') }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    @if($user->trashed())
                                        {{-- RESTORE BUTTON --}}
                                        <form method="POST" action="{{ route('admin.settings.users.restore', $user->id) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition flex items-center space-x-1">
                                                <i class="fa-solid fa-rotate-left mr-1"></i> Restore
                                            </button>
                                        </form>

                                        {{-- PERMANENT DELETE BUTTON --}}
                                        <form method="POST" action="{{ route('admin.settings.users.forceDelete', $user->id) }}"
                                              onsubmit="return confirm('PERMANENT DELETE WARNING: Are you sure you want to permanently delete this user? This cannot be undone!')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg border border-red-300 bg-red-600 text-xs font-semibold text-white hover:bg-red-700 transition flex items-center space-x-1">
                                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> Delete Forever
                                            </button>
                                        </form>
                                    @else
                                        {{-- EDIT BUTTON --}}
                                        <a href="{{ route('admin.settings.users.edit', $user->id) }}"
                                           class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                            Edit
                                        </a>

                                        {{-- SOFT DELETE BUTTON --}}
                                        @if(auth()->id() !== $user->id)
                                            <form method="POST" action="{{ route('admin.settings.users.destroy', $user->id) }}"
                                                  onsubmit="return confirm('Are you sure you want to delete user \'{{ $user->name }}\'? You can restore this user anytime from Trashed Users.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-3 py-1.5 rounded-lg border border-red-200 bg-red-50 text-xs font-semibold text-red-700 hover:bg-red-100 transition">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                <i class="fa-solid fa-users-slash text-2xl mb-2 block"></i>
                                No {{ ($status ?? 'active') === 'trashed' ? 'trashed' : '' }} users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-5 py-4 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
