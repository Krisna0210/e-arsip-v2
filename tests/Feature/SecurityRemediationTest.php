<?php

namespace Tests\Feature;

use App\Livewire\Archive\DestructionIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Registry\Incoming\Index as IncomingIndex;
use App\Livewire\Settings\RoleEditor;
use App\Livewire\Settings\RoleIndex;
use App\Livewire\Settings\UserIndex;
use App\Models\DestructionRecord;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        RateLimiter::clear('test@example.com|127.0.0.1');
    }

    public function test_inactive_user_cannot_login()
    {
        $role = Role::where('name', 'User')->first();
        $unit = Unit::first();

        $inactiveUser = User::factory()->create([
            'email' => 'inactive_user@example.com',
            'username' => 'inactive_user',
            'password' => Hash::make('password123'),
            'status' => 'inactive',
            'role_id' => $role->id,
            'unit_id' => $unit->id,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'inactive_user@example.com')
            ->set('password', 'password123')
            ->call('authenticate')
            ->assertHasErrors(['email']);

        $this->assertFalse(Auth::check());
    }

    public function test_login_rate_limiting_triggers_after_multiple_failures()
    {
        $component = Livewire::test(Login::class);

        for ($i = 0; $i < 5; $i++) {
            $component->set('email', 'wrong@example.com')
                ->set('password', 'wrongpassword')
                ->call('authenticate');
        }

        // 6th attempt should hit rate limit
        $component->set('email', 'wrong@example.com')
            ->set('password', 'wrongpassword')
            ->call('authenticate')
            ->assertSee('Terlalu banyak percobaan login');
    }

    public function test_unauthorized_user_cannot_access_role_editor_or_user_index()
    {
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();

        $regularUser = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        Livewire::actingAs($regularUser)
            ->test(RoleEditor::class)
            ->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_access_user_index()
    {
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();

        $regularUser = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        Livewire::actingAs($regularUser)
            ->test(UserIndex::class)
            ->assertStatus(403);
    }

    public function test_unapproved_destruction_cannot_be_executed()
    {
        Storage::fake('public');
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '999/TEST/2026',
            'subject' => 'Dokumen Belum Disetujui',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'archived',
            'retention_end_date' => now()->subDay()->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        // Record is in 'proposed' state, NOT 'approved'
        $record = DestructionRecord::create([
            'document_id' => $document->id,
            'proposer_id' => $superadmin->id,
            'status' => 'proposed',
            'reason' => 'Masih dalam usulan',
        ]);

        $baFile = UploadedFile::fake()->create('ba.pdf', 100, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->set('activeRecordId', $record->id)
            ->set('baFile', $baFile)
            ->call('executeDestruction')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'telah disetujui resmi');
            });

        $record->refresh();
        $this->assertEquals('proposed', $record->status);
    }

    public function test_confidential_document_is_hidden_from_unauthorized_staff_in_incoming_index()
    {
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();
        $superadmin = User::where('username', 'superadmin')->first();

        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        // Create confidential document created by superadmin
        $confidentialDoc = Document::create([
            'number' => '007/CONFIDENTIAL/2026',
            'subject' => 'Informasi Sangat Rahasia Perusahaan',
            'sender' => 'Direksi',
            'type' => 'incoming',
            'sifat' => 'rahasia',
            'status' => 'archived',
            'document_date' => date('Y-m-d'),
            'received_date' => date('Y-m-d'),
            'unit_id' => $unit->id,
            'created_by' => $superadmin->id,
        ]);

        // Create regular document
        $regularDoc = Document::create([
            'number' => '001/REGULAR/2026',
            'subject' => 'Surat Pengumuman Biasa',
            'sender' => 'HRD',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => date('Y-m-d'),
            'received_date' => date('Y-m-d'),
            'unit_id' => $unit->id,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($staff)
            ->test(IncomingIndex::class)
            ->assertSee('001/REGULAR/2026')
            ->assertDontSee('007/CONFIDENTIAL/2026');
    }

    public function test_document_preview_aborts_404_if_document_is_orphaned()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        Storage::fake('nas');
        Storage::disk('nas')->put('documents/test.pdf', 'dummy content');

        $doc = Document::create([
            'number' => '888/ORPHAN/2026',
            'subject' => 'Surat Akan Dihapus',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => date('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'file_path' => 'documents/test.pdf',
            'created_by' => $superadmin->id,
            'status' => 'active',
        ]);

        // Soft delete the parent document
        $doc->delete();

        $response = $this->actingAs($superadmin)->get(route('document.preview', $version->id));
        $response->assertStatus(404);
    }
}
