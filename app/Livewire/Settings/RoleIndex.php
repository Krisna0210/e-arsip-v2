<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Role;

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
        $role = Role::findOrFail($id);
        if ($role->users()->count() === 0) {
            $role->delete();
        }
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
