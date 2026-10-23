<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\DocumentLoan;
use App\Notifications\DocumentLoanReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendOverdueLoanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:send-overdue-reminders {--force : Abaikan rate-limiting 1 hari dan kirim ulang pengingat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirimkan email pengingat otomatis kepada staf peminjam untuk seluruh berkas fisik yang telah melewati batas jatuh tempo pengembalian.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->toDateString();
        $isForce = (bool) $this->option('force');

        $overdueLoans = DocumentLoan::with(['document.documentType', 'user'])
            ->where('status', 'borrowed')
            ->whereDate('expected_return_date', '<', $today)
            ->get();

        if ($overdueLoans->isEmpty()) {
            $this->info('Tidak ada berkas fisik pinjaman yang melewati batas jatuh tempo saat ini.');
            return Command::SUCCESS;
        }

        $sentCount = 0;
        $skippedCount = 0;
        $todayKey = now()->format('Y-m-d');

        foreach ($overdueLoans as $loan) {
            $borrower = $loan->user;
            if (!$borrower || !filter_var($borrower->email, FILTER_VALIDATE_EMAIL)) {
                $this->warn("Dilewati: Pemohon pada tiket {$loan->loan_code} tidak memiliki email valid.");
                $skippedCount++;
                continue;
            }

            $cacheKey = "overdue_reminder_sent_{$loan->id}_{$todayKey}";
            if (!$isForce && Cache::has($cacheKey)) {
                $this->line("Dilewati: Pengingat untuk tiket {$loan->loan_code} sudah pernah dikirim hari ini ({$todayKey}).");
                $skippedCount++;
                continue;
            }

            try {
                $borrower->notify(new DocumentLoanReminderNotification($loan));
                Cache::put($cacheKey, true, now()->endOfDay());

                AuditLog::record(
                    action: "Pengingat Otomatis Jatuh Tempo: " . ($loan->document?->number ?: 'Dokumen') . " (Tiket: {$loan->loan_code})",
                    documentId: $loan->document_id,
                    metadata: [
                        'loan_code' => $loan->loan_code,
                        'borrower_id' => $loan->user_id,
                        'borrower_email' => $borrower->email,
                        'expected_return_date' => $loan->expected_return_date?->toDateString(),
                    ]
                );

                $sentCount++;
                $this->info("✓ Terkirim: Tiket {$loan->loan_code} ke {$borrower->email}");
            } catch (\Throwable $e) {
                Log::warning("Gagal mengirim email pengingat keterlambatan berkas {$loan->loan_code}: " . $e->getMessage());
                $this->error("✗ Gagal mengirim ke {$borrower->email}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Proses selesai: {$sentCount} pengingat terkirim, {$skippedCount} dilewati.");

        return Command::SUCCESS;
    }
}
