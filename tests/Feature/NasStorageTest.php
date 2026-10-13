<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NasStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_nas_disk_is_registered_and_configured_with_fallback()
    {
        $disks = config('filesystems.disks');
        $this->assertArrayHasKey('nas', $disks);
        $this->assertEquals('local', $disks['nas']['driver']);
        $this->assertNotEmpty($disks['nas']['root']);
    }

    public function test_document_preview_stream_reads_from_nas_with_proper_headers()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '001/TEST/NAS/2026',
            'subject' => 'Surat Pengujian Storage NAS',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now(),
            'created_by' => $superadmin->id,
        ]);

        $fileName = '001 TEST NAS 2026_2026-10-12.pdf';
        $file = UploadedFile::fake()->create($fileName, 100, 'application/pdf');
        $path = Storage::disk('nas')->putFileAs('documents', $file, $fileName);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => '1.0',
            'file_path' => $path,
            'created_by' => $superadmin->id,
            'status' => 'active',
        ]);

        $document->update(['current_version_id' => $version->id]);

        $response = $this->actingAs($superadmin)->get(route('document.preview', $version->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="' . $document->generateArchiveFileName('pdf', '1.0') . '"');
    }

    public function test_hierarchical_folder_path_generation_and_storage_structure()
    {
        Storage::fake('nas');

        // 1. Buat hierarki loker: Induk (Tahun 2026) -> Anak (Divisi Keuangan)
        $parentFolder = \App\Models\Folder::create([
            'name' => 'Tahun 2026',
            'type' => 'outgoing',
        ]);

        $childFolder = \App\Models\Folder::create([
            'name' => 'Divisi Keuangan/Pajak',
            'type' => 'outgoing',
            'parent_id' => $parentFolder->id,
        ]);

        // Verifikasi sanitasi nama loker dan hierarki
        $this->assertEquals('Tahun 2026/Divisi Keuangan-Pajak', $childFolder->getDirectoryPath());

        // 2. Buat dokumen dengan loker anak
        $superadmin = User::where('username', 'superadmin')->first();
        $docWithFolder = Document::create([
            'number' => '001/M-IT/10/2026',
            'subject' => 'Surat Pengujian Struktur Loker NAS',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'folder_id' => $childFolder->id,
            'document_date' => '2026-10-12',
            'created_by' => $superadmin->id,
        ]);

        $docWithFolder->load('folder.parent');
        $this->assertEquals(
            'documents/Surat Keluar/Tahun 2026/Divisi Keuangan-Pajak',
            $docWithFolder->getStorageDirectoryPath()
        );

        // 3. Simpan berkas fisik ke jalur bertingkat di NAS
        $fileName = $docWithFolder->generateArchiveFileName('pdf', '1.0');
        $file = UploadedFile::fake()->create($fileName, 100, 'application/pdf');
        $path = Storage::disk('nas')->putFileAs($docWithFolder->getStorageDirectoryPath(), $file, $fileName);

        $this->assertEquals('documents/Surat Keluar/Tahun 2026/Divisi Keuangan-Pajak/' . $fileName, $path);
        Storage::disk('nas')->assertExists($path);

        // 4. Verifikasi dokumen tanpa loker (Tanpa Loker)
        $docWithoutFolder = Document::create([
            'number' => '002/SM/X/2026',
            'subject' => 'Surat Tanpa Loker',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'folder_id' => null,
            'document_date' => '2026-10-12',
            'created_by' => $superadmin->id,
        ]);

        $this->assertEquals('documents/Surat Masuk/Tanpa Loker', $docWithoutFolder->getStorageDirectoryPath());
    }
}
