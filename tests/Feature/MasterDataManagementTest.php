<?php

namespace Tests\Feature;

use App\Livewire\Settings\ArchiveClassificationIndex;
use App\Livewire\Settings\OrganizationIndex;
use App\Models\AuditLog;
use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Position;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterDataManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_unit_with_audit_log_and_notify()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->call('createUnit')
            ->set('unitCode', 'DIV-SDM')
            ->set('unitName', 'Divisi Sumber Daya Manusia')
            ->set('unitType', 'division')
            ->set('unitStatus', 'active')
            ->call('saveUnit')
            ->assertDispatched('close-modal', id: 'slide-unit')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil ditambahkan');
            });

        $this->assertDatabaseHas('units', [
            'code' => 'DIV-SDM',
            'name' => 'Divisi Sumber Daya Manusia',
            'type' => 'division',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Pembuatan Unit Kerja: Divisi Sumber Daya Manusia (Divisi)',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_cannot_delete_division_with_child_departments()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $parentDivision = Unit::create([
            'code' => 'DIV-OPS-TEST',
            'name' => 'Divisi Operasional Uji',
            'type' => 'division',
            'status' => 'active',
        ]);

        $childDepartment = Unit::create([
            'code' => 'DEP-LOG-TEST',
            'name' => 'Departemen Logistik Uji',
            'type' => 'department',
            'parent_id' => $parentDivision->id,
            'status' => 'active',
        ]);

        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->call('deleteUnit', $parentDivision->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'membawahi');
            });

        $this->assertDatabaseHas('units', [
            'id' => $parentDivision->id,
        ]);
    }

    public function test_admin_cannot_delete_unit_with_active_users()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $roleUser = Role::where('name', 'User')->first();
        $position = Position::first();

        $unit = Unit::create([
            'code' => 'DEP-TEST-USER',
            'name' => 'Departemen Berpenghuni',
            'type' => 'department',
            'status' => 'active',
        ]);

        User::factory()->create([
            'unit_id' => $unit->id,
            'role_id' => $roleUser->id,
            'position_id' => $position->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->call('deleteUnit', $unit->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'memiliki');
            });

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
        ]);
    }

    public function test_admin_can_delete_unused_unit_with_audit_log_and_notify()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $unit = Unit::create([
            'code' => 'UNIT-KOSONG',
            'name' => 'Unit Kerja Tanpa Relasi',
            'type' => 'division',
            'status' => 'active',
        ]);

        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->call('deleteUnit', $unit->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil dihapus');
            });

        $this->assertSoftDeleted('units', [
            'id' => $unit->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Penghapusan Unit Kerja: Unit Kerja Tanpa Relasi',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_can_create_and_delete_position_with_audit_log_and_notify()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Tambah jabatan
        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->call('createPosition')
            ->set('posName', 'Staff Ahli Madya')
            ->set('posLevel', 3)
            ->set('posAuthority', 'unit_head')
            ->call('savePosition')
            ->assertDispatched('close-modal', id: 'slide-position')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil ditambahkan');
            });

        $pos = Position::where('name', 'Staff Ahli Madya')->first();
        $this->assertNotNull($pos);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Pembuatan Jabatan Baru: Staff Ahli Madya',
            'user_id' => $superadmin->id,
        ]);

        // 2. Hapus jabatan kosong
        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->call('deletePosition', $pos->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil dihapus');
            });

        $this->assertSoftDeleted('positions', [
            'id' => $pos->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Penghapusan Jabatan: Staff Ahli Madya',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_cannot_delete_classification_with_child_sub_classifications()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $rootClass = Classification::create([
            'code' => 'UM-TEST',
            'subject' => 'Klasifikasi Umum Induk',
            'active_years' => 2,
            'inactive_years' => 3,
            'outcome' => 'musnah',
        ]);

        $childClass = Classification::create([
            'code' => 'UM.01-TEST',
            'subject' => 'Sub Klasifikasi Surat Masuk',
            'parent_id' => $rootClass->id,
            'active_years' => 2,
            'inactive_years' => 3,
            'outcome' => 'musnah',
        ]);

        Livewire::actingAs($superadmin)
            ->test(ArchiveClassificationIndex::class)
            ->call('deleteClassification', $rootClass->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'membawahi');
            });

        $this->assertDatabaseHas('classifications', [
            'id' => $rootClass->id,
        ]);
    }

    public function test_admin_can_delete_unused_classification_with_audit_log_and_notify()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $unusedClass = Classification::create([
            'code' => 'TG.99-TEST',
            'subject' => 'Klasifikasi Khusus Percobaan',
            'active_years' => 1,
            'inactive_years' => 1,
            'outcome' => 'musnah',
        ]);

        Livewire::actingAs($superadmin)
            ->test(ArchiveClassificationIndex::class)
            ->call('deleteClassification', $unusedClass->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil dihapus');
            });

        $this->assertSoftDeleted('classifications', [
            'id' => $unusedClass->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Penghapusan Klasifikasi: TG.99-TEST - Klasifikasi Khusus Percobaan',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_admin_cannot_delete_doc_type_in_use()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $docType = DocumentType::create([
            'name' => 'Surat Edaran Direksi Uji',
            'code' => 'SED-UJI',
            'default_sifat' => 'biasa',
        ]);

        Document::create([
            'number' => '001/SED-UJI/2026',
            'subject' => 'Surat Edaran Libur Nasional',
            'type' => 'internal',
            'document_type_id' => $docType->id,
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ArchiveClassificationIndex::class)
            ->call('deleteDocType', $docType->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'masih digunakan');
            });

        $this->assertDatabaseHas('document_types', [
            'id' => $docType->id,
        ]);
    }

    public function test_admin_can_delete_unused_doc_type_with_audit_log_and_notify()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $unusedDocType = DocumentType::create([
            'name' => 'Dokumen Jenis Tidak Terpakai',
            'code' => 'DJT-UJI',
            'default_sifat' => 'biasa',
        ]);

        Livewire::actingAs($superadmin)
            ->test(ArchiveClassificationIndex::class)
            ->call('deleteDocType', $unusedDocType->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil dihapus');
            });

        $this->assertSoftDeleted('document_types', [
            'id' => $unusedDocType->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Penghapusan Jenis Dokumen: Dokumen Jenis Tidak Terpakai (DJT-UJI)',
            'user_id' => $superadmin->id,
        ]);
    }
}
