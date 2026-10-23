<?php

namespace Tests\Feature;

use App\Livewire\Registry\ScanQueue;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\DocumentArchivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentArchivedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_document_creator_receives_email_notification_when_tapersip_uploads_file()
    {
        Notification::fake();
        Storage::fake('nas');

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staffCreator = User::factory()->create([
            'name' => 'Budi Staf Pengaju',
            'email' => 'budi.pengaju@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $docType = DocumentType::where('name', 'like', '%Surat Perintah%')->first()
            ?? DocumentType::firstOrCreate(['name' => 'Surat Perintah', 'code' => 'SPRIN', 'numbering_format' => '{NO}/SPRIN/{YYYY}']);

        $folder = Folder::create([
            'name' => 'Loker 2026',
            'type' => 'internal',
        ]);

        $document = Document::create([
            'number' => '045/SPRIN/X/2026',
            'subject' => 'Surat Perintah Pengawasan Proyek',
            'type' => 'internal',
            'document_type_id' => $docType->id,
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staffCreator->id,
        ]);

        $file = UploadedFile::fake()->create('berkas_scan_sprin.pdf', 250, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openUploadModal', $document->id)
            ->set('uploadFolderId', $folder->id)
            ->set('finalFile', $file)
            ->call('saveFinalFile')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        // Verifikasi dokumen berstatus archived
        $document->refresh();
        $this->assertEquals('archived', $document->status);

        // Verifikasi notifikasi terkirim tepat ke staf pembuat dokumen
        Notification::assertSentTo(
            $staffCreator,
            DocumentArchivedNotification::class,
            function ($notification) use ($document, $superadmin) {
                return $notification->document->id === $document->id
                    && $notification->officer->id === $superadmin->id;
            }
        );
    }

    public function test_other_users_do_not_receive_email_notification()
    {
        Notification::fake();
        Storage::fake('nas');

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staffCreator = User::factory()->create([
            'name' => 'Siti Pembuat',
            'email' => 'siti.pembuat@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $otherStaff = User::factory()->create([
            'name' => 'Ahmad Staf Lain',
            'email' => 'ahmad.lain@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '088/SK/X/2026',
            'subject' => 'Surat Keluar Kerjasama',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staffCreator->id,
        ]);

        $file = UploadedFile::fake()->create('scan_sk.pdf', 100, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openUploadModal', $document->id)
            ->set('finalFile', $file)
            ->call('saveFinalFile')
            ->assertHasNoErrors();

        // Staf lain sama sekali tidak menerima email
        Notification::assertNotSentTo($otherStaff, DocumentArchivedNotification::class);

        // Hanya staf pembuat yang menerima
        Notification::assertSentTo($staffCreator, DocumentArchivedNotification::class);
    }

    public function test_email_view_renders_correct_document_details()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $staff = User::factory()->create([
            'name' => 'Dewi Safitri',
            'email' => 'dewi@perusahaan.co.id',
            'unit_id' => $unit->id,
        ]);

        $docType = DocumentType::where('name', 'like', '%Memo%')->first()
            ?? DocumentType::firstOrCreate(['name' => 'Memo Internal', 'code' => 'MI', 'numbering_format' => '{NO}/MI/{YYYY}']);

        $folder = Folder::create([
            'name' => 'Arsip Khusus 2026',
            'type' => 'internal',
        ]);

        $document = Document::create([
            'number' => '012/MI/X/2026',
            'subject' => 'Pemberitahuan Rapat Koordinasi Anggaran',
            'type' => 'internal',
            'document_type_id' => $docType->id,
            'folder_id' => $folder->id,
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-19',
            'created_by' => $staff->id,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => '1.0',
            'file_path' => 'documents/Surat Khusus/sample.pdf',
            'created_by' => $superadmin->id,
            'status' => 'active',
        ]);

        $notification = new DocumentArchivedNotification($document, $superadmin, $version);
        $mail = $notification->toMail($staff);

        // 1. Verifikasi subjek email memuat nomor surat tanpa prefix [E-Arsip]
        $this->assertEquals('Dokumen 012/MI/X/2026 Telah Selesai Diarsipkan', $mail->subject);
        $this->assertStringNotContainsString('[E-Arsip]', $mail->subject);

        // 2. Render HTML email view dan verifikasi kontennya
        $renderedHtml = view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('Dewi Safitri', $renderedHtml);
        $this->assertStringContainsString('012/MI/X/2026', $renderedHtml);
        $this->assertStringContainsString($docType->name, $renderedHtml);
        $this->assertStringContainsString('Pemberitahuan Rapat Koordinasi Anggaran', $renderedHtml);
        $this->assertStringContainsString($superadmin->name, $renderedHtml);

        // 3. Pastikan Loker Penyimpanan dan CTA Button tidak ditampilkan sesuai desain yang diinginkan
        $this->assertStringNotContainsString('Loker Penyimpanan', $renderedHtml);
        $this->assertStringNotContainsString('Buka &amp; Pratinjau Dokumen PDF', $renderedHtml);
    }

    public function test_tapersip_upload_dispatches_archive_toast_and_loan_status_changed_event()
    {
        Notification::fake();
        Storage::fake('nas');

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staff = User::factory()->create([
            'name' => 'Ferry Staf Operasional',
            'email' => 'ferry.ops@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $folder = Folder::create([
            'name' => 'Arsip Scan 2026',
            'type' => 'outgoing',
        ]);

        $document = Document::create([
            'number' => '089/SK/X/2026',
            'subject' => 'Surat Keputusan Pengadaan Armada',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        $file = UploadedFile::fake()->create('berkas_armada.pdf', 300, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openUploadModal', $document->id)
            ->set('uploadFolderId', $folder->id)
            ->set('finalFile', $file)
            ->call('saveFinalFile')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'modal-upload-queue')
            ->assertDispatched('loan-status-changed')
            ->assertDispatched('desktop-notify', function ($name, $params) use ($document, $staff) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'scan_archived_success'
                    && str_contains($payload['title'] ?? '', 'Dokumen Berhasil Diarsipkan')
                    && str_contains($payload['body'] ?? '', $document->number)
                    && str_contains($payload['body'] ?? '', $staff->name)
                    && str_contains($payload['body'] ?? '', $staff->email)
                    && ($payload['loan_code'] ?? '') === $document->number;
            });

        $document->refresh();
        $this->assertEquals('archived', $document->status);
    }
}
