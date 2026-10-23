<?php

namespace Tests\Feature;

use App\Livewire\Registry\ScanQueue;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\NasHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

    public function test_nas_health_service_detects_online_status_and_metrics()
    {
        Storage::fake('nas');
        $status = NasHealthService::check(true);

        $this->assertTrue($status['online']);
        $this->assertEquals('connected', $status['status']);
        $this->assertNotEmpty($status['root_path']);
    }

    public function test_scan_queue_renders_nas_online_badge_and_allows_manual_refresh()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->assertSee('NAS Online')
            ->call('refreshNasConnection')
            ->assertDispatched('notify');
    }

    public function test_scan_queue_prevents_upload_and_shows_error_when_nas_is_offline()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $doc = Document::create([
            'number' => '099/TEST/OFFLINE/2026',
            'subject' => 'Surat Pengujian Offline NAS',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        config(['filesystems.disks.nas.root' => 'Z:\\non_existent_nas_path_12345']);

        $file = UploadedFile::fake()->create('test.pdf', 200, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openUploadModal', $doc->id)
            ->set('finalFile', $file)
            ->call('saveFinalFile')
            ->assertHasErrors(['finalFile'])
            ->assertSee('NAS Terputus');

        $this->assertDatabaseMissing('document_versions', [
            'document_id' => $doc->id,
        ]);
    }

    public function test_scan_queue_table_and_modal_display_specific_document_type_name_for_internal_documents()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = \App\Models\DocumentType::where('name', 'like', '%Surat Tugas%')->first()
            ?? \App\Models\DocumentType::firstOrCreate(['name' => 'Surat Tugas', 'code' => 'STG', 'numbering_format' => '{NO}/STG/{YYYY}']);

        $doc = Document::create([
            'number' => '999/STG/QUEUE/2026',
            'subject' => 'Naskah Antrean Scan Khusus',
            'type' => 'internal',
            'document_type_id' => $docType->id,
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->assertSee('999/STG/QUEUE/2026')
            ->assertSee($docType->name)
            ->call('openUploadModal', $doc->id)
            ->assertSet('uploadDocType', $docType->name);
    }

    public function test_scan_queue_smart_page_resolver_navigates_to_correct_page_for_highlight_parameter()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Buat 15 dokumen waiting_for_file (sehingga halaman terbagi menjadi 2 halaman, @10 data/halaman)
        $targetDoc = null;
        for ($i = 1; $i <= 15; $i++) {
            $d = Document::create([
                'number' => "DOC-QUEUE-{$i}/2026",
                'subject' => "Dokumen Antrean Ke-{$i}",
                'type' => 'outgoing',
                'sifat' => 'biasa',
                'status' => 'waiting_for_file',
                'document_date' => now()->format('Y-m-d'),
                'created_by' => $superadmin->id,
            ]);

            // Dokumen ke-13 berada di halaman ke-2 (karena urutan created_at asc)
            if ($i === 13) {
                $targetDoc = $d;
            }
        }

        $this->assertNotNull($targetDoc);

        // 2. Simulasi klik notifikasi dengan query param highlight=doc-{id}
        // Smart Page Resolver harus otomatis menghitung targetPage = 2 dan menyajikan data halaman 2
        Livewire::withQueryParams(['highlight' => "doc-{$targetDoc->id}"])
            ->actingAs($superadmin)
            ->test(ScanQueue::class)
            ->assertSee($targetDoc->number)
            ->assertSee($targetDoc->subject)
            ->assertSee('id="doc-row-' . $targetDoc->id . '"', false);
    }
}
