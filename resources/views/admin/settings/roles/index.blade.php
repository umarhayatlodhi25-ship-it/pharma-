@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Roles & Permissions
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Manage roles and assign specific features dynamically.
            </p>
        </div>

        <a href="{{ route('admin.settings.users.index') }}"
           class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-gray-600 text-white text-sm font-semibold hover:bg-gray-700 transition shadow-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back to Users
        </a>
    </div>

    @livewire('admin.role-management')
</div>
@endsection
