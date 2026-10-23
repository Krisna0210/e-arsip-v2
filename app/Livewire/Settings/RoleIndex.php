<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Role;
use App\Models\AuditLog;

class RoleIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function deleteRole($id)
    {
        $role = Role::withCount('users')->findOrFail($id);

        if ($role->slug === 'superadmin' || strtolower($role->name) === 'superadmin') {
            $this->dispatch('notify', ['message' => 'Role Superadmin sistem tidak dapat dihapus.', 'type' => 'error']);
            return;
        }

        if ($role->users_count > 0) {
            $this->dispatch('notify', [
                'message' => "Role '{$role->name}' tidak dapat dihapus karena masih digunakan oleh {$role->users_count} pengguna.",
                'type' => 'error'
            ]);
            return;
        }

        $roleName = $role->name;
        $role->delete();

        AuditLog::record(
            action: "Penghapusan Role: {$roleName}",
            documentId: null,
            metadata: [
                'deleted_role_id' => $id,
                'role_name' => $roleName,
            ]
        );

        $this->dispatch('notify', ['message' => "Role '{$roleName}' berhasil dihapus.", 'type' => 'success']);
    }

    public function render()
    {
        $roles = Role::query()
            ->withCount('users')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus, function ($query) {
                if ($this->filterStatus === 'active') {
                    $query->where('status', 'active');
                } elseif ($this->filterStatus === 'inactive') {
                    $query->where('status', 'inactive');
                }
            })
            ->paginate(10);

        return view('livewire.settings.role-index', [
            'roles' => $roles,
        ]);
    }
}
