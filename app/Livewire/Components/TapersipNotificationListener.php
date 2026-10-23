<?php

namespace App\Livewire\Components;

use App\Models\Document;
use App\Models\DocumentLoan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class TapersipNotificationListener extends Component
{
    public ?int $lastKnownRequestId = null;
    public ?int $lastKnownDocId = null;
    public ?string $lastCheckedAt = null;
    public int $pendingCount = 0;

    public function mount()
    {
        $this->lastCheckedAt = now()->toDateTimeString();

        // Ambil ID tertinggi saat komponen pertama kali dimuat agar tidak membunyikan data lama
        $latestLoan = DocumentLoan::latest('id')->first();
        $this->lastKnownRequestId = $latestLoan ? $latestLoan->id : 0;

        $latestDoc = Document::where('status', 'waiting_for_file')->latest('id')->first();
        $this->lastKnownDocId = $latestDoc ? $latestDoc->id : 0;

        $this->updatePendingCount();
    }

    #[On('loan-status-changed')]
    public function handleLoanStatusChanged(): void
    {
        $this->updatePendingCount();
    }

    protected function getDismissedKeys(): array
    {
        if (!Auth::check()) {
            return [];
        }
        return Cache::get('tapersip_read_notifs_' . Auth::id(), []);
    }

    protected function addDismissedKey(string $key): void
    {
        if (!Auth::check()) {
            return;
        }
        $keys = $this->getDismissedKeys();
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            // Simpan riwayat dibaca selama 30 hari
            Cache::put('tapersip_read_notifs_' . Auth::id(), $keys, now()->addDays(30));
        }
    }

    public function dismissAndRedirect(string $key, string $targetUrl)
    {
        $this->addDismissedKey($key);
        $this->updatePendingCount();
        return redirect()->to($targetUrl);
    }

    public function markAllAsRead()
    {
        if (!Auth::check() || !Auth::user()->isTapersip()) {
            return;
        }

        $allKeys = [];

        // Kunci semua antrean scan waiting_for_file saat ini
        $waitingDocs = Document::where('status', 'waiting_for_file')
            ->whereIn('type', ['outgoing', 'internal'])
            ->pluck('id');
        foreach ($waitingDocs as $docId) {
            $allKeys[] = "scan_{$docId}";
        }

        // Kunci semua permohonan pinjam requested/cancelled saat ini
        $loans = DocumentLoan::whereIn('status', ['requested', 'cancelled'])->get(['id', 'status']);
        foreach ($loans as $loan) {
            $allKeys[] = "loan_{$loan->id}_{$loan->status}";
        }

        $merged = array_unique(array_merge($this->getDismissedKeys(), $allKeys));
        Cache::put('tapersip_read_notifs_' . Auth::id(), $merged, now()->addDays(30));

        $this->updatePendingCount();
        $this->dispatch('notify', ['message' => 'Seluruh notifikasi telah ditandai sebagai dibaca.', 'type' => 'info']);
    }

    public function updatePendingCount(): void
    {
        if (!Auth::check() || !Auth::user()->isTapersip()) {
            $this->pendingCount = 0;
            return;
        }

        $dismissedKeys = $this->getDismissedKeys();

        // 1. Hitung antrean scan yang belum di-dismiss
        $waitingDocIds = Document::where('status', 'waiting_for_file')
            ->whereIn('type', ['outgoing', 'internal'])
            ->pluck('id')
            ->filter(fn ($id) => !in_array("scan_{$id}", $dismissedKeys))
            ->count();

        // 2. Hitung permohonan pinjam requested yang belum di-dismiss
        $requestedLoanIds = DocumentLoan::where('status', 'requested')
            ->get(['id', 'status'])
            ->filter(fn ($loan) => !in_array("loan_{$loan->id}_{$loan->status}", $dismissedKeys))
            ->count();

        $this->pendingCount = $waitingDocIds + $requestedLoanIds;
    }

    public function checkLoanEvents()
    {
        $user = Auth::user();
        if (!$user || !$user->isTapersip()) {
            return;
        }

        $currentTime = now()->toDateTimeString();
        $hasChanges = false;

        // 1. Cek pengajuan pinjam fisik baru (status: requested dan ID lebih besar dari yang terakhir diketahui)
        $newRequests = DocumentLoan::with(['document', 'user.unit'])
            ->where('id', '>', $this->lastKnownRequestId ?? 0)
            ->where('status', 'requested')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($newRequests as $loan) {
            $hasChanges = true;
            $this->lastKnownRequestId = max($this->lastKnownRequestId ?? 0, $loan->id);

            $unitName = $loan->user?->unit?->name ? " ({$loan->user->unit->name})" : '';
            $docNumber = $loan->document?->number ?: 'Naskah';

            $this->dispatch('desktop-notify', [
                'title' => 'Permohonan Pinjam Fisik Baru',
                'body' => "{$loan->user?->name}{$unitName} mengajukan pinjam berkas {$docNumber}. Kode: {$loan->loan_code}",
                'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                'type' => 'loan_requested',
                'loan_code' => $loan->loan_code,
            ]);
        }

        $latestAnyLoan = DocumentLoan::latest('id')->first();
        if ($latestAnyLoan && $latestAnyLoan->id > ($this->lastKnownRequestId ?? 0)) {
            $this->lastKnownRequestId = $latestAnyLoan->id;
        }

        // 2. Cek pembatalan permohonan pinjam (status: cancelled dengan updated_at setelah pengecekan terakhir)
        if ($this->lastCheckedAt) {
            $cancelledLoans = DocumentLoan::with(['document', 'user'])
                ->where('status', 'cancelled')
                ->where('updated_at', '>=', $this->lastCheckedAt)
                ->orderBy('updated_at', 'asc')
                ->get();

            foreach ($cancelledLoans as $loan) {
                $hasChanges = true;
                $docNumber = $loan->document?->number ?: 'Naskah';
                $this->dispatch('desktop-notify', [
                    'title' => 'Permohonan Pinjam Dibatalkan',
                    'body' => "Permohonan pinjam tiket {$loan->loan_code} ({$docNumber}) telah dibatalkan oleh pemohon.",
                    'url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                    'type' => 'loan_cancelled',
                    'loan_code' => $loan->loan_code,
                ]);
            }
        }

        // 3. Cek naskah baru yang menunggu upload scan PDF (status: waiting_for_file)
        $newScanDocs = Document::with(['createdBy.unit', 'documentType'])
            ->where('id', '>', $this->lastKnownDocId ?? 0)
            ->where('status', 'waiting_for_file')
            ->whereIn('type', ['outgoing', 'internal'])
            ->orderBy('id', 'asc')
            ->get();

        foreach ($newScanDocs as $doc) {
            $hasChanges = true;
            $this->lastKnownDocId = max($this->lastKnownDocId ?? 0, $doc->id);
            $stafName = $doc->createdBy ? $doc->createdBy->name : 'Staf';

            $this->dispatch('desktop-notify', [
                'title' => 'Permintaan Upload Scan Naskah',
                'body' => "{$stafName} mendaftarkan nomor {$doc->number}. Menunggu unggah berkas scan fisik.",
                'url' => route('registry.scan-queue', ['highlight' => "doc-{$doc->id}"]),
                'type' => 'scan_requested',
                'loan_code' => $doc->number,
            ]);
        }

        $latestAnyDoc = Document::where('status', 'waiting_for_file')->latest('id')->first();
        if ($latestAnyDoc && $latestAnyDoc->id > ($this->lastKnownDocId ?? 0)) {
            $this->lastKnownDocId = $latestAnyDoc->id;
        }

        if ($hasChanges) {
            $this->dispatch('loan-status-changed');
        }

        $this->lastCheckedAt = $currentTime;
        $this->updatePendingCount();
    }

    public function render()
    {
        $feedItems = collect();

        if (Auth::check() && Auth::user()->isTapersip()) {
            $dismissedKeys = $this->getDismissedKeys();

            // 1. Ambil antrean scan waiting_for_file terkini
            $scanItems = Document::with(['documentType', 'createdBy.unit'])
                ->where('status', 'waiting_for_file')
                ->whereIn('type', ['outgoing', 'internal'])
                ->latest('created_at')
                ->take(15)
                ->get()
                ->map(function ($doc) use ($dismissedKeys) {
                    $key = "scan_{$doc->id}";

                    return [
                        'key' => $key,
                        'category' => 'scan_queue',
                        'badge' => 'Upload Scan',
                        'is_unread' => !in_array($key, $dismissedKeys),
                        'identifier' => $doc->number ?: 'Tanpa Nomor',
                        'title' => 'Permintaan Upload Scan',
                        'actor' => $doc->createdBy?->name ?? 'Staf',
                        'unit' => $doc->createdBy?->unit?->name ?? ($doc->type === 'outgoing' ? 'Surat Keluar' : 'Surat Khusus'),
                        'extra' => $doc->subject,
                        'timestamp' => $doc->created_at,
                        'target_url' => route('registry.scan-queue', ['highlight' => "doc-{$doc->id}"]),
                    ];
                });

            // 2. Ambil sirkulasi peminjaman (requested / cancelled) terkini
            $loanItems = DocumentLoan::with(['document', 'user.unit'])
                ->whereIn('status', ['requested', 'cancelled'])
                ->latest('updated_at')
                ->take(15)
                ->get()
                ->map(function ($loan) use ($dismissedKeys) {
                    $key = "loan_{$loan->id}_{$loan->status}";
                    $isRequested = $loan->status === 'requested';

                    return [
                        'key' => $key,
                        'category' => 'loan_circulation',
                        'badge' => $isRequested ? 'Pinjam Fisik' : 'Batal Pinjam',
                        'is_unread' => !in_array($key, $dismissedKeys),
                        'identifier' => $loan->loan_code,
                        'title' => $isRequested ? 'Pengajuan Pinjam Fisik' : 'Pembatalan Pinjam Fisik',
                        'actor' => $loan->user?->name ?? 'Staf',
                        'unit' => $loan->user?->unit?->name ?? '-',
                        'extra' => $loan->document?->number ?? 'Tanpa Nomor',
                        'timestamp' => $loan->updated_at,
                        'target_url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                    ];
                });

            // Gabungkan kedua kategori notifikasi, hanya tampilkan yang belum di-dismiss, urutkan dari waktu terbaru
            $feedItems = $scanItems->concat($loanItems)
                ->filter(fn ($item) => $item['is_unread'])
                ->sortByDesc('timestamp')
                ->take(20)
                ->values();
        }

        return view('livewire.components.tapersip-notification-listener', [
            'feedItems' => $feedItems,
        ]);
    }
}
