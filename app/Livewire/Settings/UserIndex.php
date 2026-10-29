<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Unit;
use App\Models\Position;
use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';

    public $userId;
    public $name;
    public $username;
    public $email;
    public $password;
    public $role_id;
    public $unit_id;
    public $position_id;
    public $status = 'active';

    public function boot()
    {
        $user = Auth::user();
        if (!$user?->isSuperAdmin() && !$user?->hasModuleAccess('roles') && $user?->role?->name !== 'Admin') {
            abort(403, 'Akses ditolak.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterUnit()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->userId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => $this->userId ? 'nullable|min:8' : 'required|min:8',
            'unit_id' => 'required|exists:units,id',
            'position_id' => 'required|exists:positions,id',
            'role_id' => 'required|exists:roles,id',
        ];
    }

    public function render()
    {
        $users = User::with(['unit', 'position', 'role'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('username', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterUnit, function ($query) {
                $query->where('unit_id', $this->filterUnit);
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.settings.user-index', [
            'users' => $users,
            'units' => Unit::all(),
            'positions' => Position::all(),
            'roles' => Role::all(),
        ]);
    }

    public function create()
    {
        $this->reset(['userId', 'name', 'username', 'email', 'password', 'role_id', 'unit_id', 'position_id']);
        $this->status = 'active';
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'slide-user');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->role_id = $user->role_id;
        $this->unit_id = $user->unit_id;
        $this->position_id = $user->position_id;
        $this->status = $user->status ?? 'active';
        $this->password = null;

        $this->resetValidation();
        $this->dispatch('open-modal', id: 'slide-user');
    }

    public function save()
    {
        $validatedData = $this->validate();

        if ($this->password) {
            $validatedData['password'] = Hash::make($this->password);
        } else {
            unset($validatedData['password']);
        }

        $validatedData['status'] = $this->status ?: 'active';
        $isNew = !$this->userId;

        $user = User::updateOrCreate(
            ['id' => $this->userId],
            $validatedData
        );

        $action = $isNew ? "Registrasi Pengguna Baru: {$user->name}" : "Pembaruan Data Pengguna: {$user->name}";
        AuditLog::record(
            action: $action,
            documentId: null,
            metadata: [
                'target_user_id' => $user->id,
                'username' => $user->username,
                'role_id' => $user->role_id,
                'unit_id' => $user->unit_id,
                'position_id' => $user->position_id,
                'status' => $user->status,
            ]
        );

        $msg = $isNew ? "Pengguna '{$user->name}' berhasil didaftarkan." : "Data pengguna '{$user->name}' berhasil diperbarui.";
        $this->dispatch('close-modal', id: 'slide-user');
        $this->dispatch('notify', ['message' => $msg, 'type' => 'success']);
    }

    public function delete($id)
    {
        if ($id == Auth::id()) {
            $this->dispatch('notify', ['message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.', 'type' => 'error']);
            return;
        }

        $user = User::findOrFail($id);

        if ($user->username === 'superadmin') {
            $this->dispatch('notify', ['message' => 'Akun Superadmin utama tidak dapat dihapus.', 'type' => 'error']);
            return;
        }

        $userName = $user->name;
        $userUsername = $user->username;
        $user->delete();

        AuditLog::record(
            action: "Penghapusan Pengguna: {$userName}",
            documentId: null,
            metadata: [
                'deleted_user_id' => $id,
                'username' => $userUsername,
            ]
        );

        $this->dispatch('notify', ['message' => "Pengguna '{$userName}' berhasil dihapus.", 'type' => 'success']);
    }
}
