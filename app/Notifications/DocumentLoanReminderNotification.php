<?php

namespace App\Notifications;

use App\Models\DocumentLoan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentLoanReminderNotification extends Notification
{
    use Queueable;

    public DocumentLoan $loan;
    public ?User $officer;

    /**
     * Create a new notification instance.
     */
    public function __construct(DocumentLoan $loan, ?User $officer = null)
    {
        $this->loan = $loan;
        $this->officer = $officer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->loan->loadMissing(['document.documentType', 'user.unit']);
        $document = $this->loan->document;

        $docType = $document?->documentType?->name
            ?? ($document?->type === 'outgoing' ? 'Surat Keluar' : ($document?->type === 'incoming' ? 'Surat Masuk' : 'Surat Khusus'));

        $isOverdue = $this->loan->expected_return_date && now()->isAfter($this->loan->expected_return_date);

        $subject = $isOverdue
            ? "Pemberitahuan Keterlambatan Pengembalian Berkas Fisik {$this->loan->loan_code}"
            : "Pengingat Batas Pengembalian Berkas Fisik {$this->loan->loan_code}";

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.loan-reminder', [
                'recipientName' => $notifiable->name ?? 'Staf Pemohon',
                'loan' => $this->loan,
                'document' => $document,
                'documentType' => $docType,
                'officer' => $this->officer,
                'isOverdue' => $isOverdue,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'loan_id' => $this->loan->id,
            'loan_code' => $this->loan->loan_code,
            'document_id' => $this->loan->document_id,
            'officer_id' => $this->officer?->id,
        ];
    }
}
