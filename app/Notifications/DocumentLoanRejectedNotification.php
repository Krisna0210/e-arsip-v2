<?php

namespace App\Notifications;

use App\Models\DocumentLoan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentLoanRejectedNotification extends Notification
{
    use Queueable;

    public DocumentLoan $loan;
    public ?User $officer;
    public ?string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(DocumentLoan $loan, ?User $officer = null, ?string $reason = null)
    {
        $this->loan = $loan;
        $this->officer = $officer;
        $this->reason = $reason;
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

        $rejectionReason = $this->reason ?: ($this->loan->notes ?: 'Tidak ada alasan khusus yang dicantumkan.');

        return (new MailMessage)
            ->subject("Permohonan Pinjam Berkas Fisik {$this->loan->loan_code} Ditolak")
            ->view('emails.loan-rejected', [
                'recipientName' => $notifiable->name ?? 'Staf Pemohon',
                'loan' => $this->loan,
                'document' => $document,
                'documentType' => $docType,
                'officer' => $this->officer,
                'reason' => $rejectionReason,
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
            'reason' => $this->reason,
        ];
    }
}
