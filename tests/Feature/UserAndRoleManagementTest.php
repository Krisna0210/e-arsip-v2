<?php

namespace Tests\Feature;

use App\Livewire\Settings\RoleEditor;
use App\Livewire\Settings\RoleIndex;
use App\Livewire\Settings\UserIndex;
use App\Models\AuditLog;
use App\Models\Position;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserAndRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_user_with_audit_log_and_notify_event()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $position = Position::first();
        $roleUser = Role::where('name', 'User')->first();

        Livewire::actingAs($superadmin)
            ->test(UserIndex::class)
            ->set('name', 'Karyawan Baru BUMN')
            ->set('username', 'karyawan_bumn')
            ->set('email', 'karyawan.baru@perusahaan.co.id')
            ->set('password', 'password123')
            ->set('unit_id', $unit->id)
            ->set('position_id', $position->id)
            ->set('role_id', $roleUser->id)
            ->set('status', 'active')
            ->call('save')
            ->assertDispatched('close-modal', id: 'slide-user')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil didaftarkan');
            });

        $this->assertDatabaseHas('users', [
            'username' => 'karyawan_bumn',
            'email' => 'karyawan.baru@perusahaan.co.id',
            'name' => 'Karyawan Baru BUMN',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Registrasi Pengguna Baru: Karyawan Baru BUMN',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_cannot_delete_own_account()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        Livewire::actingAs($superadmin)
            ->test(UserIndex::class)
            ->call('delete', $superadmin->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'Anda tidak dapat menghapus akun Anda sendiri');
            });

        $this->assertDatabaseHas('users', [
            'id' => $superadmin->id,
            'username' => 'superadmin',
        ]);
    }

    public function test_admin_cannot_delete_superadmin_user()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $roleAdmin = Role::where('name', 'Admin')->first() ?? Role::where('name', 'User')->first();
        $unit = Unit::first();
        $position = Position::first();

        $otherAdmin = User::factory()->create([
            'username' => 'admin_kedua',
            'role_id' => $roleAdmin->id,
            'unit_id' => $unit->id,
            'position_id' => $position->id,
        ]);

        Livewire::actingAs($otherAdmin)
            ->test(UserIndex::class)
            ->call('delete', $superadmin->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'Superadmin utama tidak dapat dihapus');
            });

        $this->assertDatabaseHas('users', [
            'id' => $superadmin->id,
            'username' => 'superadmin',
        ]);
    }

    public function test_admin_can_delete_regular_user_with_audit_log_and_notify()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();
        $position = Position::first();

        $regularUser = User::factory()->create([
            'name' => 'Staf Akan Dihapus',
            'username' => 'staf_dihapus',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
            'position_id' => $position->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(UserIndex::class)
            ->call('delete', $regularUser->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil dihapus');
            });

        $this->assertSoftDeleted('users', [
            'id' => $regularUser->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Penghapusan Pengguna: Staf Akan Dihapus',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_cannot_delete_role_in_use()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();
        $position = Position::first();

        // Kaitkan user aktif ke role agar role_in_use terdeteksi
        User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
            'position_id' => $position->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(RoleIndex::class)
            ->call('deleteRole', $roleUser->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'tidak dapat dihapus karena masih digunakan');
            });

        $this->assertDatabaseHas('roles', [
            'id' => $roleUser->id,
        ]);
    }

    public function test_admin_can_delete_unused_role_with_audit_log_and_notify()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $unusedRole = Role::create([
            'name' => 'Role Magang Percobaan',
            'slug' => 'role-magang-percobaan',
            'status' => 'active',
            'description' => 'Role khusus uji coba tanpa user',
            'module_visibility' => ['dashboard'],
        ]);

        Livewire::actingAs($superadmin)
            ->test(RoleIndex::class)
            ->call('deleteRole', $unusedRole->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil dihapus');
            });

        $this->assertSoftDeleted('roles', [
            'id' => $unusedRole->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Penghapusan Role: Role Magang Percobaan',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_can_save_role_in_editor_with_audit_log_and_flash_session()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        Livewire::actingAs($superadmin)
            ->test(RoleEditor::class)
            ->set('roleName', 'Kepala Sub Bagian Umum')
            ->set('roleStatus', 'active')
            ->set('roleDescription', 'Pejabat struktural sub bagian umum')
            ->set('moduleVisibility', ['dashboard' => true, 'input_incoming' => true])
            ->call('save')
            ->assertRedirect(route('settings.roles.index'))
            ->assertSessionHas('success', "Role 'Kepala Sub Bagian Umum' berhasil disimpan.");

        $role = Role::where('name', 'Kepala Sub Bagian Umum')->first();
        $this->assertNotNull($role);
        $this->assertEquals(['dashboard', 'input_incoming'], $role->module_visibility);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Pembuatan Role Baru: Kepala Sub Bagian Umum',
            'user_id' => $superadmin->id,
        ]);
    }
}
