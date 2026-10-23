<?php

namespace Tests\Feature;

use App\Livewire\Registry\DocumentLoanIndex;
use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\DocumentType;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\DocumentLoanApprovedNotification;
use App\Notifications\DocumentLoanRejectedNotification;
use App\Notifications\DocumentLoanReminderNotification;
use App\Notifications\DocumentLoanReturnedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentLoanNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_borrower_receives_email_notification_when_loan_is_approved()
    {
        Notification::fake();

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'name' => 'Budi Staf Pemohon',
            'email' => 'budi.pemohon@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '077/SK/X/2026',
            'subject' => 'Surat Keputusan Kerjasama Proyek',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji Notifikasi Persetujuan',
            'status' => 'requested',
        ]);

        // Petugas Tapersip menyetujui peminjaman hardcopy
        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('approveLoan', $loan->id)
            ->assertDispatched('notify');

        $loan->refresh();
        $this->assertEquals('borrowed', $loan->status);

        // Verifikasi notifikasi terkirim tepat ke staf pemohon
        Notification::assertSentTo(
            $borrower,
            DocumentLoanApprovedNotification::class,
            function ($notification) use ($loan, $superadmin) {
                return $notification->loan->id === $loan->id
                    && $notification->officer->id === $superadmin->id;
            }
        );
    }

    public function test_other_users_do_not_receive_loan_approval_notification()
    {
        Notification::fake();

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'name' => 'Siti Pemohon',
            'email' => 'siti.pemohon@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $otherStaff = User::factory()->create([
            'name' => 'Doni Staf Lain',
            'email' => 'doni.lain@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '088/SP/X/2026',
            'subject' => 'Surat Perintah Kerja Fasilitas',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(7),
            'purpose' => 'Uji Anti Kebocoran Email',
            'status' => 'requested',
        ]);

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('approveLoan', $loan->id);

        // Staf lain sama sekali tidak menerima email
        Notification::assertNotSentTo($otherStaff, DocumentLoanApprovedNotification::class);

        // Hanya staf pemohon yang menerima
        Notification::assertSentTo($borrower, DocumentLoanApprovedNotification::class);
    }

    public function test_loan_approved_email_renders_correctly_without_footer()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $borrower = User::factory()->create([
            'name' => 'Rina Staff Legal',
            'email' => 'rina.legal@perusahaan.co.id',
            'unit_id' => $unit->id,
        ]);

        $docType = DocumentType::where('name', 'like', '%Surat Perintah%')->first()
            ?? DocumentType::firstOrCreate(['name' => 'Surat Perintah', 'code' => 'SPRIN', 'numbering_format' => '{NO}/SPRIN/{YYYY}']);

        $document = Document::create([
            'number' => '025/SPRIN/X/2026',
            'subject' => 'Surat Perintah Investigasi Internal',
            'type' => 'internal',
            'document_type_id' => $docType->id,
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-21',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-9999',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => '2026-10-21',
            'expected_return_date' => '2026-10-28',
            'purpose' => 'Pemeriksaan Kasus Legal',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        $notification = new DocumentLoanApprovedNotification($loan, $superadmin);
        $mail = $notification->toMail($borrower);

        // 1. Verifikasi subjek email memuat kode tiket resmi
        $this->assertEquals('Peminjaman Berkas Fisik TIKET-202610-9999 Telah Disetujui', $mail->subject);

        // 2. Render HTML email view dan verifikasi kelengkapan datanya
        $renderedHtml = view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('Rina Staff Legal', $renderedHtml);
        $this->assertStringContainsString('TIKET-202610-9999', $renderedHtml);
        $this->assertStringContainsString('025/SPRIN/X/2026', $renderedHtml);
        $this->assertStringContainsString($docType->name, $renderedHtml);
        $this->assertStringContainsString('Surat Perintah Investigasi Internal', $renderedHtml);
        $this->assertStringContainsString($superadmin->name, $renderedHtml);

        // 3. Pastikan tidak ada elemen footer
        $this->assertStringNotContainsString('<td class="footer">', $renderedHtml);
        $this->assertStringNotContainsString('footer-text', $renderedHtml);
    }

    public function test_approving_loan_dispatches_success_toast_notification()
    {
        Notification::fake();

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'name' => 'Ahmad Teknisi',
            'email' => 'ahmad.teknisi@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '101/ND/X/2026',
            'subject' => 'Nota Dinas Pemeliharaan Server',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-21',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1011',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(3),
            'purpose' => 'Uji Event Toast Sukses',
            'status' => 'requested',
        ]);

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('approveLoan', $loan->id)
            ->assertDispatched('desktop-notify', function ($name, $params) use ($loan, $borrower) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'loan_approved_success'
                    && str_contains($payload['title'] ?? '', 'Peminjaman Disetujui & Email Terkirim')
                    && str_contains($payload['body'] ?? '', $borrower->name)
                    && str_contains($payload['body'] ?? '', $borrower->email)
                    && ($payload['loan_code'] ?? '') === $loan->loan_code;
            })
            ->assertDispatched('loan-status-changed');

        $loan->refresh();
        $this->assertEquals('borrowed', $loan->status);
    }

    public function test_approving_loan_handles_email_failure_gracefully_and_dispatches_warning_toast()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        // Buat mock user yang melempar Exception saat notify() dipanggil
        $borrower = User::factory()->create([
            'name' => 'Bambang Jaringan',
            'email' => 'bambang.jaringan@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '102/BA/X/2026',
            'subject' => 'Berita Acara Insiden Jaringan',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-21',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1012',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(2),
            'purpose' => 'Uji Penanganan Gagal SMTP',
            'status' => 'requested',
        ]);

        // Simulasikan error SMTP menggunakan Notification facade fake yang melempar exception
        // atau Notification::assertNothingSent
        // Kita intercept dengan mock Notification channel
        Notification::shouldReceive('send')
            ->andThrow(new \Exception('Connection refused to SMTP mail server:25'));

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('approveLoan', $loan->id)
            ->assertDispatched('desktop-notify', function ($name, $params) use ($loan, $borrower) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'loan_approved_mail_failed'
                    && str_contains($payload['title'] ?? '', 'Email Gagal')
                    && str_contains($payload['body'] ?? '', $borrower->email);
            });

        // Verifikasi bahwa berkas fisik tetap berhasil disetujui (tidak rollback fatal error)
        $loan->refresh();
        $this->assertEquals('borrowed', $loan->status);
        $this->assertEquals($superadmin->id, $loan->officer_id);
    }

    public function test_approving_loan_without_borrower_email_dispatches_mail_failed_toast()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        // Borrower tanpa email valid
        $borrower = User::factory()->create([
            'name' => 'Staff Manual',
            'email' => '',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '103/MEMO/X/2026',
            'subject' => 'Memo Tanpa Email',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-21',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1013',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(2),
            'purpose' => 'Uji Peminjam Tanpa Email',
            'status' => 'requested',
        ]);

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('approveLoan', $loan->id)
            ->assertDispatched('desktop-notify', function ($name, $params) use ($loan) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'loan_approved_mail_failed'
                    && str_contains($payload['title'] ?? '', 'Tanpa Email');
            });

        $loan->refresh();
        $this->assertEquals('borrowed', $loan->status);
    }

    public function test_borrower_receives_rejection_email_and_toast_when_loan_is_rejected()
    {
        Notification::fake();

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'name' => 'Hendro Analis',
            'email' => 'hendro.analis@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '104/SK/X/2026',
            'subject' => 'Surat Keputusan Operasional Gudang',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-21',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1014',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(4),
            'purpose' => 'Uji Penolakan Peminjaman',
            'status' => 'requested',
        ]);

        $rejectionReason = 'Berkas fisik sedang digunakan untuk audit BPK RI';

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('openRejectModal', $loan->id)
            ->assertSet('selectedLoanId', $loan->id)
            ->assertDispatched('open-modal', 'modal-loan-reject')
            ->set('rejectNotes', $rejectionReason)
            ->call('confirmReject')
            ->assertDispatched('close-modal', 'modal-loan-reject')
            ->assertDispatched('loan-status-changed')
            ->assertDispatched('desktop-notify', function ($name, $params) use ($loan, $borrower) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'loan_rejected_success'
                    && str_contains($payload['title'] ?? '', 'Permohonan Ditolak & Email Terkirim')
                    && str_contains($payload['body'] ?? '', $borrower->name)
                    && str_contains($payload['body'] ?? '', $borrower->email)
                    && ($payload['loan_code'] ?? '') === $loan->loan_code;
            });

        $loan->refresh();
        $this->assertEquals('rejected', $loan->status);
        $this->assertEquals($rejectionReason, $loan->notes);
        $this->assertEquals($superadmin->id, $loan->officer_id);

        Notification::assertSentTo(
            $borrower,
            DocumentLoanRejectedNotification::class,
            function ($notification) use ($loan, $rejectionReason) {
                return $notification->loan->id === $loan->id
                    && $notification->reason === $rejectionReason;
            }
        );
    }

    public function test_loan_rejected_email_renders_correctly_without_footer()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $borrower = User::factory()->create([
            'name' => 'Maya Staf Finance',
            'email' => 'maya.finance@perusahaan.co.id',
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '099/MEMO/X/2026',
            'subject' => 'Memo Internal Pengadaan Server',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-21',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1015',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => '2026-10-21',
            'expected_return_date' => '2026-10-25',
            'purpose' => 'Uji Template Email Tolak',
            'status' => 'rejected',
            'notes' => 'Dokumen fisik sedang dalam perbaikan jilid',
            'officer_id' => $superadmin->id,
        ]);

        $notification = new DocumentLoanRejectedNotification($loan, $superadmin, 'Dokumen fisik sedang dalam perbaikan jilid');
        $mail = $notification->toMail($borrower);

        // 1. Verifikasi subjek email
        $this->assertEquals('Permohonan Pinjam Berkas Fisik TIKET-202610-1015 Ditolak', $mail->subject);

        // 2. Render HTML email view
        $renderedHtml = view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('Maya Staf Finance', $renderedHtml);
        $this->assertStringContainsString('TIKET-202610-1015', $renderedHtml);
        $this->assertStringContainsString('099/MEMO/X/2026', $renderedHtml);
        $this->assertStringContainsString('Memo Internal Pengadaan Server', $renderedHtml);
        $this->assertStringContainsString('Dokumen fisik sedang dalam perbaikan jilid', $renderedHtml);
        $this->assertStringContainsString($superadmin->name, $renderedHtml);

        // 3. Pastikan tidak ada elemen footer
        $this->assertStringNotContainsString('<td class="footer">', $renderedHtml);
        $this->assertStringNotContainsString('footer-text', $renderedHtml);
    }

    public function test_borrower_receives_return_confirmation_email_and_toast_when_loan_is_returned()
    {
        Notification::fake();

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'name' => 'Dimas Akuntan',
            'email' => 'dimas.akuntan@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '105/SPK/X/2026',
            'subject' => 'Surat Perjanjian Kerja Sama Logistik',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-21',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1016',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now()->subDays(3),
            'expected_return_date' => now()->addDays(4),
            'purpose' => 'Uji Konfirmasi Pengembalian Berkas',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        $conditionNotes = 'Berkas fisik diterima kembali lengkap, map rapi dan bersih.';

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('openReturnModal', $loan->id)
            ->assertSet('selectedLoanId', $loan->id)
            ->assertDispatched('open-modal', 'modal-loan-return')
            ->set('returnNotes', $conditionNotes)
            ->call('confirmReturn')
            ->assertDispatched('close-modal', 'modal-loan-return')
            ->assertDispatched('loan-status-changed')
            ->assertDispatched('desktop-notify', function ($name, $params) use ($loan, $borrower) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'loan_returned_success'
                    && str_contains($payload['title'] ?? '', 'Berkas Kembali & Email Terkirim')
                    && str_contains($payload['body'] ?? '', $borrower->name)
                    && str_contains($payload['body'] ?? '', $borrower->email)
                    && ($payload['loan_code'] ?? '') === $loan->loan_code;
            });

        $loan->refresh();
        $this->assertEquals('returned', $loan->status);
        $this->assertEquals($conditionNotes, $loan->notes);
        $this->assertNotNull($loan->returned_date);
        $this->assertEquals($superadmin->id, $loan->officer_id);

        Notification::assertSentTo(
            $borrower,
            DocumentLoanReturnedNotification::class,
            function ($notification) use ($loan, $conditionNotes) {
                return $notification->loan->id === $loan->id
                    && $notification->notes === $conditionNotes;
            }
        );
    }

    public function test_loan_returned_email_renders_correctly_without_footer()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $borrower = User::factory()->create([
            'name' => 'Indah Sekar',
            'email' => 'indah.sekar@perusahaan.co.id',
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '106/SK/X/2026',
            'subject' => 'Surat Keputusan Pengangkatan Pegawai',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-21',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1017',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => '2026-10-15',
            'expected_return_date' => '2026-10-22',
            'returned_date' => '2026-10-21',
            'purpose' => 'Pemeriksaan Berkas Kepegawaian',
            'status' => 'returned',
            'notes' => 'Fisik surat lengkap dan tersimpan kembali di Loker A-01',
            'officer_id' => $superadmin->id,
        ]);

        $notification = new DocumentLoanReturnedNotification($loan, $superadmin, 'Fisik surat lengkap dan tersimpan kembali di Loker A-01');
        $mail = $notification->toMail($borrower);

        // 1. Verifikasi subjek email
        $this->assertEquals('Pengembalian Berkas Fisik TIKET-202610-1017 Berhasil Dikonfirmasi', $mail->subject);

        // 2. Render HTML email view
        $renderedHtml = view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('Indah Sekar', $renderedHtml);
        $this->assertStringContainsString('TIKET-202610-1017', $renderedHtml);
        $this->assertStringContainsString('106/SK/X/2026', $renderedHtml);
        $this->assertStringContainsString('Surat Keputusan Pengangkatan Pegawai', $renderedHtml);
        $this->assertStringContainsString('Fisik surat lengkap dan tersimpan kembali di Loker A-01', $renderedHtml);
        $this->assertStringContainsString($superadmin->name, $renderedHtml);

        // 3. Pastikan tidak ada elemen footer
        $this->assertStringNotContainsString('<td class="footer">', $renderedHtml);
        $this->assertStringNotContainsString('footer-text', $renderedHtml);
    }

    public function test_tapersip_can_filter_overdue_loans_in_circulation_table()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'name' => 'Bowo Staf',
            'email' => 'bowo@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $docOverdue = Document::create([
            'number' => '107/SP/X/2026',
            'subject' => 'Surat Terlambat Kembali',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-01',
            'created_by' => $superadmin->id,
        ]);

        $loanOverdue = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1018',
            'document_id' => $docOverdue->id,
            'user_id' => $borrower->id,
            'borrow_date' => now()->subDays(10),
            'expected_return_date' => now()->subDays(3),
            'purpose' => 'Uji Overdue Filter',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        $docActive = Document::create([
            'number' => '108/SP/X/2026',
            'subject' => 'Surat Masih Dalam Jadwal',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-15',
            'created_by' => $superadmin->id,
        ]);

        $loanActive = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1019',
            'document_id' => $docActive->id,
            'user_id' => $borrower->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji Active Filter',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->set('activeTab', 'circulation')
            ->set('circulationStatus', 'overdue')
            ->assertSee('TIKET-202610-1018')
            ->assertDontSee('TIKET-202610-1019');
    }

    public function test_tapersip_can_send_reminder_email_for_borrowed_loan()
    {
        Notification::fake();

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'name' => 'Wawan Engineer',
            'email' => 'wawan.eng@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '109/ND/X/2026',
            'subject' => 'Nota Dinas Perbaikan Jaringan',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-10',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1020',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now()->subDays(5),
            'expected_return_date' => now()->subDays(1),
            'purpose' => 'Uji Kirim Pengingat',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('sendReminderEmail', $loan->id)
            ->assertDispatched('notify')
            ->assertDispatched('desktop-notify', function ($name, $params) use ($loan, $borrower) {
                $payload = is_array($params) && isset($params['type']) ? $params : ($params[0] ?? []);
                return ($payload['type'] ?? '') === 'loan_reminder_success'
                    && str_contains($payload['body'] ?? '', $borrower->name)
                    && str_contains($payload['body'] ?? '', $borrower->email)
                    && ($payload['loan_code'] ?? '') === $loan->loan_code;
            });

        Notification::assertSentTo(
            $borrower,
            DocumentLoanReminderNotification::class,
            function ($notification) use ($loan) {
                return $notification->loan->id === $loan->id;
            }
        );
    }

    public function test_loan_reminder_email_renders_correctly_without_footer()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $borrower = User::factory()->create([
            'name' => 'Surya Kencana',
            'email' => 'surya.kencana@perusahaan.co.id',
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '110/SK/X/2026',
            'subject' => 'Surat Keputusan Evaluasi SOP',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-10',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1021',
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => '2026-10-10',
            'expected_return_date' => '2026-10-17',
            'purpose' => 'Evaluasi Dokumen Kerja',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        $notification = new DocumentLoanReminderNotification($loan, $superadmin);
        $mail = $notification->toMail($borrower);

        $this->assertStringContainsString('TIKET-202610-1021', $mail->subject);

        $renderedHtml = view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('Surya Kencana', $renderedHtml);
        $this->assertStringContainsString('TIKET-202610-1021', $renderedHtml);
        $this->assertStringContainsString('110/SK/X/2026', $renderedHtml);
        $this->assertStringContainsString('Surat Keputusan Evaluasi SOP', $renderedHtml);
        $this->assertStringContainsString($superadmin->name, $renderedHtml);

        // Pastikan tidak ada elemen footer
        $this->assertStringNotContainsString('<td class="footer">', $renderedHtml);
        $this->assertStringNotContainsString('footer-text', $renderedHtml);
    }
}
