<div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left Side: Roles List & Create -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Roles</h3>
            
            <div class="mb-4">
                <form wire:submit.prevent="createRole" class="flex items-center space-x-2">
                    <input type="text" wire:model.defer="newRoleName" placeholder="New Role Name" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded-md text-sm hover:bg-blue-700 transition">
                        Add
                    </button>
                </form>
                @error('newRoleName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            
            <div class="space-y-2 mt-4">
                @foreach($roles as $role)
                    <button wire:click="selectRole({{ $role->id }})" 
                            class="w-full text-left px-4 py-3 rounded-lg border transition {{ $selectedRoleId === $role->id ? 'bg-blue-50 border-blue-200 text-blue-700 font-semibold' : 'border-gray-100 hover:bg-gray-50' }}">
                        {{ $role->name }}
                        @if($role->slug === 'admin')
                            <span class="text-xs ml-2 text-gray-500 font-normal">(Full Access)</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
        
        <!-- Right Side: Permissions/Features Assignment -->
        <div class="md:col-span-2 bg-white p-5 rounded-xl shadow-sm border border-gray-200">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Assign Features</h3>
            
            @if(session()->has('success'))
                <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif
            
            @if($selectedRoleId)
                @php $selectedRole = $roles->firstWhere('id', $selectedRoleId); @endphp
                
                @if($selectedRole->slug === 'admin')
                    <div class="p-4 bg-gray-50 rounded-lg text-gray-600 text-center">
                        <i class="fa-solid fa-shield-halved text-3xl mb-2 text-blue-400"></i>
                        <p>The <strong>Admin</strong> role always has full access to all features.</p>
                        <p class="text-sm">You don't need to assign features to this role.</p>
                    </div>
                @else
                    <form wire:submit.prevent="savePermissions">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            @foreach($features as $key => $label)
                                <label class="flex items-start space-x-3 p-3 border rounded-lg hover:bg-slate-50 cursor-pointer">
                                    <input type="checkbox" wire:model="selectedFeatures" value="{{ $key }}" class="mt-1 rounded text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        
                        <div class="text-right">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition shadow-sm">
                                <i class="fa-solid fa-save mr-2"></i> Save Features
                            </button>
                        </div>
                    </form>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                    <i class="fa-solid fa-hand-pointer text-4xl mb-3"></i>
                    <p>Select a role from the left to assign features</p>
                </div>
            @endif
        </div>
        
    </div>
</div>
