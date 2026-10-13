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
}
