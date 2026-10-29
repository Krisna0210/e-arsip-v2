<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Registry\Incoming\Index as IncomingIndex;
use App\Livewire\Registry\Internal\Index as InternalIndex;
use App\Livewire\Registry\Outgoing\Index as OutgoingIndex;
use App\Livewire\Registry\ScanQueue;
use App\Models\AuditLog;
use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentCorrectionAndRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_tapersip_can_correct_metadata_in_scan_queue_with_audit_log_and_notify()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $classification = Classification::whereNotNull('parent_id')->first();
        $docType = DocumentType::first();

        $document = Document::create([
            'number' => '010/CORR/X/2026',
            'subject' => 'Perihal Awal Yang Typo',
            'type' => 'outgoing',
            'recipient_name' => 'PT Salah Ketik',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'classification_id' => $classification->id,
            'document_type_id' => $docType->id,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openEditMetadataModal', $document->id)
            ->assertSet('editDocId', $document->id)
            ->assertSet('editSubject', 'Perihal Awal Yang Typo')
            ->set('editSubject', 'Perihal Resmi yang Sudah Diperbaiki')
            ->set('editRecipientName', 'PT Mitra Sinergi Persada')
            ->set('editReason', 'Ralat pengetikan nama mitra dan perihal')
            ->call('saveEditedMetadata')
            ->assertDispatched('close-modal', 'modal-edit-queue-metadata')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('Perihal Resmi yang Sudah Diperbaiki', $document->subject);
        $this->assertEquals('PT Mitra Sinergi Persada', $document->recipient_name);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'Koreksi Metadata Draft Naskah: 010/CORR/X/2026',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_tapersip_or_creator_can_void_document_number_in_scan_queue()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '011/VOID/X/2026',
            'subject' => 'Surat Tugas Yang Batal Terbit',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openVoidModal', $document->id)
            ->assertSet('voidDocId', $document->id)
            ->set('voidReason', 'Penerbitan surat dibatalkan atas arahan Direksi')
            ->call('confirmVoidDocument')
            ->assertDispatched('close-modal', 'modal-void-queue-document')
            ->assertDispatched('loan-status-changed')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('cancelled', $document->status);
        $this->assertEquals('Penerbitan surat dibatalkan atas arahan Direksi', $document->notes);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'Pembatalan Nomor Naskah Resmi (Void): 011/VOID/X/2026',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_staff_can_correct_own_draft_metadata_in_dashboard()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $classification = Classification::whereNotNull('parent_id')->first();

        $document = Document::create([
            'number' => '012/DASH/X/2026',
            'subject' => 'Draft Surat Staf Salah Tulis',
            'type' => 'outgoing',
            'recipient_name' => 'Instansi Asal',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'classification_id' => $classification->id,
            'created_by' => $staff->id,
        ]);

        Livewire::actingAs($staff)
            ->test(Dashboard::class)
            ->call('openEditDraftModal', $document->id)
            ->set('editSubject', 'Draft Surat Staf Diperbaiki')
            ->set('editRecipientName', 'Kementerian Perhubungan RI')
            ->set('editReason', 'Koreksi instansi tujuan surat')
            ->call('saveEditedDraft')
            ->assertDispatched('close-modal', 'modal-dashboard-edit-draft')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('Draft Surat Staf Diperbaiki', $document->subject);
        $this->assertEquals('Kementerian Perhubungan RI', $document->recipient_name);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'Koreksi Metadata Draft Naskah (Dashboard): 012/DASH/X/2026',
            'user_id' => $staff->id,
        ]);
    }

    public function test_staff_can_void_own_draft_number_in_dashboard()
    {
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '013/DASH-VOID/2026',
            'subject' => 'Surat Yang Batal Digunakan',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        Livewire::actingAs($staff)
            ->test(Dashboard::class)
            ->call('openVoidDraftModal', $document->id)
            ->set('voidReason', 'Salah ambil jenis penomoran surat')
            ->call('confirmVoidDraft')
            ->assertDispatched('close-modal', 'modal-dashboard-void-draft')
            ->assertDispatched('loan-status-changed')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('cancellation_requested', $document->status);
        $this->assertEquals('Salah ambil jenis penomoran surat', $document->notes);
    }

    public function test_superadmin_can_directly_void_draft_number_in_dashboard()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '013-ADMIN/DASH-VOID/2026',
            'subject' => 'Surat Yang Batal Digunakan Admin',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(Dashboard::class)
            ->call('openVoidDraftModal', $document->id)
            ->set('voidReason', 'Pembatalan langsung oleh superadmin')
            ->call('confirmVoidDraft')
            ->assertDispatched('close-modal', 'modal-dashboard-void-draft')
            ->assertDispatched('loan-status-changed')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('cancelled', $document->status);
        $this->assertEquals($superadmin->id, $document->cancelled_by);
        $this->assertEquals('Pembatalan langsung oleh superadmin', $document->notes);
    }

    public function test_tapersip_can_correct_metadata_on_archived_outgoing_document()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $classification = Classification::whereNotNull('parent_id')->first();

        $document = Document::create([
            'number' => '014/ARCH-CORR/2026',
            'subject' => 'Surat Arsip Keluar Typo',
            'type' => 'outgoing',
            'recipient_name' => 'Tujuan Typo',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-10',
            'classification_id' => $classification->id,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(OutgoingIndex::class)
            ->call('openEditMetadataModal', $document->id)
            ->set('editSubject', 'Surat Arsip Keluar Resmi Terkoreksi')
            ->set('editRecipientName', 'Kementerian Komunikasi dan Digital')
            ->set('editReason', 'Menyesuaikan dengan fisik surat yang ditandatangani')
            ->call('saveEditedMetadata')
            ->assertDispatched('close-modal', 'modal-edit-archived-metadata')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('Surat Arsip Keluar Resmi Terkoreksi', $document->subject);
        $this->assertEquals('Kementerian Komunikasi dan Digital', $document->recipient_name);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'Koreksi Metadata Dokumen Arsip: 014/ARCH-CORR/2026',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_tapersip_can_upload_revision_pdf_scan_v2_on_archived_outgoing_document()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();
        $classification = Classification::whereNotNull('parent_id')->first();

        $document = Document::create([
            'number' => '015/REV/2026',
            'subject' => 'Surat Keluar Butuh Revisi Scan',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-15',
            'classification_id' => $classification->id,
            'created_by' => $superadmin->id,
        ]);

        $v1 = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => '1.0',
            'file_path' => 'documents/outgoing/sample_v1.pdf',
            'created_by' => $superadmin->id,
            'status' => 'active',
        ]);
        $document->update(['current_version_id' => $v1->id]);

        $newScanFile = UploadedFile::fake()->create('revisi_scan_lengkap.pdf', 500, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(OutgoingIndex::class)
            ->call('openRevisionModal', $document->id)
            ->assertSet('revisionCurrentVersion', '1.0')
            ->assertSet('revisionNextVersion', '2.0')
            ->set('revisionFile', $newScanFile)
            ->set('revisionReason', 'Upload ulang berkas scan lengkap dengan stempel basah asli')
            ->call('saveRevisionFile')
            ->assertDispatched('close-modal', 'modal-revision-upload')
            ->assertDispatched('notify');

        $document->refresh();
        $v1->refresh();

        $this->assertEquals('obsolete', $v1->status);

        $v2 = DocumentVersion::where('document_id', $document->id)->where('version_number', '2.0')->first();
        $this->assertNotNull($v2);
        $this->assertEquals('active', $v2->status);
        $this->assertEquals($v2->id, $document->current_version_id);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'Unggah Berkas Revisi Arsip (v2.0): 015/REV/2026',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_tapersip_can_correct_metadata_and_upload_revision_on_incoming_document()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $incoming = Document::create([
            'number' => '088/EXT-IN/X/2026',
            'sender' => 'Pengirim Lama Salah',
            'subject' => 'Surat Masuk Permohonan Data',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-10',
            'received_date' => '2026-10-12',
            'unit_id' => $unit->id,
            'created_by' => $superadmin->id,
        ]);

        $v1 = DocumentVersion::create([
            'document_id' => $incoming->id,
            'version_number' => '1.0',
            'file_path' => 'documents/incoming/in_v1.pdf',
            'created_by' => $superadmin->id,
            'status' => 'active',
        ]);
        $incoming->update(['current_version_id' => $v1->id]);

        // 1. Koreksi Metadata
        Livewire::actingAs($superadmin)
            ->test(IncomingIndex::class)
            ->call('openEditMetadataModal', $incoming->id)
            ->set('editSender', 'Kementerian Keuangan RI')
            ->set('editReason', 'Ralat nama instansi pengirim surat')
            ->call('saveEditedMetadata')
            ->assertDispatched('close-modal', 'modal-edit-archived-metadata')
            ->assertDispatched('notify');

        $incoming->refresh();
        $this->assertEquals('Kementerian Keuangan RI', $incoming->sender);

        // 2. Upload Revisi File
        $revFile = UploadedFile::fake()->create('incoming_scan_rev.pdf', 300, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(IncomingIndex::class)
            ->call('openRevisionModal', $incoming->id)
            ->set('revisionFile', $revFile)
            ->set('revisionReason', 'Scan ulang lembar disposisi pengirim yang tertinggal')
            ->call('saveRevisionFile')
            ->assertDispatched('close-modal', 'modal-revision-upload')
            ->assertDispatched('notify');

        $incoming->refresh();
        $v2 = DocumentVersion::where('document_id', $incoming->id)->where('version_number', '2.0')->first();
        $this->assertNotNull($v2);
        $this->assertEquals($v2->id, $incoming->current_version_id);
    }

    public function test_correct_internal_document_recipient_sets_valid_enum_receipt_status()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $classification = Classification::whereNotNull('parent_id')->first();
        $units = Unit::take(2)->get();
        $unitA = $units[0];
        $unitB = $units[1];

        // 1. Uji di Antrean Scan (ScanQueue)
        $internalDraft = Document::create([
            'number' => '020/MEMO/X/2026',
            'subject' => 'Memo Internal Draft',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'classification_id' => $classification->id,
            'created_by' => $superadmin->id,
        ]);
        $internalDraft->recipients()->create([
            'recipient_id' => $unitA->id,
            'recipient_type' => Unit::class,
            'receipt_status' => 'unread',
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openEditMetadataModal', $internalDraft->id)
            ->set('editRecipientId', $unitB->id)
            ->set('editReason', 'Ralat departemen penerima memo internal')
            ->call('saveEditedMetadata')
            ->assertDispatched('close-modal', 'modal-edit-queue-metadata')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('document_recipients', [
            'document_id' => $internalDraft->id,
            'recipient_id' => $unitB->id,
            'recipient_type' => Unit::class,
            'receipt_status' => 'unread',
        ]);

        // 2. Uji di Arsip Internal (InternalIndex)
        $internalArchived = Document::create([
            'number' => '021/MEMO-ARCH/X/2026',
            'subject' => 'Memo Internal Arsip',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'classification_id' => $classification->id,
            'created_by' => $superadmin->id,
        ]);
        $internalArchived->recipients()->create([
            'recipient_id' => $unitA->id,
            'recipient_type' => Unit::class,
            'receipt_status' => 'unread',
        ]);

        Livewire::actingAs($superadmin)
            ->test(InternalIndex::class)
            ->call('openEditMetadataModal', $internalArchived->id)
            ->set('editRecipientId', $unitB->id)
            ->set('editReason', 'Koreksi unit penerima arsip memo')
            ->call('saveEditedMetadata')
            ->assertDispatched('close-modal', 'modal-edit-archived-metadata')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('document_recipients', [
            'document_id' => $internalArchived->id,
            'recipient_id' => $unitB->id,
            'recipient_type' => Unit::class,
            'receipt_status' => 'unread',
        ]);

        // 3. Uji di Dashboard (Dashboard)
        Livewire::actingAs($superadmin)
            ->test(Dashboard::class)
            ->call('openEditDraftModal', $internalDraft->id)
            ->set('editRecipientId', $unitA->id)
            ->set('editReason', 'Pengembalian unit penerima dari dashboard')
            ->call('saveEditedDraft')
            ->assertDispatched('close-modal', 'modal-dashboard-edit-draft')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('document_recipients', [
            'document_id' => $internalDraft->id,
            'recipient_id' => $unitA->id,
            'recipient_type' => Unit::class,
            'receipt_status' => 'unread',
        ]);
    }

    public function test_changing_classification_shows_preview_and_updates_number_when_checkbox_checked()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $classifications = Classification::whereNotNull('parent_id')->take(2)->get();
        $classA = $classifications[0];
        $classB = $classifications[1];

        $oldNumber = "01/DIR-IT-{$classA->code}/10/2026";
        $expectedNewNumber = "01/DIR-IT-{$classB->code}/10/2026";

        $document = Document::create([
            'number' => $oldNumber,
            'subject' => 'Surat Keluar Salah Klasifikasi',
            'type' => 'outgoing',
            'recipient_name' => 'Kementerian BUMN',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => '2026-10-20',
            'classification_id' => $classA->id,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openEditMetadataModal', $document->id)
            ->assertSet('editClassificationId', $classA->id)
            ->assertSet('newDocNumberPreview', '')
            ->set('editClassificationId', $classB->id)
            ->assertSet('newDocNumberPreview', $expectedNewNumber)
            ->assertSet('syncDocNumberWithClassification', true)
            ->set('editReason', 'Koreksi kode klasifikasi ke Keuangan')
            ->call('saveEditedMetadata')
            ->assertDispatched('close-modal', 'modal-edit-queue-metadata')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals($classB->id, $document->classification_id);
        $this->assertEquals($expectedNewNumber, $document->number);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_changing_classification_preserves_number_when_checkbox_unchecked()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $classifications = Classification::whereNotNull('parent_id')->take(2)->get();
        $classA = $classifications[0];
        $classB = $classifications[1];

        $oldNumber = "02/DIR-IT-{$classA->code}/10/2026";

        $document = Document::create([
            'number' => $oldNumber,
            'subject' => 'Surat Keluar Sudah Dicetak Fisik',
            'type' => 'outgoing',
            'recipient_name' => 'PT PLN Persero',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => '2026-10-20',
            'classification_id' => $classA->id,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openEditMetadataModal', $document->id)
            ->set('editClassificationId', $classB->id)
            ->assertSet('syncDocNumberWithClassification', true)
            // Staf tidak ingin nomor diubah karena fisik sudah dicetak
            ->set('syncDocNumberWithClassification', false)
            ->set('editReason', 'Hanya ralat metadata sistem, fisik sudah ttd')
            ->call('saveEditedMetadata')
            ->assertDispatched('close-modal', 'modal-edit-queue-metadata');

        $document->refresh();
        $this->assertEquals($classB->id, $document->classification_id);
        // Nomor surat tetap nomor lama!
        $this->assertEquals($oldNumber, $document->number);
    }

    public function test_changing_document_month_updates_number_when_checkbox_checked()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $class = Classification::whereNotNull('parent_id')->first();

        $oldNumber = "03/DIR-IT-{$class->code}/09/2026";
        $expectedNewNumber = "03/DIR-IT-{$class->code}/10/2026";

        $document = Document::create([
            'number' => $oldNumber,
            'subject' => 'Surat Keluar Geser Bulan',
            'type' => 'outgoing',
            'recipient_name' => 'PT Pertamina',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => '2026-09-15',
            'classification_id' => $class->id,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openEditMetadataModal', $document->id)
            ->set('editDocumentDate', '2026-10-15')
            ->assertSet('newDocNumberPreview', $expectedNewNumber)
            ->assertSet('syncDocNumberWithClassification', true)
            ->set('editReason', 'Ralat tanggal surat karena mundurnya jadwal ttd')
            ->call('saveEditedMetadata')
            ->assertDispatched('close-modal', 'modal-edit-queue-metadata')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('2026-10-15', $document->document_date->format('Y-m-d'));
        $this->assertEquals($expectedNewNumber, $document->number);
    }

    public function test_changing_both_classification_and_month_updates_both_in_number()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $classifications = Classification::whereNotNull('parent_id')->take(2)->get();
        $classA = $classifications[0];
        $classB = $classifications[1];

        $oldNumber = "04/DIR-IT-{$classA->code}/09/2026";
        $expectedNewNumber = "04/DIR-IT-{$classB->code}/10/2026";

        $document = Document::create([
            'number' => $oldNumber,
            'subject' => 'Surat Keluar Geser Bulan dan Ganti Klasifikasi',
            'type' => 'outgoing',
            'recipient_name' => 'Kementerian Keuangan RI',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => '2026-09-15',
            'classification_id' => $classA->id,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openEditMetadataModal', $document->id)
            ->set('editClassificationId', $classB->id)
            ->set('editDocumentDate', '2026-10-15')
            ->assertSet('newDocNumberPreview', $expectedNewNumber)
            ->assertSet('syncDocNumberWithClassification', true)
            ->set('editReason', 'Koreksi ganda: klasifikasi dan bulan terbit')
            ->call('saveEditedMetadata')
            ->assertDispatched('close-modal', 'modal-edit-queue-metadata')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals($classB->id, $document->classification_id);
        $this->assertEquals('2026-10-15', $document->document_date->format('Y-m-d'));
        $this->assertEquals($expectedNewNumber, $document->number);
    }

    public function test_changing_same_month_day_only_does_not_trigger_number_sync()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $class = Classification::whereNotNull('parent_id')->first();

        $oldNumber = "05/DIR-IT-{$class->code}/10/2026";

        $document = Document::create([
            'number' => $oldNumber,
            'subject' => 'Surat Keluar Geser Hari Saja',
            'type' => 'outgoing',
            'recipient_name' => 'PT Telkom',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => '2026-10-10',
            'classification_id' => $class->id,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openEditMetadataModal', $document->id)
            ->set('editDocumentDate', '2026-10-25')
            ->assertSet('newDocNumberPreview', '')
            ->assertSet('syncDocNumberWithClassification', false);
    }
}
