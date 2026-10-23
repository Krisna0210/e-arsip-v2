<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\DocumentType;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Livewire\Dashboard;
use App\Livewire\Registry\Outgoing\Create as OutgoingCreate;
use App\Livewire\Registry\Internal\Create as InternalCreate;
use App\Livewire\Registry\ScanQueue;
use App\Livewire\Registry\DocumentLoanIndex;
use App\Livewire\Settings\AuditTrailIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_activity_is_recorded_when_outgoing_letter_is_booked()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::where('name', 'like', '%Surat Keluar%')->first();
        $unit = Unit::where('type', 'division')->first() ?? Unit::first();
        $classification = Classification::whereNotNull('parent_id')->first() ?? Classification::first();

        Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unit->id)
            ->set('signer_type', 'pimpinan_unit')
            ->set('recipient_name', 'Kementerian BUMN')
            ->set('subject', 'Uji Coba Audit Trail Booking Surat')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber')
            ->assertHasNoErrors();

        $log = AuditLog::where('user_id', $superadmin->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Booking Nomor Surat Keluar', $log->action);
        $this->assertEquals('Uji Coba Audit Trail Booking Surat', $log->metadata['subject']);
    }

    public function test_staff_only_sees_their_own_activities_on_dashboard()
    {
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();

        $userA = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $userB = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        // Activity by User A
        AuditLog::record(
            action: 'Aktivitas User A',
            documentId: null,
            metadata: [],
            userId: $userA->id
        );

        // Activity by User B
        AuditLog::record(
            action: 'Aktivitas User B',
            documentId: null,
            metadata: [],
            userId: $userB->id
        );

        // User A tests Dashboard
        $component = Livewire::actingAs($userA)->test(Dashboard::class);
        $component->assertViewHas('canViewAllActivities', false);
        $component->assertViewHas('recentActivities', function ($activities) use ($userA) {
            return $activities->count() === 1 && $activities->first()->user_id === $userA->id;
        });
        $component->assertSee('Aktivitas User A');
        $component->assertDontSee('Aktivitas User B');
    }

    public function test_superadmin_sees_all_activities_on_dashboard()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();

        $userA = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        AuditLog::record(action: 'Aktivitas Spesifik User A', documentId: null, metadata: [], userId: $userA->id);
        AuditLog::record(action: 'Aktivitas Superadmin', documentId: null, metadata: [], userId: $superadmin->id);

        $component = Livewire::actingAs($superadmin)->test(Dashboard::class);
        $component->assertViewHas('canViewAllActivities', true);
        $component->assertSee('Seluruh Sistem');
        $component->assertSee('Aktivitas Spesifik User A');
        $component->assertSee('Aktivitas Superadmin');
    }

    public function test_tapersip_sees_all_activities_on_dashboard()
    {
        $unit = Unit::first();

        // Create a role with archive_queue access
        $tapersipRole = Role::create([
            'name' => 'Petugas Tapersip',
            'description' => 'Staff Pengarsipan',
            'status' => 'active',
            'module_visibility' => ['archive_queue', 'dashboard'],
        ]);

        $tapersipUser = User::factory()->create([
            'role_id' => $tapersipRole->id,
            'unit_id' => $unit->id,
        ]);

        $otherUser = User::factory()->create(['unit_id' => $unit->id]);

        AuditLog::record(action: 'Surat Masuk dari Rekanan Luar', documentId: null, metadata: [], userId: $otherUser->id);

        $component = Livewire::actingAs($tapersipUser)->test(Dashboard::class);
        $component->assertViewHas('canViewAllActivities', true);
        $component->assertSee('Seluruh Sistem');
        $component->assertSee('Surat Masuk dari Rekanan Luar');
    }

    public function test_special_document_booking_records_selected_document_type_name_in_audit_log_metadata()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::where('name', 'like', '%Memo%')->first() 
            ?? DocumentType::firstOrCreate(['name' => 'Memo Internal', 'code' => 'MI', 'numbering_format' => '{NO}/MI/{UNIT}/{YYYY}']);
        $senderDept = Unit::departments()->first() ?? Unit::first();
        $recipientDept = Unit::departments()->where('id', '!=', $senderDept->id)->first() ?? $senderDept;
        $classification = Classification::whereNotNull('parent_id')->first() ?? Classification::first();

        Livewire::actingAs($superadmin)
            ->test(InternalCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $senderDept->id)
            ->set('recipient_id', $recipientDept->id)
            ->set('subject', 'Uji Coba Audit Trail Naskah Khusus')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber')
            ->assertHasNoErrors();

        $log = AuditLog::where('user_id', $superadmin->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Booking Nomor Surat Khusus', $log->action);
        $this->assertIsArray($log->metadata);
        $this->assertEquals($docType->name, $log->metadata['type']);
        $this->assertNotEquals('internal', $log->metadata['type']);
    }

    public function test_scan_queue_records_document_type_name_in_audit_log_metadata_for_internal_document()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::where('name', 'like', '%Surat Perintah%')->first()
            ?? DocumentType::firstOrCreate(['name' => 'Surat Perintah', 'code' => 'SPRIN', 'numbering_format' => '{NO}/SPRIN/{YYYY}']);

        $document = Document::create([
            'number' => '055/SPRIN/X/2026',
            'subject' => 'Surat Perintah Tugas Khusus',
            'type' => 'internal',
            'document_type_id' => $docType->id,
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $file = UploadedFile::fake()->create('scan_sprin.pdf', 150, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openUploadModal', $document->id)
            ->set('finalFile', $file)
            ->call('saveFinalFile')
            ->assertHasNoErrors();

        $log = AuditLog::where('document_id', $document->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Unggah Scan Fisik Arsip', $log->action);
        $this->assertEquals($docType->name, $log->metadata['type']);
    }

    public function test_audit_trail_index_renders_document_type_for_backward_compatible_internal_logs()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::firstOrCreate(['name' => 'Surat Keputusan Direksi', 'code' => 'SKD', 'numbering_format' => '{NO}/SKD/{YYYY}']);

        $document = Document::create([
            'number' => '100/SKD/X/2026',
            'subject' => 'SK Direksi Legacy Internal',
            'type' => 'internal',
            'document_type_id' => $docType->id,
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        // Simulasikan log lama yang masih tersimpan dengan type => 'internal'
        AuditLog::record(
            action: 'Registrasi & Arsip Surat Khusus: ' . $document->number,
            documentId: $document->id,
            metadata: [
                'subject' => $document->subject,
                'type' => 'internal',
            ],
            userId: $superadmin->id
        );

        Livewire::actingAs($superadmin)
            ->test(AuditTrailIndex::class)
            ->assertSee($docType->name)
            ->assertDontSee('"type":"internal"');
    }

    public function test_dashboard_monitoring_table_displays_specific_document_type_name_for_internal_documents()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::where('name', 'like', '%Surat Tugas%')->first()
            ?? DocumentType::firstOrCreate(['name' => 'Surat Tugas', 'code' => 'STG', 'numbering_format' => '{NO}/STG/{YYYY}']);

        $document = Document::create([
            'number' => '777/STG/X/2026',
            'subject' => 'Surat Tugas Monitoring Khusus',
            'type' => 'internal',
            'document_type_id' => $docType->id,
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(Dashboard::class)
            ->assertSee('777/STG/X/2026')
            ->assertSee($docType->name);
    }

    public function test_superadmin_sees_loan_circulation_stats_card_on_dashboard()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '888/DASH/X/2026',
            'subject' => 'Surat Uji Pinjam Dashboard',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1025',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now()->subDays(10),
            'expected_return_date' => now()->subDays(3),
            'purpose' => 'Uji Statistik Dashboard',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(Dashboard::class)
            ->assertSee('Sirkulasi Berkas')
            ->assertSee('1 Terlambat');
    }

    public function test_staff_sees_personal_loan_stats_card_on_dashboard()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleStaff = Role::create([
            'name' => 'Staf Pengguna Pinjam',
            'slug' => 'staf-pengguna-pinjam',
            'module_visibility' => ['document_borrowing', 'dashboard'],
        ]);

        $staffUser = User::factory()->create([
            'role_id' => $roleStaff->id,
            'unit_id' => $unit->id,
        ]);

        $otherUser = User::factory()->create([
            'role_id' => $roleStaff->id,
            'unit_id' => $unit->id,
        ]);

        $documentA = Document::create([
            'number' => '889/DASH/X/2026',
            'subject' => 'Surat Pinjam Staff User',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $documentB = Document::create([
            'number' => '890/DASH/X/2026',
            'subject' => 'Surat Pinjam Other User',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        // Pinjaman milik staffUser
        DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1026',
            'document_id' => $documentA->id,
            'user_id' => $staffUser->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji Pinjaman Personal',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        // Pinjaman milik otherUser
        DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1027',
            'document_id' => $documentB->id,
            'user_id' => $otherUser->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji Pinjaman Orang Lain',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        Livewire::actingAs($staffUser)
            ->test(Dashboard::class)
            ->assertSee('Pinjaman Saya')
            ->assertDontSee('Sirkulasi Berkas');
    }
}
