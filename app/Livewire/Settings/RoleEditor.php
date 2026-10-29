<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleEditor extends Component
{
    public ?Role $role = null;

    public $roleName = '';
    public $roleDescription = '';
    public $roleStatus = 'active';
    public $permissionMatrix = [];
    public $moduleVisibility = [];

    public function boot()
    {
        $user = Auth::user();
        if (!$user?->isSuperAdmin() && !$user?->hasModuleAccess('roles') && $user?->role?->name !== 'Admin') {
            abort(403, 'Akses ditolak.');
        }
    }

    public function mount(?Role $role = null)
    {
        // Initialize default matrix
        $actions = ['view', 'download', 'create', 'edit', 'verify', 'approve', 'disposition', 'archive'];
        $sifats = ['biasa', 'penting', 'rahasia'];
        foreach ($actions as $action) {
            foreach ($sifats as $sifat) {
                $this->permissionMatrix[$action][$sifat] = false;
            }
        }

        if ($role && $role->exists) {
            $this->role = $role;
            $this->roleName = $role->name;
            $this->roleDescription = $role->description;
            $this->roleStatus = $role->status;
            
            $modules = $role->module_visibility ?? [];
            foreach ($modules as $mod) {
                $this->moduleVisibility[$mod] = true;
            }

            // Migrasi otomatis jika role masih memiliki key legacy input_in_out
            if (in_array('input_in_out', $modules)) {
                $this->moduleVisibility['input_incoming'] = true;
                $this->moduleVisibility['input_outgoing'] = true;
            }

            $rolePermissions = DB::table('role_permission')
                ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                ->where('role_permission.role_id', $role->id)
                ->get();

            foreach ($rolePermissions as $rp) {
                if (isset($this->permissionMatrix[$rp->code])) {
                    $this->permissionMatrix[$rp->code][$rp->sifat] = true;
                }
            }
        }
    }

    public function save()
    {
        $this->validate([
            'roleName' => 'required|string|max:255',
            'roleStatus' => 'required|in:active,inactive',
            'roleDescription' => 'nullable|string',
            'moduleVisibility' => 'nullable|array',
        ]);

        $isNew = !$this->role || !$this->role->exists;

        if ($isNew) {
            $this->role = new Role();
        }

        $this->role->name = $this->roleName;
        $this->role->status = $this->roleStatus;
        $this->role->description = $this->roleDescription;
        $this->role->module_visibility = collect($this->moduleVisibility)
            ->filter(fn($value) => $value)
            ->keys()
            ->toArray();
        $this->role->save();

        // Sync permissions
        $this->role->permissions()->detach();
        
        $permissions = Permission::all()->keyBy('code');
        
        foreach ($this->permissionMatrix as $actionCode => $sifats) {
            foreach ($sifats as $sifat => $isChecked) {
                if ($isChecked && isset($permissions[$actionCode])) {
                    $this->role->permissions()->attach($permissions[$actionCode]->id, [
                        'sifat' => $sifat
                    ]);
                }
            }
        }

        $action = $isNew ? "Pembuatan Role Baru: {$this->role->name}" : "Pembaruan Konfigurasi Role: {$this->role->name}";
        AuditLog::record(
            action: $action,
            documentId: null,
            metadata: [
                'role_id' => $this->role->id,
                'role_name' => $this->role->name,
                'status' => $this->role->status,
                'modules_count' => count($this->role->module_visibility ?? []),
            ]
        );

        session()->flash('success', "Role '{$this->role->name}' berhasil disimpan.");

        return redirect()->route('settings.roles.index');
    }

    public function render()
    {
        return view('livewire.settings.role-editor');
    }
}

