<?php

namespace App\Notifications;

use App\Models\DocumentLoan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentLoanReturnedNotification extends Notification
{
    use Queueable;

    public DocumentLoan $loan;
    public ?User $officer;
    public ?string $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct(DocumentLoan $loan, ?User $officer = null, ?string $notes = null)
    {
        $this->loan = $loan;
        $this->officer = $officer;
        $this->notes = $notes;
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

        $conditionNotes = $this->notes ?: ($this->loan->notes ?: 'Berkas fisik diterima kembali dalam kondisi baik dan lengkap.');

        return (new MailMessage)
            ->subject("Pengembalian Berkas Fisik {$this->loan->loan_code} Berhasil Dikonfirmasi")
            ->view('emails.loan-returned', [
                'recipientName' => $notifiable->name ?? 'Staf Pemohon',
                'loan' => $this->loan,
                'document' => $document,
                'documentType' => $docType,
                'officer' => $this->officer,
                'notes' => $conditionNotes,
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
            'notes' => $this->notes,
        ];
    }
}
