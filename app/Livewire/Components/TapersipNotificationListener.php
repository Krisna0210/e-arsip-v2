<?php

namespace App\Livewire\Components;

use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\UserNotificationRead;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class TapersipNotificationListener extends Component
{
    public ?int $lastKnownRequestId = null;
    public ?int $lastKnownDocId = null;
    public ?int $lastKnownCancelDocId = null;
    public ?string $lastCheckedAt = null;
    public int $pendingCount = 0;
    public int $historyCount = 0;

    public function mount()
    {
        $this->lastCheckedAt = now()->toDateTimeString();

        // Ambil ID tertinggi saat komponen pertama kali dimuat agar tidak membunyikan data lama
        $latestLoan = DocumentLoan::latest('id')->first();
        $this->lastKnownRequestId = $latestLoan ? $latestLoan->id : 0;

        $latestDoc = Document::where('status', 'waiting_for_file')->latest('id')->first();
        $this->lastKnownDocId = $latestDoc ? $latestDoc->id : 0;

        $latestCancelDoc = Document::where('status', 'cancellation_requested')->latest('id')->first();
        $this->lastKnownCancelDocId = $latestCancelDoc ? $latestCancelDoc->id : 0;

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
        return UserNotificationRead::where('user_id', Auth::id())
            ->pluck('notification_key')
            ->toArray();
    }

    protected function addDismissedKey(string $key): void
    {
        if (!Auth::check()) {
            return;
        }
        UserNotificationRead::firstOrCreate([
            'user_id' => Auth::id(),
            'notification_key' => $key,
        ]);
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

        // Kunci semua permohonan pembatalan cancellation_requested saat ini
        $cancelDocs = Document::where('status', 'cancellation_requested')->pluck('id');
        foreach ($cancelDocs as $docId) {
            $allKeys[] = "cancel_req_{$docId}";
        }

        $userId = Auth::id();
        $existingKeys = UserNotificationRead::where('user_id', $userId)
            ->whereIn('notification_key', $allKeys)
            ->pluck('notification_key')
            ->toArray();

        $newKeys = array_diff($allKeys, $existingKeys);
        $recordsToInsert = [];
        $now = now();
        foreach ($newKeys as $k) {
            $recordsToInsert[] = [
                'user_id' => $userId,
                'notification_key' => $k,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($recordsToInsert)) {
            UserNotificationRead::insert($recordsToInsert);
        }

        $this->updatePendingCount();
        $this->dispatch('notify', ['message' => 'Seluruh notifikasi telah ditandai sebagai dibaca.', 'type' => 'info']);
    }

    public function updatePendingCount(): void
    {
        if (!Auth::check() || !Auth::user()->isTapersip()) {
            $this->pendingCount = 0;
            $this->historyCount = 0;
            return;
        }

        $readKeys = $this->getDismissedKeys();

        // 1. Hitung antrean scan yang belum dibaca
        $waitingDocIds = Document::where('status', 'waiting_for_file')
            ->whereIn('type', ['outgoing', 'internal'])
            ->pluck('id')
            ->filter(fn ($id) => !in_array("scan_{$id}", $readKeys))
            ->count();

        // 2. Hitung permohonan pinjam requested yang belum dibaca
        $requestedLoanIds = DocumentLoan::where('status', 'requested')
            ->get(['id', 'status'])
            ->filter(fn ($loan) => !in_array("loan_{$loan->id}_{$loan->status}", $readKeys))
            ->count();

        // 3. Hitung permohonan pembatalan nomor yang belum dibaca
        $cancelDocIds = Document::where('status', 'cancellation_requested')
            ->pluck('id')
            ->filter(fn ($id) => !in_array("cancel_req_{$id}", $readKeys))
            ->count();

        $this->pendingCount = $waitingDocIds + $requestedLoanIds + $cancelDocIds;
        $this->historyCount = UserNotificationRead::where('user_id', Auth::id())->count();
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

        // 4. Cek permohonan pembatalan naskah baru dari staf (status: cancellation_requested)
        $newCancelDocs = Document::with(['createdBy.unit', 'documentType'])
            ->where('id', '>', $this->lastKnownCancelDocId ?? 0)
            ->where('status', 'cancellation_requested')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($newCancelDocs as $doc) {
            $hasChanges = true;
            $this->lastKnownCancelDocId = max($this->lastKnownCancelDocId ?? 0, $doc->id);
            $stafName = $doc->createdBy ? $doc->createdBy->name : 'Staf';
            $unitName = $doc->createdBy?->unit?->name ? " ({$doc->createdBy->unit->name})" : '';
            $docNumber = $doc->number ?: 'Tanpa Nomor';

            $this->dispatch('desktop-notify', [
                'title' => 'Permohonan Batal Nomor Naskah',
                'body' => "{$stafName}{$unitName} mengajukan pembatalan naskah {$docNumber}. Alasan: " . \Illuminate\Support\Str::limit($doc->notes ?: '-', 60),
                'url' => route('registry.cancelled.index', ['tab' => 'requests', 'highlight' => "doc-{$doc->id}"]),
                'type' => 'cancellation_requested',
                'loan_code' => $docNumber,
            ]);
        }

        $latestAnyCancelDoc = Document::where('status', 'cancellation_requested')->latest('id')->first();
        if ($latestAnyCancelDoc && $latestAnyCancelDoc->id > ($this->lastKnownCancelDocId ?? 0)) {
            $this->lastKnownCancelDocId = $latestAnyCancelDoc->id;
        }

        if ($hasChanges) {
            $this->dispatch('loan-status-changed');
        }

        $this->lastCheckedAt = $currentTime;
        $this->updatePendingCount();
    }

    public function render()
    {
        $unreadItems = collect();
        $historyItems = collect();

        if (Auth::check() && Auth::user()->isTapersip()) {
            $readKeys = $this->getDismissedKeys();

            // 1. Antrean scan waiting_for_file terkini
            $scanItems = Document::with(['documentType', 'createdBy.unit'])
                ->where('status', 'waiting_for_file')
                ->whereIn('type', ['outgoing', 'internal'])
                ->latest('created_at')
                ->take(20)
                ->get()
                ->map(function ($doc) use ($readKeys) {
                    $key = "scan_{$doc->id}";

                    return [
                        'key' => $key,
                        'category' => 'scan_queue',
                        'badge' => 'Upload Scan',
                        'is_unread' => !in_array($key, $readKeys),
                        'identifier' => $doc->number ?: 'Tanpa Nomor',
                        'title' => 'Permintaan Upload Scan',
                        'actor' => $doc->createdBy?->name ?? 'Staf',
                        'unit' => $doc->createdBy?->unit?->name ?? ($doc->type === 'outgoing' ? 'Surat Keluar' : 'Surat Khusus'),
                        'extra' => $doc->subject,
                        'timestamp' => $doc->created_at,
                        'target_url' => route('registry.scan-queue', ['highlight' => "doc-{$doc->id}"]),
                    ];
                });

            // 1b. Dokumen yang selesai diarsipkan yang pernah dibaca oleh Tapersip ini (Histori)
            $readScanDocIds = collect($readKeys)
                ->filter(fn ($k) => str_starts_with($k, 'scan_'))
                ->map(fn ($k) => (int) str_replace('scan_', '', $k))
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->toArray();

            $archivedScanItems = !empty($readScanDocIds)
                ? Document::with(['documentType', 'createdBy.unit'])
                    ->whereIn('id', $readScanDocIds)
                    ->where('status', 'archived')
                    ->whereIn('type', ['outgoing', 'internal'])
                    ->latest('updated_at')
                    ->take(20)
                    ->get()
                    ->map(function ($doc) {
                        $key = "scan_{$doc->id}";
                        $targetUrl = $doc->type === 'internal'
                            ? route('registry.special.index', ['highlight' => "doc-{$doc->id}"])
                            : route('registry.outgoing.index', ['highlight' => "doc-{$doc->id}"]);

                        return [
                            'key' => $key,
                            'category' => 'scan_queue',
                            'badge' => 'Selesai Arsip',
                            'is_unread' => false,
                            'identifier' => $doc->number ?: 'Tanpa Nomor',
                            'title' => 'Naskah Selesai Diarsipkan',
                            'actor' => $doc->createdBy?->name ?? 'Staf',
                            'unit' => $doc->createdBy?->unit?->name ?? ($doc->type === 'outgoing' ? 'Surat Keluar' : 'Surat Khusus'),
                            'extra' => $doc->subject,
                            'timestamp' => $doc->updated_at,
                            'target_url' => $targetUrl,
                        ];
                    })
                : collect();

            // 2. Sirkulasi peminjaman (requested / cancelled) terkini
            $loanItems = DocumentLoan::with(['document', 'user.unit'])
                ->whereIn('status', ['requested', 'cancelled'])
                ->latest('updated_at')
                ->take(20)
                ->get()
                ->map(function ($loan) use ($readKeys) {
                    $key = "loan_{$loan->id}_{$loan->status}";
                    $isRequested = $loan->status === 'requested';

                    return [
                        'key' => $key,
                        'category' => 'loan_circulation',
                        'badge' => $isRequested ? 'Pinjam Fisik' : 'Batal Pinjam',
                        'is_unread' => !in_array($key, $readKeys),
                        'identifier' => $loan->loan_code,
                        'title' => $isRequested ? 'Pengajuan Pinjam Fisik' : 'Pembatalan Pinjam Fisik',
                        'actor' => $loan->user?->name ?? 'Staf',
                        'unit' => $loan->user?->unit?->name ?? '-',
                        'extra' => $loan->document?->number ?? 'Tanpa Nomor',
                        'timestamp' => $loan->updated_at,
                        'target_url' => route('borrowings.index', ['tab' => 'circulation', 'highlight' => "loan-{$loan->id}"]),
                    ];
                });

            // 3. Permohonan pembatalan (cancellation_requested) terkini
            $cancelItems = Document::with(['documentType', 'createdBy.unit'])
                ->where('status', 'cancellation_requested')
                ->latest('cancellation_requested_at')
                ->take(20)
                ->get()
                ->map(function ($doc) use ($readKeys) {
                    $key = "cancel_req_{$doc->id}";

                    return [
                        'key' => $key,
                        'category' => 'cancellation',
                        'badge' => 'Batal Nomor',
                        'is_unread' => !in_array($key, $readKeys),
                        'identifier' => $doc->number ?: 'Tanpa Nomor',
                        'title' => 'Permohonan Batal Nomor',
                        'actor' => $doc->createdBy?->name ?? 'Staf',
                        'unit' => $doc->createdBy?->unit?->name ?? ($doc->type === 'outgoing' ? 'Surat Keluar' : 'Surat Khusus'),
                        'extra' => 'Alasan: ' . ($doc->notes ?: '-'),
                        'timestamp' => $doc->cancellation_requested_at ?? $doc->updated_at,
                        'target_url' => route('registry.cancelled.index', ['tab' => 'requests', 'highlight' => "doc-{$doc->id}"]),
                    ];
                });

            // 3b. Dokumen yang resmi dibatalkan (Void) yang pernah dibaca oleh Tapersip ini (Histori)
            $readCancelDocIds = collect($readKeys)
                ->filter(fn ($k) => str_starts_with($k, 'cancel_req_') || str_starts_with($k, 'cancel_void_'))
                ->map(fn ($k) => (int) str_replace(['cancel_req_', 'cancel_void_'], '', $k))
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->toArray();

            $voidedItems = !empty($readCancelDocIds)
                ? Document::with(['documentType', 'createdBy.unit'])
                    ->whereIn('id', $readCancelDocIds)
                    ->where('status', 'cancelled')
                    ->latest('updated_at')
                    ->take(20)
                    ->get()
                    ->map(function ($doc) {
                        $key = "cancel_req_{$doc->id}";

                        return [
                            'key' => $key,
                            'category' => 'cancellation',
                            'badge' => 'Batal (Void)',
                            'is_unread' => false,
                            'identifier' => $doc->number ?: 'Tanpa Nomor',
                            'title' => 'Nomor Naskah Dibatalkan',
                            'actor' => $doc->createdBy?->name ?? 'Staf',
                            'unit' => $doc->createdBy?->unit?->name ?? ($doc->type === 'outgoing' ? 'Surat Keluar' : 'Surat Khusus'),
                            'extra' => 'Alasan: ' . ($doc->notes ?: '-'),
                            'timestamp' => $doc->updated_at,
                            'target_url' => route('registry.cancelled.index', ['tab' => 'register', 'highlight' => "doc-{$doc->id}"]),
                        ];
                    })
                : collect();

            $allItems = $scanItems->concat($loanItems)->concat($cancelItems)->concat($archivedScanItems)->concat($voidedItems);

            // Pisahkan unread dan history
            $unreadItems = $allItems->filter(fn ($item) => $item['is_unread'])
                ->sortByDesc('timestamp')
                ->values();

            $historyItems = $allItems->filter(fn ($item) => !$item['is_unread'])
                ->sortByDesc('timestamp')
                ->take(30)
                ->values();
        }

        return view('livewire.components.tapersip-notification-listener', [
            'feedItems' => $unreadItems, // backward compatibility
            'unreadItems' => $unreadItems,
            'historyItems' => $historyItems,
            'historyCount' => $this->historyCount,
        ]);
    }
}
