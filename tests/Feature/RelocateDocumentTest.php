<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Livewire\Registry\Outgoing\Index as OutgoingIndex;
use App\Livewire\Registry\Incoming\Index as IncomingIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RelocateDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_authorized_user_can_relocate_document_with_nas_file_sync()
    {
        Storage::fake('nas');

        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::where('name', 'like', '%Surat Keluar%')->first();
        $unit = Unit::where('type', 'division')->first() ?? Unit::first();
        $classification = Classification::first();

        // 1. Buat Loker Asal dan Loker Tujuan
        $folderOrigin = Folder::create([
            'name' => 'Loker Keuangan 2026',
            'type' => 'outgoing',
        ]);

        $folderTarget = Folder::create([
            'name' => 'Loker Operasional 2026',
            'type' => 'outgoing',
        ]);

        // 2. Buat dokumen dengan file fisik di NAS
        $document = Document::create([
            'type' => 'outgoing',
            'number' => '01/SK/TEST/10/2026',
            'document_date' => now(),
            'unit_id' => $unit->id,
            'document_type_id' => $docType->id,
            'classification_id' => $classification->id,
            'folder_id' => $folderOrigin->id,
            'sifat' => 'biasa',
            'status' => 'archived',
            'subject' => 'Surat Pengujian Pemindahan Loker',
            'created_by' => $superadmin->id,
        ]);

        $initialDirectory = $document->getStorageDirectoryPath();
        $fileName = '01 SK TEST 10 2026.pdf';
        $initialFilePath = "{$initialDirectory}/{$fileName}";

        // Tulis file fisik dummy ke storage NAS
        Storage::disk('nas')->put($initialFilePath, '%PDF-1.4 dummy pdf content');
        Storage::disk('nas')->assertExists($initialFilePath);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'file_path' => $initialFilePath,
            'created_by' => $superadmin->id,
            'status' => 'active',
        ]);

        $document->update(['current_version_id' => $version->id]);

        // 3. Eksekusi pemindahan melalui Livewire Component
        Livewire::actingAs($superadmin)
            ->test(OutgoingIndex::class)
            ->call('openMoveModal', $document->id)
            ->assertSet('moveDocId', $document->id)
            ->assertSet('moveCurrentFolderName', 'Loker Keuangan 2026')
            ->set('moveTargetFolderId', $folderTarget->id)
            ->call('executeMoveDocument')
            ->assertDispatched('close-modal', 'modal-move-document')
            ->assertDispatched('notify');

        // 4. Verifikasi di Database
        $document->refresh();
        $version->refresh();
        $this->assertEquals($folderTarget->id, $document->folder_id);

        // 5. Verifikasi Sinkronisasi Fisik di Storage NAS
        $expectedNewDirectory = $document->getStorageDirectoryPath();
        $expectedNewFilePath = "{$expectedNewDirectory}/{$fileName}";

        Storage::disk('nas')->assertMissing($initialFilePath);
        Storage::disk('nas')->assertExists($expectedNewFilePath);
        $this->assertEquals($expectedNewFilePath, $version->file_path);

        // 6. Verifikasi Audit Log Tercatat
        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'user_id' => $superadmin->id,
        ]);

        $auditLog = AuditLog::where('document_id', $document->id)->latest('id')->first();
        $this->assertStringContainsString('Pemindahan Loker', $auditLog->action);
        $this->assertEquals('Loker Keuangan 2026', $auditLog->metadata['from_folder']);
        $this->assertEquals('Loker Operasional 2026', $auditLog->metadata['to_folder']);
    }

    public function test_user_without_archive_permission_cannot_relocate_document()
    {
        Storage::fake('nas');

        $roleStaff = Role::create([
            'name' => 'Staf Input Saja',
            'status' => 'active',
            'module_visibility' => ['dashboard', 'in_out_mail'],
        ]);

        $staff = User::create([
            'name' => 'Staf Biasa',
            'username' => 'stafbiasapindah',
            'email' => 'stafpindah@arsip.com',
            'password' => bcrypt('password'),
            'role_id' => $roleStaff->id,
            'status' => 'active',
        ]);

        $folder = Folder::create([
            'name' => 'Loker A',
            'type' => 'incoming',
        ]);

        $document = Document::create([
            'type' => 'incoming',
            'number' => '099/IN/2026',
            'document_date' => now(),
            'received_date' => now(),
            'sender' => 'Pengirim Luar',
            'subject' => 'Surat Uji Otorisasi',
            'sifat' => 'biasa',
            'status' => 'archived',
            'folder_id' => $folder->id,
            'created_by' => $staff->id,
        ]);

        // Staf tanpa hak arsip mencoba memindahkan loker
        Livewire::actingAs($staff)
            ->test(IncomingIndex::class)
            ->call('openMoveModal', $document->id)
            ->assertDispatched('notify');

        // Mencoba eksekusi pemindahan paksa
        $component = Livewire::actingAs($staff)
            ->test(IncomingIndex::class);

        $component->set('moveDocId', $document->id);
        $component->call('executeMoveDocument')
            ->assertStatus(403);
    }
}
