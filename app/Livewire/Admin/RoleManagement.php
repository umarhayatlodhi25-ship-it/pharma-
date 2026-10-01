<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Str;

class RoleManagement extends Component
{
    public $roles;
    public $selectedRoleId = null;
    public $features = [
        'view_dashboard' => 'View Dashboard',
        'manage_medicines' => 'Manage Medicines & Inventory',
        'manage_purchases' => 'Manage Purchases',
        'manage_suppliers' => 'Manage Suppliers',
        'manage_returns' => 'Manage Returns',
        'process_pos' => 'Access POS Counter',
        'manage_hold_invoices' => 'Manage Hold Invoices',
        'view_sales' => 'View Sales History',
        'view_expiry' => 'View Expiry Alerts',
        'manage_patients' => 'Manage Patients',
        'manage_doctors' => 'Manage Doctors',
        'manage_opd_tokens' => 'Manage OPD Tokens',
        'manage_hospital_billing' => 'Manage Hospital Billing',
        'manage_doctor_ledgers' => 'Manage Doctor Ledgers',
        'manage_hospital_services' => 'Manage Hospital Services',
        'view_reports' => 'View Reports Hub',
    ];
    public $selectedFeatures = [];
    
    public $newRoleName = '';
    
    public function mount()
    {
        $this->loadRoles();
    }
    
    public function loadRoles()
    {
        $this->roles = Role::all();
    }
    
    public function createRole()
    {
        $this->validate([
            'newRoleName' => 'required|string|min:2|unique:roles,name',
        ]);
        
        $role = Role::create([
            'name' => $this->newRoleName,
            'slug' => Str::slug($this->newRoleName, '_')
        ]);
        
        $this->newRoleName = '';
        $this->loadRoles();
        $this->selectRole($role->id);
        
        session()->flash('success', 'New role created successfully!');
    }
    
    public function selectRole($roleId)
    {
        $this->selectedRoleId = $roleId;
        $this->selectedFeatures = RolePermission::where('role_id', $roleId)
            ->pluck('feature')
            ->toArray();
    }
    
    public function savePermissions()
    {
        if (!$this->selectedRoleId) return;
        
        RolePermission::where('role_id', $this->selectedRoleId)->delete();
        
        foreach ($this->selectedFeatures as $feature) {
            RolePermission::create([
                'role_id' => $this->selectedRoleId,
                'feature' => $feature
            ]);
        }
        
        session()->flash('success', 'Features updated successfully for selected role!');
    }
    
    public function render()
    {
        return view('livewire.admin.role-management');
    }
}
