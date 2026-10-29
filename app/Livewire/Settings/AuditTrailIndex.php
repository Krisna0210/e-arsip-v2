<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use App\Models\Classification;
use App\Models\Unit;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AuditTrailIndex extends Component
{
    use WithPagination;

    public function boot()
    {
        if (!Auth::user()?->isSuperAdmin() && !Auth::user()?->hasModuleAccess('audit')) {
            abort(403, 'Akses ditolak.');
        }
    }

    public $search = '';
    public $filterAction = '';
    public $filterDateStart = '';
    public $filterDateEnd = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterAction' => ['except' => ''],
        'filterDateStart' => ['except' => ''],
        'filterDateEnd' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterAction()
    {
        $this->resetPage();
    }

    public function updatingFilterDateStart()
    {
        $this->resetPage();
    }

    public function updatingFilterDateEnd()
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = AuditLog::with(['user', 'document.documentType'])
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('action', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function (Builder $userQuery) {
                          $userQuery->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->filterAction, function (Builder $query) {
                $query->where('action', $this->filterAction);
            })
            ->when($this->filterDateStart, function (Builder $query) {
                $query->whereDate('created_at', '>=', $this->filterDateStart);
            })
            ->when($this->filterDateEnd, function (Builder $query) {
                $query->whereDate('created_at', '<=', $this->filterDateEnd);
            })
            ->latest('created_at')
            ->paginate(15);

        // Get unique actions for the filter dropdown
        $actions = AuditLog::select('action')->distinct()->pluck('action');

        $classificationsMap = Classification::pluck('code', 'id')->toArray();
        $unitsMap = Unit::all()->mapWithKeys(fn($u) => [$u->id => $u->name . ($u->code ? " ({$u->code})" : '')])->toArray();

        return view('livewire.settings.audit-trail-index', [
            'logs' => $logs,
            'actions' => $actions,
            'classificationsMap' => $classificationsMap,
            'unitsMap' => $unitsMap,
        ]);
    }
}
