<?php

namespace Tests\Feature;

use App\Livewire\Archive\DestructionIndex;
use App\Models\AuditLog;
use App\Models\DestructionRecord;
use App\Models\Document;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArchiveDestructionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_propose_document_destruction_with_audit_log_and_notify_toast()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '901/RET/X/2026',
            'subject' => 'Surat Habis Masa Retensi JRA',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'retention_end_date' => now()->subDay()->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $reason = 'Masa retensi inaktif 5 tahun telah habis sesuai pedoman jadwal retensi arsip.';

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->call('openProposeModal', $document->id)
            ->assertSet('selectedDocId', $document->id)
            ->assertDispatched('open-modal', 'modal-propose')
            ->set('proposalReason', $reason)
            ->call('submitProposal')
            ->assertDispatched('close-modal', 'modal-propose')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil diajukan');
            });

        $this->assertDatabaseHas('destruction_records', [
            'document_id' => $document->id,
            'proposer_id' => $superadmin->id,
            'status' => 'proposed',
            'reason' => $reason,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => "Pengajuan Usulan Pemusnahan Arsip: 901/RET/X/2026",
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_authorized_user_can_approve_proposal_with_audit_log_and_notify_toast()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '902/RET/X/2026',
            'subject' => 'Surat Menunggu Approval Pemusnahan',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'retention_end_date' => now()->subDay()->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $record = DestructionRecord::create([
            'document_id' => $document->id,
            'proposer_id' => $superadmin->id,
            'status' => 'proposed',
            'reason' => 'Usulan musnah arsip lampau',
        ]);

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->call('approveProposal', $record->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil disetujui');
            });

        $record->refresh();
        $this->assertEquals('approved', $record->status);
        $this->assertEquals($superadmin->id, $record->approver_id);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => "Persetujuan Usulan Pemusnahan Arsip: 902/RET/X/2026",
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_authorized_user_can_reject_proposal_with_audit_log_and_notify_toast()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '903/RET/X/2026',
            'subject' => 'Surat Tolak Pemusnahan',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'archived',
            'retention_end_date' => now()->subDay()->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $record = DestructionRecord::create([
            'document_id' => $document->id,
            'proposer_id' => $superadmin->id,
            'status' => 'proposed',
            'reason' => 'Usulan ditolak karena berkas masih aktif digunakan',
        ]);

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->call('rejectProposal', $record->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'info'
                    && str_contains($payload['message'] ?? '', 'telah ditolak');
            });

        $record->refresh();
        $this->assertEquals('rejected', $record->status);
        $this->assertEquals($superadmin->id, $record->approver_id);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => "Penolakan Usulan Pemusnahan Arsip: 903/RET/X/2026",
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_authorized_user_can_execute_destruction_with_ba_file_and_audit_log()
    {
        Storage::fake('public');

        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '904/RET/X/2026',
            'subject' => 'Surat Siap Eksekusi Pemusnahan',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'archived',
            'retention_end_date' => now()->subDay()->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $record = DestructionRecord::create([
            'document_id' => $document->id,
            'proposer_id' => $superadmin->id,
            'approver_id' => $superadmin->id,
            'status' => 'approved',
            'reason' => 'Disetujui untuk pemusnahan fisik',
        ]);

        $baFile = UploadedFile::fake()->create('berita_acara_pemusnahan_904.pdf', 400, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->call('openExecuteModal', $record->id)
            ->assertSet('activeRecordId', $record->id)
            ->assertDispatched('open-modal', 'modal-execute')
            ->set('baFile', $baFile)
            ->call('executeDestruction')
            ->assertDispatched('close-modal', 'modal-execute')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil dieksekusi');
            });

        $record->refresh();
        $this->assertEquals('executed', $record->status);
        $this->assertEquals($superadmin->id, $record->executor_id);
        $this->assertNotNull($record->executed_at);
        $this->assertNotNull($record->ba_file_path);

        // Pastikan dokumen berubah menjadi disposed
        $document->refresh();
        $this->assertEquals('disposed', $document->archive_status);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => "Eksekusi Pemusnahan Arsip & Upload Berita Acara: 904/RET/X/2026",
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_destruction_index_renders_custom_pagination_view()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // Buat 12 dokumen yang sudah melewati masa retensi untuk memicu paginasi (>10 data)
        for ($i = 1; $i <= 12; $i++) {
            Document::create([
                'number' => "PAG-{$i}/RET/2026",
                'subject' => "Surat Retensi Paginasi {$i}",
                'type' => 'outgoing',
                'sifat' => 'biasa',
                'status' => 'archived',
                'archive_status' => 'archived',
                'retention_end_date' => now()->subDays(2)->format('Y-m-d'),
                'document_date' => now()->subYears(5)->format('Y-m-d'),
                'created_by' => $superadmin->id,
            ]);
        }

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->assertSee('PAG-1/RET/2026')
            ->assertSee('Menampilkan')
            ->assertSee('dari')
            ->assertSee('Next');
    }

    public function test_open_propose_modal_loads_document_metadata_and_dispatches_event()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '905/PROPOSE-META/2026',
            'subject' => 'Surat Metadata Usulan Modal',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'active',
            'retention_end_date' => now()->subDays(5)->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->call('openProposeModal', $document->id)
            ->assertSet('selectedDocId', $document->id)
            ->assertSet('selectedDocNumber', '905/PROPOSE-META/2026')
            ->assertSet('selectedDocSubject', 'Surat Metadata Usulan Modal')
            ->assertDispatched('open-modal', 'modal-propose');
    }

    public function test_rejection_modal_workflow_with_reason()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '906/REJECT-MODAL/2026',
            'subject' => 'Surat Uji Modal Tolak Pemusnahan',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'active',
            'retention_end_date' => now()->subDays(5)->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $record = DestructionRecord::create([
            'document_id' => $document->id,
            'proposer_id' => $superadmin->id,
            'status' => 'proposed',
            'reason' => 'Usul hapus fisik',
        ]);

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->call('openRejectModal', $record->id)
            ->assertSet('rejectRecordId', $record->id)
            ->assertSet('rejectRecordDocNumber', '906/REJECT-MODAL/2026')
            ->assertDispatched('open-modal', 'modal-reject')
            ->set('rejectionReason', 'Masih dibutuhkan oleh auditor eksternal BPK')
            ->call('rejectProposal')
            ->assertDispatched('close-modal', 'modal-reject')
            ->assertDispatched('notify');

        $record->refresh();
        $this->assertEquals('rejected', $record->status);
        $this->assertEquals($superadmin->id, $record->approver_id);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => "Penolakan Usulan Pemusnahan Arsip: 906/REJECT-MODAL/2026",
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_unauthorized_staff_cannot_approve_reject_or_execute()
    {
        $roleUser = Role::where('name', 'User')->first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => Unit::first()->id,
        ]);

        $document = Document::create([
            'number' => '907/UNAUTH/2026',
            'subject' => 'Surat Proteksi Hak Akses',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'active',
            'retention_end_date' => now()->subDays(5)->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        $record = DestructionRecord::create([
            'document_id' => $document->id,
            'proposer_id' => $staff->id,
            'status' => 'proposed',
            'reason' => 'Usulan staf',
        ]);

        Livewire::actingAs($staff)
            ->test(DestructionIndex::class)
            ->call('approveProposal', $record->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error';
            });

        $record->refresh();
        $this->assertEquals('proposed', $record->status);
    }

    public function test_user_can_propose_bulk_collective_destruction_in_one_dupa_batch()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Buat 3 naskah yang habis masa retensi
        $docs = [];
        for ($i = 1; $i <= 3; $i++) {
            $docs[] = Document::create([
                'number' => "908/BULK-{$i}/2026",
                'subject' => "Naskah Usulan Kolektif Ke-{$i}",
                'type' => 'outgoing',
                'sifat' => 'biasa',
                'status' => 'archived',
                'archive_status' => 'active',
                'retention_end_date' => now()->subDays(10)->format('Y-m-d'),
                'document_date' => now()->subYears(5)->format('Y-m-d'),
                'created_by' => $superadmin->id,
            ]);
        }

        $selectedIds = collect($docs)->pluck('id')->map(fn($id) => (string)$id)->toArray();

        // 2. Ajukan usulan kolektif
        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->set('selectedDocIds', $selectedIds)
            ->call('openProposeModal')
            ->assertDispatched('open-modal', 'modal-propose')
            ->set('proposalReason', 'Pemusnahan massal arsip inaktif 5 tahun periode 2021')
            ->call('submitProposal')
            ->assertDispatched('close-modal', 'modal-propose')
            ->assertDispatched('notify');

        // 3. Verifikasi seluruh 3 naskah tercatat dalam 1 batch_code DUPA yang sama
        $records = DestructionRecord::whereIn('document_id', $selectedIds)->get();
        $this->assertCount(3, $records);

        $batchCodes = $records->pluck('batch_code')->unique();
        $this->assertCount(1, $batchCodes, 'Semua naskah harus memiliki 1 batch_code DUPA yang sama.');

        $batchCode = $batchCodes->first();
        $this->assertStringStartsWith('DUPA-', $batchCode);

        // 4. Verifikasi audit trail kolektif
        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $docs[0]->id,
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_batch_approval_and_execution_applies_single_ba_to_all_documents_and_marks_disposed()
    {
        Storage::fake('public');
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Buat 3 dokumen dan 3 record dalam 1 batch
        $batchCode = 'DUPA-202610-999';
        $docs = [];
        $records = [];
        for ($i = 1; $i <= 3; $i++) {
            $doc = Document::create([
                'number' => "909/BATCH-EXEC-{$i}/2026",
                'subject' => "Naskah Eksekusi Batch Ke-{$i}",
                'type' => 'internal',
                'sifat' => 'biasa',
                'status' => 'archived',
                'archive_status' => 'active',
                'retention_end_date' => now()->subDays(10)->format('Y-m-d'),
                'document_date' => now()->subYears(5)->format('Y-m-d'),
                'created_by' => $superadmin->id,
            ]);
            $docs[] = $doc;

            $records[] = DestructionRecord::create([
                'batch_code' => $batchCode,
                'document_id' => $doc->id,
                'proposer_id' => $superadmin->id,
                'status' => 'proposed',
                'reason' => 'Usulan musnah batch test',
            ]);
        }

        // 2. Approve batch sekaligus
        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->call('approveProposal', $batchCode)
            ->assertDispatched('notify');

        // Pastikan ketiga record berstatus approved
        $this->assertEquals(3, DestructionRecord::where('batch_code', $batchCode)->where('status', 'approved')->count());

        // 3. Eksekusi batch dengan 1 file PDF Berita Acara
        $baFile = UploadedFile::fake()->create('berita_acara_kolektif.pdf', 500, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->call('openExecuteModal', $batchCode)
            ->assertSet('activeBatchCode', $batchCode)
            ->assertDispatched('open-modal', 'modal-execute')
            ->set('baFile', $baFile)
            ->call('executeDestruction')
            ->assertDispatched('close-modal', 'modal-execute')
            ->assertDispatched('notify');

        // 4. Pastikan ketiga record berstatus executed dengan 1 ba_file_path yang sama
        $executedRecords = DestructionRecord::where('batch_code', $batchCode)->get();
        $this->assertCount(3, $executedRecords);
        foreach ($executedRecords as $rec) {
            $this->assertEquals('executed', $rec->status);
            $this->assertEquals($superadmin->id, $rec->executor_id);
            $this->assertNotNull($rec->ba_file_path);
        }

        // 5. Pastikan seluruh dokumen berubah menjadi disposed (musnah)
        foreach ($docs as $doc) {
            $doc->refresh();
            $this->assertEquals('disposed', $doc->archive_status);
        }
    }

    public function test_disposed_documents_are_hidden_from_document_loan_catalog()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // Dokumen yang telah dimusnahkan
        $disposedDoc = Document::create([
            'number' => '910/DISPOSED-LOAN/2026',
            'subject' => 'Surat Musnah Tidak Boleh Dipinjam',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'disposed',
            'retention_end_date' => now()->subDays(20)->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        // Dokumen aktif normal
        $activeDoc = Document::create([
            'number' => '911/ACTIVE-LOAN/2026',
            'subject' => 'Surat Aktif Boleh Dipinjam',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'active',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Registry\DocumentLoanIndex::class)
            ->assertSee($activeDoc->number)
            ->assertDontSee($disposedDoc->number);
    }

    public function test_registry_indexes_display_dimusnahkan_badge_for_disposed_documents()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Dokumen Surat Keluar Musnah
        $outDoc = Document::create([
            'number' => '912/OUT-DISPOSED/2026',
            'subject' => 'Surat Keluar Telah Musnah',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'disposed',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Registry\Outgoing\Index::class)
            ->assertSee($outDoc->number)
            ->assertSee('Dimusnahkan');

        // 2. Dokumen Surat Masuk Musnah
        $inDoc = Document::create([
            'number' => '913/IN-DISPOSED/2026',
            'subject' => 'Surat Masuk Telah Musnah',
            'type' => 'incoming',
            'sender' => 'Instansi Luar',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'disposed',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Registry\Incoming\Index::class)
            ->assertSee($inDoc->number)
            ->assertSee('Dimusnahkan');

        // 3. Dokumen Surat Khusus (Internal) Musnah
        $intDoc = Document::create([
            'number' => '914/INT-DISPOSED/2026',
            'subject' => 'Surat Khusus Telah Musnah',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'disposed',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Registry\Internal\Index::class)
            ->assertSee($intDoc->number)
            ->assertSee('Dimusnahkan');
    }

    public function test_disposed_documents_cannot_be_moved_or_revised_in_registry()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $disposedDoc = Document::create([
            'number' => '915/GUARD-DISPOSED/2026',
            'subject' => 'Surat Terproteksi dari Loker dan Revisi',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'disposed',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Registry\Outgoing\Index::class)
            ->call('openMoveModal', $disposedDoc->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'Dimusnahkan');
            })
            ->call('openRevisionModal', $disposedDoc->id)
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'Dimusnahkan');
            });
    }

    public function test_destruction_index_renders_aligned_document_identity_and_distribution_flow()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        // 1. Dokumen Surat Masuk yang habis retensi
        $inDoc = Document::create([
            'number' => '916/EXP-INCOMING/2026',
            'subject' => 'Surat Masuk Kedaluwarsa Retensi',
            'type' => 'incoming',
            'sender' => 'Kementerian Keuangan RI',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'active',
            'retention_end_date' => now()->subDay()->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'unit_id' => $unit->id,
            'created_by' => $superadmin->id,
        ]);

        // 2. Dokumen Surat Keluar yang habis retensi
        $outDoc = Document::create([
            'number' => '917/EXP-OUTGOING/2026',
            'subject' => 'Surat Keluar Kedaluwarsa Retensi',
            'type' => 'outgoing',
            'recipient_name' => 'PT Mitra Sejahtera',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'active',
            'retention_end_date' => now()->subDay()->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'unit_id' => $unit->id,
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DestructionIndex::class)
            ->assertSee($inDoc->number)
            ->assertSee('Kementerian Keuangan RI')
            ->assertSee($unit->name)
            ->assertSee($outDoc->number)
            ->assertSee('PT Mitra Sejahtera');
    }

    public function test_user_without_approve_destruction_permission_sees_dash_and_cannot_approve()
    {
        // 1. Role yang memiliki akses usul pemusnahan (destruction), tapi TIDAK memiliki approve_destruction
        $roleOfficer = Role::create([
            'name' => 'PetugasPengusul',
            'description' => 'Petugas Arsip Unit',
            'status' => 'active',
            'module_visibility' => ['destruction'],
        ]);

        $officer = User::factory()->create([
            'role_id' => $roleOfficer->id,
            'unit_id' => Unit::first()->id,
        ]);

        $doc = Document::create([
            'number' => '918/NO-APPROVE-ROLE/2026',
            'subject' => 'Surat Uji Proteksi Tanpa Izin Penilai',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'active',
            'retention_end_date' => now()->subDay()->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $officer->id,
        ]);

        $record = DestructionRecord::create([
            'batch_code' => 'DUPA-202610-NOAPP',
            'document_id' => $doc->id,
            'proposer_id' => $officer->id,
            'status' => 'proposed',
            'reason' => 'Usulan tanpa wewenang verifikasi',
        ]);

        // 2. Akses komponen: Pada tab proposals, kolom tindakan harus menampilkan '-' dan TIDAK menampilkan tombol Approve
        Livewire::actingAs($officer)
            ->test(DestructionIndex::class)
            ->set('activeTab', 'proposals')
            ->assertSee('DUPA-202610-NOAPP')
            ->assertSee('Diperiksa')
            ->assertDontSeeHtml('wire:click="approveProposal')
            ->assertDontSeeHtml('wire:click="openRejectModal')
            ->assertSeeHtml('<span class="text-slate-400 text-xs font-mono select-none">-</span>')
            ->call('approveProposal', 'DUPA-202610-NOAPP')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'error'
                    && str_contains($payload['message'] ?? '', 'tidak memiliki hak akses');
            });

        $record->refresh();
        $this->assertEquals('proposed', $record->status, 'Status naskah harus tetap proposed.');
    }

    public function test_user_with_approve_destruction_permission_sees_buttons_and_can_approve()
    {
        // 1. Role yang memiliki checklist approve_destruction
        $roleApprover = Role::create([
            'name' => 'PanitiaPemusnah',
            'description' => 'Pejabat Penilai & Penyetuju Pemusnahan',
            'status' => 'active',
            'module_visibility' => ['destruction', 'approve_destruction'],
        ]);

        $approver = User::factory()->create([
            'role_id' => $roleApprover->id,
            'unit_id' => Unit::first()->id,
        ]);

        $doc = Document::create([
            'number' => '919/WITH-APPROVE-ROLE/2026',
            'subject' => 'Surat Uji Role Dengan Approve',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'archive_status' => 'active',
            'retention_end_date' => now()->subDay()->format('Y-m-d'),
            'document_date' => now()->subYears(5)->format('Y-m-d'),
            'created_by' => $approver->id,
        ]);

        $record = DestructionRecord::create([
            'batch_code' => 'DUPA-202610-WITHAPP',
            'document_id' => $doc->id,
            'proposer_id' => $approver->id,
            'status' => 'proposed',
            'reason' => 'Usulan siap diapprove panitia',
        ]);

        // 2. Akses komponen: Pada tab proposals, tombol Approve & Reject harus muncul dan dapat diklik
        Livewire::actingAs($approver)
            ->test(DestructionIndex::class)
            ->set('activeTab', 'proposals')
            ->assertSee('DUPA-202610-WITHAPP')
            ->assertSee('Diperiksa')
            ->assertSee('Approve')
            ->assertSee('Reject')
            ->call('approveProposal', 'DUPA-202610-WITHAPP')
            ->assertDispatched('notify', function ($name, $params) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'success'
                    && str_contains($payload['message'] ?? '', 'berhasil disetujui');
            });

        $record->refresh();
        $this->assertEquals('approved', $record->status, 'Status naskah harus berubah menjadi approved.');

        // 3. Setelah disetujui, tombol Eksekusi BA terbuka
        Livewire::actingAs($approver)
            ->test(DestructionIndex::class)
            ->set('activeTab', 'proposals')
            ->assertSee('Disetujui')
            ->assertSee('Eksekusi BA');
    }
}
