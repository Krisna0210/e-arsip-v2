<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentArchivedNotification extends Notification
{
    use Queueable;

    public Document $document;
    public ?User $officer;
    public ?DocumentVersion $version;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document, ?User $officer = null, ?DocumentVersion $version = null)
    {
        $this->document = $document;
        $this->officer = $officer;
        $this->version = $version;
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
        $this->document->loadMissing(['folder.parent', 'documentType']);

        $docType = $this->document->documentType?->name
            ?? ($this->document->type === 'outgoing' ? 'Surat Keluar' : ($this->document->type === 'incoming' ? 'Surat Masuk' : 'Surat Khusus'));

        $previewUrl = $this->version
            ? route('document.preview', $this->version->id)
            : ($this->document->currentVersion ? route('document.preview', $this->document->currentVersion->id) : route('dashboard'));

        return (new MailMessage)
            ->subject("Dokumen {$this->document->number} Telah Selesai Diarsipkan")
            ->view('emails.document-archived', [
                'recipientName' => $notifiable->name ?? 'Staf Pendaftar',
                'document' => $this->document,
                'documentType' => $docType,
                'officer' => $this->officer,
                'previewUrl' => $previewUrl,
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
            'document_id' => $this->document->id,
            'number' => $this->document->number,
            'subject' => $this->document->subject,
            'officer_id' => $this->officer?->id,
        ];
    }
}
