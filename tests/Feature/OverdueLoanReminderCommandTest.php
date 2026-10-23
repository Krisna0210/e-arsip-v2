<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\DocumentLoanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OverdueLoanReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_command_sends_reminder_to_overdue_loans()
    {
        Notification::fake();

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        // 1. Borrower dengan pinjaman lewat jatuh tempo
        $borrowerOverdue = User::factory()->create([
            'name' => 'Adit Peminjam Terlambat',
            'email' => 'adit.terlambat@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $docOverdue = Document::create([
            'number' => '111/SP/X/2026',
            'subject' => 'Surat Terlambat Otomatis',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-01',
            'created_by' => $superadmin->id,
        ]);

        $loanOverdue = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1022',
            'document_id' => $docOverdue->id,
            'user_id' => $borrowerOverdue->id,
            'borrow_date' => now()->subDays(10),
            'expected_return_date' => now()->subDays(2),
            'purpose' => 'Uji Cron Job Overdue',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        // 2. Borrower dengan pinjaman masih aktif sesuai jadwal
        $borrowerOnSchedule = User::factory()->create([
            'name' => 'Bela Staf Tepat Waktu',
            'email' => 'bela.ontime@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $docOnSchedule = Document::create([
            'number' => '112/SP/X/2026',
            'subject' => 'Surat Masih Dalam Jadwal',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-15',
            'created_by' => $superadmin->id,
        ]);

        $loanOnSchedule = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1023',
            'document_id' => $docOnSchedule->id,
            'user_id' => $borrowerOnSchedule->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji Cron Job On Time',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        // Jalankan perintah console artisan
        $exitCode = Artisan::call('loans:send-overdue-reminders');
        $this->assertEquals(0, $exitCode);

        // Pastikan hanya peminjam yang terlambat yang menerima notifikasi email
        Notification::assertSentTo($borrowerOverdue, DocumentLoanReminderNotification::class, function ($n) use ($loanOverdue) {
            return $n->loan->id === $loanOverdue->id;
        });

        Notification::assertNotSentTo($borrowerOnSchedule, DocumentLoanReminderNotification::class);

        // Pastikan Audit Log otomatis tercatat
        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $docOverdue->id,
            'action' => "Pengingat Otomatis Jatuh Tempo: 111/SP/X/2026 (Tiket: {$loanOverdue->loan_code})",
        ]);
    }

    public function test_command_rate_limiting_prevents_duplicate_reminders_on_same_day()
    {
        Notification::fake();

        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'name' => 'Candra Peminjam',
            'email' => 'candra@perusahaan.co.id',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $doc = Document::create([
            'number' => '113/SP/X/2026',
            'subject' => 'Surat Uji Rate Limit Pengingat',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => '2026-10-01',
            'created_by' => $superadmin->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'TIKET-202610-1024',
            'document_id' => $doc->id,
            'user_id' => $borrower->id,
            'borrow_date' => now()->subDays(8),
            'expected_return_date' => now()->subDays(1),
            'purpose' => 'Uji Rate Limit Spam',
            'status' => 'borrowed',
            'officer_id' => $superadmin->id,
        ]);

        // Eksekusi pertama: Berhasil dikirim
        Artisan::call('loans:send-overdue-reminders');
        Notification::assertSentToTimes($borrower, DocumentLoanReminderNotification::class, 1);

        // Eksekusi kedua di hari yang sama: Dilewati (mencegah spam ke inbox staf)
        Artisan::call('loans:send-overdue-reminders');
        Notification::assertSentToTimes($borrower, DocumentLoanReminderNotification::class, 1);

        // Eksekusi dengan opsi --force: Tetap dikirim
        Artisan::call('loans:send-overdue-reminders', ['--force' => true]);
        Notification::assertSentToTimes($borrower, DocumentLoanReminderNotification::class, 2);
    }

    public function test_command_displays_no_loans_message_when_all_on_schedule()
    {
        Notification::fake();

        Artisan::call('loans:send-overdue-reminders');
        $output = Artisan::output();

        $this->assertStringContainsString('Tidak ada berkas fisik pinjaman yang melewati batas jatuh tempo saat ini', $output);
    }
}
