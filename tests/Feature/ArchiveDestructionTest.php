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
}
