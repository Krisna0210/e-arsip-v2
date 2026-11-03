<?php

namespace Tests\Feature;

use App\Livewire\Library\DigitalLibraryIndex;
use App\Models\DigitalDocument;
use App\Models\DigitalFolder;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DigitalLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_document_number_generation_follows_specified_format()
    {
        $currentYear = (int) date('Y');

        $num1 = DigitalDocument::generateDocumentNumber($currentYear);
        $this->assertEquals("001-ARSIP-DIG-{$currentYear}", $num1);

        // Simulasi pembuatan naskah 1
        DigitalDocument::create([
            'title' => 'As-Built Drawing Gedung A',
            'document_number' => $num1,
            'category' => 'As-Built Drawing',
            'year' => $currentYear,
            'file_path' => 'library/as-built-drawing/2026/test.pdf',
            'file_name' => 'test.pdf',
            'file_size' => 1024,
        ]);

        $num2 = DigitalDocument::generateDocumentNumber($currentYear);
        $this->assertEquals("002-ARSIP-DIG-{$currentYear}", $num2);

        // Uji tahun lampau (misal 2020)
        $num2020 = DigitalDocument::generateDocumentNumber(2020);
        $this->assertEquals("001-ARSIP-DIG-2020", $num2020);
    }

    public function test_user_can_upload_digital_document_with_specified_metadata_attributes()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $folder = DigitalFolder::create([
            'name' => 'Proyek Gedung Pusat 2026',
            'created_by' => $superadmin->id,
        ]);

        $fakePdf = UploadedFile::fake()->create('denah_lantai_1.pdf', 2048, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openUploadModal', $folder->id)
            ->set('uploadTitle', 'As-Built Drawing Arsitektur Lantai 1')
            ->set('uploadCategory', 'As-Built Drawing')
            ->set('uploadYear', 2026)
            ->set('uploadUnitId', $unit->id)
            ->set('uploadFolderId', $folder->id)
            ->set('uploadTags', '#drawing #arsitektur #gedung-a')
            ->set('uploadDescription', 'Revisi final per 30 Oktober 2026')
            ->set('uploadFile', $fakePdf)
            ->call('saveDocument')
            ->assertDispatched('close-modal', 'modal-upload-digital-doc')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('digital_documents', [
            'title' => 'As-Built Drawing Arsitektur Lantai 1',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'As-Built Drawing',
            'year' => 2026,
            'unit_id' => $unit->id,
            'folder_id' => $folder->id,
            'tags' => '#drawing #arsitektur #gedung-a',
            'file_name' => 'denah_lantai_1.pdf',
        ]);

        // Verifikasi file tersimpan di NAS storage
        $doc = DigitalDocument::where('document_number', '001-ARSIP-DIG-2026')->first();
        $this->assertNotNull($doc);
        Storage::disk('nas')->assertExists($doc->file_path);

        // Verifikasi Audit Trail
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Unggah Berkas Pustaka Digital: 001-ARSIP-DIG-2026 - As-Built Drawing Arsitektur Lantai 1 (public)',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_user_can_create_folder_hierarchy_and_filter_documents_by_folder()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Buat folder via Livewire
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openCreateFolderModal')
            ->set('folderName', 'Dokumen Kontrak Multiyears')
            ->set('folderDescription', 'Kumpulan kontrak proyek 2020-2026')
            ->call('saveFolder')
            ->assertDispatched('close-modal', 'modal-folder-editor')
            ->assertDispatched('notify');

        $folder = DigitalFolder::where('name', 'Dokumen Kontrak Multiyears')->first();
        $this->assertNotNull($folder);

        // 2. Buat dokumen di dalam folder dan dokumen di luar folder
        $docInFolder = DigitalDocument::create([
            'title' => 'Kontrak Pembangunan Tahap 1',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'Kontrak/Perjanjian',
            'year' => 2026,
            'folder_id' => $folder->id,
            'file_path' => 'library/kontrak-perjanjian/2026/kontrak1.pdf',
            'file_name' => 'kontrak1.pdf',
            'file_size' => 1024,
            'created_by' => $superadmin->id,
        ]);

        $docOutsideFolder = DigitalDocument::create([
            'title' => 'Manual Book Genset Gedung',
            'document_number' => '002-ARSIP-DIG-2026',
            'category' => 'Manual/Pedoman',
            'year' => 2026,
            'folder_id' => null,
            'file_path' => 'library/manual-pedoman/2026/manual.pdf',
            'file_name' => 'manual.pdf',
            'file_size' => 1024,
            'created_by' => $superadmin->id,
        ]);

        // 3. Uji filter folder aktif
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('selectFolder', $folder->id)
            ->assertSet('activeFolderId', $folder->id)
            ->assertSee($docInFolder->document_number)
            ->assertSee($docInFolder->title)
            ->assertDontSee($docOutsideFolder->document_number);
    }

    public function test_multifaceted_search_and_hashtag_filtering()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $doc1 = DigitalDocument::create([
            'title' => 'Cetak Biru Jalur Pipa Sanitasi Air',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'As-Built Drawing',
            'year' => 2026,
            'tags' => '#plumbing #instalasi-air #gedung-utama',
            'file_path' => 'library/as-built-drawing/2026/doc1.pdf',
            'file_name' => 'doc1.pdf',
            'file_size' => 1024,
            'created_by' => $superadmin->id,
        ]);

        $doc2 = DigitalDocument::create([
            'title' => 'Sertifikat Hak Guna Bangunan Lahan A',
            'document_number' => '002-ARSIP-DIG-2026',
            'category' => 'Dokumen Hukum/Aset',
            'year' => 2020,
            'tags' => '#legalitas #tanah #aset-pusat',
            'file_path' => 'library/dokumen-hukum-aset/2020/doc2.pdf',
            'file_name' => 'doc2.pdf',
            'file_size' => 1024,
            'created_by' => $superadmin->id,
        ]);

        // Cari berdasarkan tagar #instalasi-air
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('filterByTag', 'instalasi-air')
            ->assertSet('selectedTag', 'instalasi-air')
            ->assertSee($doc1->document_number)
            ->assertDontSee($doc2->document_number);

        // Cari berdasarkan filter Kategori Dokumen Hukum/Aset
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->set('filterCategory', 'Dokumen Hukum/Aset')
            ->assertSee($doc2->document_number)
            ->assertDontSee($doc1->document_number);

        // Cari berdasarkan Tahun 2020
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->set('filterYear', '2020')
            ->assertSee($doc2->document_number)
            ->assertDontSee($doc1->document_number);
    }

    public function test_document_preview_and_download_endpoints()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();

        $content = '%PDF-1.4 Fake PDF Content for digital library';
        $filePath = 'library/as-built-drawing/2026/001-ARSIP-DIG-2026_test.pdf';
        Storage::disk('nas')->put($filePath, $content);

        $doc = DigitalDocument::create([
            'title' => 'Denah Instalasi Listrik',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'As-Built Drawing',
            'year' => 2026,
            'file_path' => $filePath,
            'file_name' => 'denah_listrik.pdf',
            'file_size' => strlen($content),
            'mime_type' => 'application/pdf',
            'created_by' => $superadmin->id,
        ]);

        // 1. Endpoint Preview
        $previewResp = $this->actingAs($superadmin)->get(route('library.preview', $doc->id));
        $previewResp->assertStatus(200);

        // 2. Endpoint Download
        $downloadResp = $this->actingAs($superadmin)->get(route('library.download', $doc->id));
        $downloadResp->assertStatus(200);
        $downloadResp->assertHeader('content-disposition', 'attachment; filename=' . $doc->system_file_name);
    }

    public function test_unauthorized_user_cannot_access_digital_library_module()
    {
        $roleNoLibrary = Role::create([
            'name' => 'StafKandidat',
            'status' => 'active',
            'module_visibility' => ['dashboard'], // Tidak punya digital_library
        ]);

        $user = User::factory()->create([
            'role_id' => $roleNoLibrary->id,
            'unit_id' => Unit::first()->id,
        ]);

        $response = $this->actingAs($user)->get(route('library.index'));
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_action_column_renders_icon_buttons_for_preview_download_and_delete()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $doc = DigitalDocument::create([
            'title' => 'As-Built Drawing Mekanikal',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'As-Built Drawing',
            'year' => 2026,
            'file_path' => 'library/as-built-drawing/2026/mep.pdf',
            'file_name' => 'mep.pdf',
            'file_size' => 2048,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->assertSeeHtml('title="Pratinjau Berkas"')
            ->assertSeeHtml('title="Unduh Berkas"')
            ->assertSeeHtml('title="Pindahkan Folder Berkas"')
            ->assertSeeHtml('title="Hapus Berkas"')
            ->assertSeeHtml('wire:click="openPreviewModal(' . $doc->id . ')')
            ->assertSeeHtml(route('library.download', $doc->id))
            ->assertSeeHtml('wire:click="confirmDelete(' . $doc->id . ')');
    }

    public function test_preview_modal_renders_security_shield_and_forensic_watermark()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Dokumen Privat: Memuat watermark forensik & badge Dokumen Dilindungi
        $privDoc = DigitalDocument::create([
            'title' => 'Dokumen Rencana Rahasia',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'As-Built Drawing',
            'access_type' => 'private',
            'year' => 2026,
            'file_path' => 'library/as-built-drawing/2026/sec.pdf',
            'file_name' => 'sec.pdf',
            'file_size' => 1024,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openPreviewModal', $privDoc->id)
            ->assertSeeHtml('@contextmenu.prevent')
            ->assertSeeHtml('@media print')
            ->assertSeeHtml('pattern id="forensic-watermark"')
            ->assertSeeHtml('Dokumen Dilindungi')
            ->assertSeeHtml('Tampilan Pratinjau Diamankan')
            ->assertSee($superadmin->name);

        // 2. Dokumen Publik: Tampilan jernih TANPA watermark pattern
        $pubDoc = DigitalDocument::create([
            'title' => 'Manual Book Terbuka Umum',
            'document_number' => '002-ARSIP-DIG-2026',
            'category' => 'Manual/Pedoman',
            'access_type' => 'public',
            'year' => 2026,
            'file_path' => 'library/manual/pub.pdf',
            'file_name' => 'pub.pdf',
            'file_size' => 1024,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openPreviewModal', $pubDoc->id)
            ->assertSeeHtml('Dokumen Terbuka')
            ->assertDontSeeHtml('pattern id="forensic-watermark"');
    }

    public function test_user_with_flexibility_permission_can_move_document_between_folders()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $folderA = DigitalFolder::create(['name' => 'Folder Asal', 'created_by' => $superadmin->id]);
        $folderB = DigitalFolder::create(['name' => 'Folder Tujuan', 'created_by' => $superadmin->id]);

        $doc = DigitalDocument::create([
            'title' => 'Gambar Struktur Pondasi',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'As-Built Drawing',
            'year' => 2026,
            'folder_id' => $folderA->id,
            'file_path' => 'library/as-built-drawing/2026/pondasi.pdf',
            'file_name' => 'pondasi.pdf',
            'file_size' => 2048,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openMoveDocModal', $doc->id)
            ->assertSet('moveDocId', $doc->id)
            ->assertDispatched('open-modal', 'modal-move-digital-doc')
            ->set('moveTargetFolderId', $folderB->id)
            ->call('executeMoveDoc')
            ->assertDispatched('close-modal', 'modal-move-digital-doc')
            ->assertDispatched('notify');

        $doc->refresh();
        $this->assertEquals($folderB->id, $doc->folder_id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => "Pemindahan Berkas Pustaka Digital: 001-ARSIP-DIG-2026 ke folder 'Folder Tujuan'",
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_user_can_move_folder_with_cycle_protection()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $rootFolder = DigitalFolder::create(['name' => 'Proyek Kantor 2020', 'created_by' => $superadmin->id]);
        $subFolder = DigitalFolder::create(['name' => 'Mekanikal Elektrikal', 'parent_id' => $rootFolder->id, 'created_by' => $superadmin->id]);
        $targetRoot = DigitalFolder::create(['name' => 'Arsip Induk Baru', 'created_by' => $superadmin->id]);

        // 1. Pindahkan subFolder (yang tidak memiliki anak) ke targetRoot
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openMoveFolderModal', $subFolder->id)
            ->assertSet('moveFolderId', $subFolder->id)
            ->assertDispatched('open-modal', 'modal-move-digital-folder')
            ->set('moveTargetParentId', $targetRoot->id)
            ->call('executeMoveFolder')
            ->assertDispatched('close-modal', 'modal-move-digital-folder')
            ->assertDispatched('notify');

        $subFolder->refresh();
        $this->assertEquals($targetRoot->id, $subFolder->parent_id);

        // 2. Uji Cycle Protection: Tidak boleh memindahkan folder ke dalam dirinya sendiri
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openMoveFolderModal', $subFolder->id)
            ->set('moveTargetParentId', $subFolder->id)
            ->call('executeMoveFolder')
            ->assertHasErrors(['moveTargetParentId']);
    }

    public function test_parent_folder_with_subfolders_cannot_be_moved_or_deleted()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $parentFolder = DigitalFolder::create(['name' => 'Folder Induk Utama', 'created_by' => $superadmin->id]);
        $childFolder = DigitalFolder::create(['name' => 'Subfolder Turunan', 'parent_id' => $parentFolder->id, 'created_by' => $superadmin->id]);
        $destination = DigitalFolder::create(['name' => 'Folder Alternatif', 'created_by' => $superadmin->id]);

        // 1. Percobaan memindahkan folder induk yang memiliki subfolder -> HARUS DITOLAK
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openMoveFolderModal', $parentFolder->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'memiliki subfolder sehingga tidak dapat dipindahkan');
            });

        // 2. Percobaan menghapus folder induk yang memiliki subfolder -> HARUS DITOLAK
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('confirmDeleteFolder', $parentFolder->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'memiliki subfolder sehingga tidak dapat dihapus');
            });

        // 3. Pastikan folder induk masih ada di database
        $this->assertDatabaseHas('digital_folders', ['id' => $parentFolder->id]);

        // 4. Setelah subfolder dipindahkan ke folder lain (sehingga folder induk tidak lagi punya subfolder)
        $childFolder->update(['parent_id' => $destination->id]);

        // Sekarang folder induk dapat dipindahkan dan dihapus
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openMoveFolderModal', $parentFolder->id)
            ->assertDispatched('open-modal', 'modal-move-digital-folder')
            ->call('confirmDeleteFolder', $parentFolder->id)
            ->assertDispatched('open-modal', 'modal-delete-digital-folder');
    }

    public function test_folder_deletion_safely_preserves_documents_without_data_loss()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $parentFolder = DigitalFolder::create(['name' => 'Parent Folder', 'created_by' => $superadmin->id]);
        $targetFolder = DigitalFolder::create(['name' => 'Folder Dihapus', 'parent_id' => $parentFolder->id, 'created_by' => $superadmin->id]);

        $doc = DigitalDocument::create([
            'title' => 'Berkas Yang Diamankan',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'Kontrak/Perjanjian',
            'year' => 2026,
            'folder_id' => $targetFolder->id,
            'file_path' => 'library/kontrak/2026/aman.pdf',
            'file_name' => 'aman.pdf',
            'file_size' => 1024,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('confirmDeleteFolder', $targetFolder->id)
            ->assertSet('deleteFolderId', $targetFolder->id)
            ->assertDispatched('open-modal', 'modal-delete-digital-folder')
            ->call('executeDeleteFolder')
            ->assertDispatched('close-modal', 'modal-delete-digital-folder')
            ->assertDispatched('notify');

        // Pastikan targetFolder sudah terhapus
        $this->assertDatabaseMissing('digital_folders', ['id' => $targetFolder->id]);

        // ZERO DATA LOSS: Pastikan dokumen TIDAK hilang, melainkan dialihkan ke parentFolder
        $doc->refresh();
        $this->assertEquals($parentFolder->id, $doc->folder_id);
    }

    public function test_user_without_library_flexibility_permission_is_denied()
    {
        $roleBasic = Role::create([
            'name' => 'StafPustakaBiasa',
            'status' => 'active',
            'module_visibility' => ['digital_library'], // Punya digital_library, tapi TIDAK punya library_flexibility
        ]);

        $user = User::factory()->create([
            'role_id' => $roleBasic->id,
            'unit_id' => Unit::first()->id,
        ]);

        $folder = DigitalFolder::create(['name' => 'Folder Uji', 'created_by' => $user->id]);
        $doc = DigitalDocument::create([
            'title' => 'Dokumen Terproteksi',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'Manual/Pedoman',
            'year' => 2026,
            'folder_id' => $folder->id,
            'file_path' => 'library/manual/2026/prot.pdf',
            'file_name' => 'prot.pdf',
            'file_size' => 1024,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(DigitalLibraryIndex::class)
            ->call('openMoveDocModal', $doc->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error';
            })
            ->call('openMoveFolderModal', $folder->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error';
            })
            ->call('confirmDeleteFolder', $folder->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error';
            });
    }

    public function test_user_can_upload_private_document()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();
        $fakePdf = UploadedFile::fake()->create('rahasia_aset.pdf', 1024, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openUploadModal')
            ->set('uploadTitle', 'Sertifikat Rahasia Hak Guna Lahan B')
            ->set('uploadCategory', 'Dokumen Hukum/Aset')
            ->set('uploadAccessType', 'private')
            ->set('uploadYear', 2026)
            ->set('uploadFile', $fakePdf)
            ->call('saveDocument')
            ->assertDispatched('close-modal', 'modal-upload-digital-doc')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('digital_documents', [
            'title' => 'Sertifikat Rahasia Hak Guna Lahan B',
            'access_type' => 'private',
            'category' => 'Dokumen Hukum/Aset',
        ]);
    }

    public function test_private_document_access_request_approval_and_time_based_expiration()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();

        $roleStaff = Role::create([
            'name' => 'StafStaffBiasa',
            'status' => 'active',
            'module_visibility' => ['digital_library'],
        ]);

        $staff = User::factory()->create([
            'role_id' => $roleStaff->id,
            'unit_id' => Unit::first()->id,
        ]);

        $content = '%PDF-1.4 Private Content';
        $filePath = 'library/dokumen-hukum-aset/2026/001-ARSIP-DIG-2026_privat.pdf';
        Storage::disk('nas')->put($filePath, $content);

        // Dokumen dibuat oleh Superadmin dengan tipe private
        $privDoc = DigitalDocument::create([
            'title' => 'Dokumen Kontrak Rahasia',
            'document_number' => '001-ARSIP-DIG-2026',
            'category' => 'Dokumen Hukum/Aset',
            'access_type' => 'private',
            'year' => 2026,
            'file_path' => $filePath,
            'file_name' => 'privat.pdf',
            'file_size' => strlen($content),
            'mime_type' => 'application/pdf',
            'created_by' => $superadmin->id,
        ]);

        // 1. Staf biasa melihat tombol "Privat" dan tidak bisa langsung pratinjau / unduh
        Livewire::actingAs($staff)
            ->test(DigitalLibraryIndex::class)
            ->assertSee('Privat')
            ->assertDontSeeHtml('title="Pratinjau Berkas"');

        // Akses langsung ke rute ditolak 403
        $this->actingAs($staff)->get(route('library.preview', $privDoc->id))->assertStatus(403);
        $this->actingAs($staff)->get(route('library.download', $privDoc->id))->assertStatus(403);

        // 2. Staf mengajukan izin akses berkas privat
        Livewire::actingAs($staff)
            ->test(DigitalLibraryIndex::class)
            ->call('openRequestAccessModal', $privDoc->id)
            ->assertSet('requestAccessDocId', $privDoc->id)
            ->assertDispatched('open-modal', 'modal-request-private-access')
            ->set('requestAccessReason', 'Membutuhkan acuan pasal kontrak untuk verifikasi audit keuangan.')
            ->call('submitRequestAccess')
            ->assertDispatched('close-modal', 'modal-request-private-access')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('digital_document_access_requests', [
            'digital_document_id' => $privDoc->id,
            'user_id' => $staff->id,
            'status' => 'pending',
            'reason' => 'Membutuhkan acuan pasal kontrak untuk verifikasi audit keuangan.',
        ]);

        $req = \App\Models\DigitalDocumentAccessRequest::where('digital_document_id', $privDoc->id)->where('user_id', $staff->id)->first();
        $this->assertNotNull($req);

        // Setelah diajukan, staf melihat status "Menunggu Persetujuan"
        Livewire::actingAs($staff)
            ->test(DigitalLibraryIndex::class)
            ->assertSee('Menunggu Persetujuan');

        // 3. Tapersip menyetujui izin akses dengan durasi 24 jam
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->set('activeTab', 'pending_approvals')
            ->assertSee($staff->name)
            ->assertSee($privDoc->document_number)
            ->call('openApproveAccessModal', $req->id)
            ->assertSet('approvalRequestId', $req->id)
            ->assertDispatched('open-modal', 'modal-approve-private-access')
            ->set('approvalDurationHours', 24)
            ->call('executeApproveAccess')
            ->assertDispatched('close-modal', 'modal-approve-private-access')
            ->assertDispatched('notify');

        $req->refresh();
        $this->assertEquals('approved', $req->status);
        $this->assertEquals(24, $req->duration_hours);
        $this->assertTrue($req->expires_at->isFuture());

        // 4. Staf sekarang dapat melihat tombol Pratinjau aktif dan dapat membuka file
        Livewire::actingAs($staff)
            ->test(DigitalLibraryIndex::class)
            ->assertSeeHtml('title="Pratinjau Berkas"');

        $this->actingAs($staff)->get(route('library.preview', $privDoc->id))->assertStatus(200);
        $this->actingAs($staff)->get(route('library.download', $privDoc->id))->assertStatus(200);

        // 5. Uji Batas Waktu Habis (Kedaluwarsa): Set expires_at ke masa lalu
        $req->update(['expires_at' => now()->subMinute()]);

        // Akses langsung rute kembali ditolak 403
        $this->actingAs($staff)->get(route('library.preview', $privDoc->id))->assertStatus(403);
        $this->actingAs($staff)->get(route('library.download', $privDoc->id))->assertStatus(403);

        // Tombol di UI kembali terkunci menjadi "Privat"
        Livewire::actingAs($staff)
            ->test(DigitalLibraryIndex::class)
            ->assertSee('Privat')
            ->assertDontSeeHtml('title="Pratinjau Berkas"');
    }

    public function test_private_document_access_rejection_workflow()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $roleStaff = Role::create([
            'name' => 'StafKaryawan',
            'status' => 'active',
            'module_visibility' => ['digital_library'],
        ]);

        $staff = User::factory()->create([
            'role_id' => $roleStaff->id,
            'unit_id' => Unit::first()->id,
        ]);

        $privDoc = DigitalDocument::create([
            'title' => 'Dokumen Rahasia Lainnya',
            'document_number' => '002-ARSIP-DIG-2026',
            'category' => 'Kontrak/Perjanjian',
            'access_type' => 'private',
            'year' => 2026,
            'file_path' => 'library/kontrak/test.pdf',
            'file_name' => 'test.pdf',
            'file_size' => 1024,
            'created_by' => $superadmin->id,
        ]);

        $req = \App\Models\DigitalDocumentAccessRequest::create([
            'digital_document_id' => $privDoc->id,
            'user_id' => $staff->id,
            'reason' => 'Ingin melihat untuk kepo',
            'status' => 'pending',
        ]);

        // Tapersip menolak permohonan akses
        Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('openRejectAccessModal', $req->id)
            ->assertSet('rejectionRequestId', $req->id)
            ->assertDispatched('open-modal', 'modal-reject-private-access')
            ->set('rejectionReasonNotes', 'Alasan pengajuan tidak berkaitan dengan tugas dinas.')
            ->call('executeRejectAccess')
            ->assertDispatched('close-modal', 'modal-reject-private-access')
            ->assertDispatched('notify');

        $req->refresh();
        $this->assertEquals('rejected', $req->status);
        $this->assertEquals('Alasan pengajuan tidak berkaitan dengan tugas dinas.', $req->rejection_reason);

        // Staf melihat status penolakan di tab "my_requests"
        Livewire::actingAs($staff)
            ->test(DigitalLibraryIndex::class)
            ->set('activeTab', 'my_requests')
            ->assertSee('Ditolak')
            ->assertSee('Alasan pengajuan tidak berkaitan dengan tugas dinas.');
    }

    public function test_user_with_upload_digital_library_permission_can_create_folder_and_upload()
    {
        Storage::fake('nas');

        $roleUploader = Role::create([
            'name' => 'StafPengunggah',
            'status' => 'active',
            'module_visibility' => ['digital_library', 'upload_digital_library'],
        ]);

        $uploader = User::factory()->create([
            'role_id' => $roleUploader->id,
            'unit_id' => Unit::first()->id,
        ]);

        $fakePdf = UploadedFile::fake()->create('berkas_teknik.pdf', 1024, 'application/pdf');

        Livewire::actingAs($uploader)
            ->test(DigitalLibraryIndex::class)
            ->assertSee('Folder Baru')
            ->assertSee('Tambah Berkas')
            ->call('openCreateFolderModal')
            ->assertDispatched('open-modal', 'modal-folder-editor')
            ->set('folderName', 'Folder Baru Staf')
            ->call('saveFolder')
            ->assertDispatched('close-modal', 'modal-folder-editor')
            ->assertDispatched('notify')
            ->call('openUploadModal')
            ->assertDispatched('open-modal', 'modal-upload-digital-doc')
            ->set('uploadTitle', 'Berkas Gambar Teknik Baru')
            ->set('uploadCategory', 'As-Built Drawing')
            ->set('uploadYear', 2026)
            ->set('uploadFile', $fakePdf)
            ->call('saveDocument')
            ->assertDispatched('close-modal', 'modal-upload-digital-doc')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('digital_folders', ['name' => 'Folder Baru Staf']);
        $this->assertDatabaseHas('digital_documents', ['title' => 'Berkas Gambar Teknik Baru']);
    }

    public function test_user_without_upload_digital_library_permission_cannot_create_folder_or_upload()
    {
        $roleReader = Role::create([
            'name' => 'StafPembacaPustaka',
            'status' => 'active',
            'module_visibility' => ['digital_library'], // Tanpa upload_digital_library
        ]);

        $reader = User::factory()->create([
            'role_id' => $roleReader->id,
            'unit_id' => Unit::first()->id,
        ]);

        Livewire::actingAs($reader)
            ->test(DigitalLibraryIndex::class)
            ->assertDontSee('Folder Baru')
            ->assertDontSee('Tambah Berkas')
            ->call('openCreateFolderModal')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'membuat folder baru');
            })
            ->call('openUploadModal')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'mengunggah berkas baru');
            });
    }

    public function test_private_access_request_triggers_notification_and_increments_tapersip_pending_count()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $roleStaff = Role::create([
            'name' => 'StafKaryawanNotif',
            'status' => 'active',
            'module_visibility' => ['digital_library'],
        ]);

        $staff = User::factory()->create([
            'role_id' => $roleStaff->id,
            'unit_id' => Unit::first()->id,
        ]);

        $privDoc = DigitalDocument::create([
            'title' => 'Dokumen Peta Jalur Pipa Minyak',
            'document_number' => '009-ARSIP-DIG-2026',
            'category' => 'As-Built Drawing',
            'access_type' => 'private',
            'year' => 2026,
            'file_path' => 'library/drawing/pipa.pdf',
            'file_name' => 'pipa.pdf',
            'file_size' => 1024,
            'created_by' => $superadmin->id,
        ]);

        // 1. Staf mengajukan akses privat, komponen mendispatch desktop-notify & loan-status-changed
        Livewire::actingAs($staff)
            ->test(DigitalLibraryIndex::class)
            ->call('openRequestAccessModal', $privDoc->id)
            ->set('requestAccessReason', 'Untuk rujukan pemeliharaan teknis di lapangan.')
            ->call('submitRequestAccess')
            ->assertDispatched('desktop-notify', function ($name, $params) use ($privDoc) {
                $data = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($data['type'] ?? '') === 'private_access_requested'
                    && ($data['loan_code'] ?? '') === $privDoc->document_number;
            })
            ->assertDispatched('loan-status-changed');

        $req = \App\Models\DigitalDocumentAccessRequest::where('digital_document_id', $privDoc->id)->first();
        $this->assertNotNull($req);

        // 2. Komponen TapersipNotificationListener menghitung pendingCount dan menampilkan notifikasi
        $tapersipComponent = Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Components\TapersipNotificationListener::class);

        $initialPending = $tapersipComponent->get('pendingCount');
        $this->assertGreaterThan(0, $initialPending);

        $unreadItems = collect($tapersipComponent->viewData('unreadItems'));
        $targetItem = $unreadItems->firstWhere('key', "priv_access_{$req->id}");

        $this->assertNotNull($targetItem);
        $this->assertEquals('Akses Privat', $targetItem['badge']);
        $this->assertEquals($privDoc->document_number, $targetItem['identifier']);
        $this->assertStringContainsString("highlight=req-{$req->id}", $targetItem['target_url']);
        $this->assertStringContainsString('tab=pending_approvals', $targetItem['target_url']);

        // 3. Tapersip membaca / mendismiss notifikasi
        $tapersipComponent->call('dismissAndRedirect', "priv_access_{$req->id}", $targetItem['target_url'])
            ->assertSet('pendingCount', $initialPending - 1);
    }

    public function test_deep_nested_4_level_folder_hierarchy_keeps_ancestors_expanded_when_subfolder_active()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Buat 4 level hierarki folder: Root -> Sub 1 -> Sub 2 -> Sub 3 -> Sub 4
        $root = DigitalFolder::create(['name' => '01. Induk Master', 'created_by' => $superadmin->id]);
        $sub1 = DigitalFolder::create(['name' => '02. Subfolder 1', 'parent_id' => $root->id, 'created_by' => $superadmin->id]);
        $sub2 = DigitalFolder::create(['name' => '03. Subfolder 2', 'parent_id' => $sub1->id, 'created_by' => $superadmin->id]);
        $sub3 = DigitalFolder::create(['name' => '04. Subfolder 3', 'parent_id' => $sub2->id, 'created_by' => $superadmin->id]);
        $sub4 = DigitalFolder::create(['name' => '05. Subfolder 4', 'parent_id' => $sub3->id, 'created_by' => $superadmin->id]);

        // Verifikasi getAncestorIds()
        $this->assertEquals([$sub3->id, $sub2->id, $sub1->id, $root->id], $sub4->getAncestorIds());
        $this->assertEquals([$sub2->id, $sub1->id, $root->id], $sub3->getAncestorIds());

        // Buat dokumen di dalam Subfolder 4
        $doc = DigitalDocument::create([
            'title' => 'Berkas Spesifikasi Teknis Level 4',
            'document_number' => '099-ARSIP-DIG-2026',
            'category' => 'As-Built Drawing',
            'year' => 2026,
            'folder_id' => $sub4->id,
            'file_path' => 'library/as-built-drawing/2026/spek.pdf',
            'file_name' => 'spek.pdf',
            'file_size' => 2048,
            'created_by' => $superadmin->id,
        ]);

        // Uji saat Subfolder 4 aktif: seluruh leluhur harus masuk ke expandedFolderIds
        $test = Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('selectFolder', $sub4->id)
            ->assertSet('activeFolderId', $sub4->id)
            ->assertSee($doc->document_number)
            ->assertSee('05. Subfolder 4')
            ->assertSee('04. Subfolder 3')
            ->assertSee('03. Subfolder 2')
            ->assertSee('02. Subfolder 1')
            ->assertSee('01. Induk Master');

        $expandedIds = $test->viewData('expandedFolderIds');
        $this->assertContains($root->id, $expandedIds);
        $this->assertContains($sub1->id, $expandedIds);
        $this->assertContains($sub2->id, $expandedIds);
        $this->assertContains($sub3->id, $expandedIds);
        $this->assertContains($sub4->id, $expandedIds);

        // Uji saat beralih ke Subfolder 3: Root, Sub 1, Sub 2, dan Sub 3 tetap expanded
        $test2 = Livewire::actingAs($superadmin)
            ->test(DigitalLibraryIndex::class)
            ->call('selectFolder', $sub3->id)
            ->assertSet('activeFolderId', $sub3->id);

        $expandedIds3 = $test2->viewData('expandedFolderIds');
        $this->assertContains($root->id, $expandedIds3);
        $this->assertContains($sub1->id, $expandedIds3);
        $this->assertContains($sub2->id, $expandedIds3);
        $this->assertContains($sub3->id, $expandedIds3);
    }
}
