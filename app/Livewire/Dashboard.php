<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public $monitoringFilter = 'all'; // 'all', 'waiting', 'archived'
    public $monitoringSearch = '';

    public function setMonitoringFilter($filter)
    {
        $this->monitoringFilter = $filter;
        $this->resetPage('monitoringPage');
    }

    public function updatedMonitoringSearch()
    {
        $this->resetPage('monitoringPage');
    }

    public function render()
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        $countIncoming = Document::where('type', 'incoming')->count();
        $countOutgoing = Document::where('type', 'outgoing')->count();
        $countInternal = Document::where('type', 'internal')->count();
        $countRetention = Document::whereNotNull('retention_end_date')->where('retention_end_date', '<=', now()->addDays(30))->count();

        // Hitung antrean yang menunggu upload (Personal per akun staf jika bukan Superadmin)
        $countWaitingQuery = Document::where('status', 'waiting_for_file');
        if (!$isSuperAdmin) {
            $countWaitingQuery->where('created_by', Auth::id());
        }
        $countWaiting = $countWaitingQuery->count();

        // Kueri Monitoring Arsip Dokumen:
        $monitoringQuery = Document::with(['unit', 'documentType', 'classification', 'createdBy']);

        // Filter status monitoring
        if ($this->monitoringFilter === 'waiting') {
            $monitoringQuery->where('status', 'waiting_for_file');
        } elseif ($this->monitoringFilter === 'archived') {
            $monitoringQuery->where('status', 'archived');
        } else {
            $monitoringQuery->where(function ($q) {
                $q->where('status', 'waiting_for_file')
                  ->orWhere(function ($qArchived) {
                      $qArchived->where('status', 'archived')
                                ->where('updated_at', '>=', now()->subDays(30));
                  });
            });
        }

        // Filter pencarian nomor, subjek, atau penerima
        if (!empty(trim($this->monitoringSearch))) {
            $term = trim($this->monitoringSearch);
            $monitoringQuery->where(function ($q) use ($term) {
                $q->where('number', 'like', "%{$term}%")
                  ->orWhere('subject', 'like', "%{$term}%")
                  ->orWhere('recipient_name', 'like', "%{$term}%");
            });
        }

        if (!$isSuperAdmin) {
            $monitoringQuery->where('created_by', Auth::id());
        }

        // Paginasi 5 data per halaman agar tabel tetap rapi dan tidak memanjang ke bawah
        $monitoringDocuments = $monitoringQuery
            ->orderBy('created_at', 'desc')
            ->paginate(5, ['*'], 'monitoringPage');

        $canViewAllActivities = $user ? $user->canViewAllActivities() : false;

        $activitiesQuery = AuditLog::with(['user', 'document']);
        if (!$canViewAllActivities) {
            $activitiesQuery->where('user_id', Auth::id());
        }

        $recentActivities = $activitiesQuery
            ->latest('created_at')
            ->take(5)
            ->get();

        // Evaluasi izin visibilitas kartu statistik via Role Matrix (Superadmin bypass otomatis)
        $canViewIncoming = $user ? $user->hasModuleAccess('card_incoming') : false;
        $canViewOutgoing = $user ? $user->hasModuleAccess('card_outgoing') : false;
        $canViewInternal = $user ? $user->hasModuleAccess('card_internal') : false;
        $canViewWaiting = $user ? $user->hasModuleAccess('card_waiting') : false;
        $canViewRetention = $user ? $user->hasModuleAccess('card_retention') : false;

        $visibleCardsCount = (int)$canViewIncoming + (int)$canViewOutgoing + (int)$canViewInternal + (int)$canViewWaiting + (int)$canViewRetention;

        $gridColsClass = match($visibleCardsCount) {
            1 => 'grid-cols-1 max-w-sm',
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
            default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-5',
        };

        return view('livewire.dashboard', [
            'countIncoming' => $countIncoming,
            'countOutgoing' => $countOutgoing,
            'countInternal' => $countInternal,
            'countWaiting' => $countWaiting,
            'countRetention' => $countRetention,
            'monitoringDocuments' => $monitoringDocuments,
            'recentActivities' => $recentActivities,
            'isSuperAdmin' => $isSuperAdmin,
            'canViewAllActivities' => $canViewAllActivities,
            'canViewIncoming' => $canViewIncoming,
            'canViewOutgoing' => $canViewOutgoing,
            'canViewInternal' => $canViewInternal,
            'canViewWaiting' => $canViewWaiting,
            'canViewRetention' => $canViewRetention,
            'visibleCardsCount' => $visibleCardsCount,
            'gridColsClass' => $gridColsClass,
        ]);
    }
}
