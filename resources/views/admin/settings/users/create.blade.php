@extends('layouts.app')

@section('content')

<div class="max-w-3xl mx-auto space-y-6">

    <div>

        <h1 class="text-2xl font-bold text-gray-900">
            Add User
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Create a new pharmacy system user.
        </p>

    </div>


    <form method="POST"
          action="{{ route('admin.settings.users.store') }}"
          class="bg-white border border-gray-200
                 rounded-xl shadow-sm">

        @csrf

        <div class="p-6 space-y-5">

            {{-- NAME --}}
            <div>

                <label class="block text-sm font-semibold
                              text-gray-700 mb-1.5">

                    Name

                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name') }}"
                       required
                       class="w-full h-11 rounded-lg
                              border border-gray-300
                              px-3 text-sm
                              focus:border-blue-500
                              focus:ring-2
                              focus:ring-blue-100
                              outline-none">

                @error('name')
                    <p class="text-xs text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- EMAIL --}}
            <div>

                <label class="block text-sm font-semibold
                              text-gray-700 mb-1.5">

                    Email

                </label>

                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       class="w-full h-11 rounded-lg
                              border border-gray-300
                              px-3 text-sm
                              focus:border-blue-500
                              focus:ring-2
                              focus:ring-blue-100
                              outline-none">

                @error('email')
                    <p class="text-xs text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- ROLE --}}
            <div>

                <label class="block text-sm font-semibold
                              text-gray-700 mb-1.5">

                    Role

                </label>

                <select name="role"
                        required
                        class="w-full h-11 rounded-lg
                               border border-gray-300
                               px-3 text-sm
                               bg-white
                               focus:border-blue-500
                               focus:ring-2
                               focus:ring-blue-100
                               outline-none">

                    <option value="">
                        Select Role
                    </option>

                    @foreach($roles as $r)
                        <option value="{{ $r->slug }}" @selected(old('role') === $r->slug)>
                            {{ $r->name }}
                        </option>
                    @endforeach

                </select>

                @error('role')
                    <p class="text-xs text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- PASSWORD --}}
            <div>

                <label class="block text-sm font-semibold
                              text-gray-700 mb-1.5">

                    Password

                </label>

                <input type="password"
                       name="password"
                       required
                       class="w-full h-11 rounded-lg
                              border border-gray-300
                              px-3 text-sm
                              focus:border-blue-500
                              focus:ring-2
                              focus:ring-blue-100
                              outline-none">

                @error('password')
                    <p class="text-xs text-red-600 mt-1">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- CONFIRM PASSWORD --}}
            <div>

                <label class="block text-sm font-semibold
                              text-gray-700 mb-1.5">

                    Confirm Password

                </label>

                <input type="password"
                       name="password_confirmation"
                       required
                       class="w-full h-11 rounded-lg
                              border border-gray-300
                              px-3 text-sm
                              focus:border-blue-500
                              focus:ring-2
                              focus:ring-blue-100
                              outline-none">

            </div>

        </div>


        <div class="px-6 py-4
                    bg-gray-50
                    border-t border-gray-200
                    flex justify-end gap-3">

            <a href="{{ route('admin.settings.users.index') }}"
               class="px-4 py-2.5 rounded-lg
                      border border-gray-300
                      bg-white text-sm
                      font-semibold text-gray-700">

                Cancel

            </a>

            <button type="submit"
                    class="px-5 py-2.5 rounded-lg
                           bg-blue-600 text-white
                           text-sm font-semibold
                           hover:bg-blue-700">

                Create User

            </button>

        </div>

    </form>

</div>

@endsection