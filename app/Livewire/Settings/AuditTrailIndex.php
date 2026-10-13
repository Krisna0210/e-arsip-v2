<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

class AuditTrailIndex extends Component
{
    use WithPagination;

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
        $logs = AuditLog::with(['user', 'document'])
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

        return view('livewire.settings.audit-trail-index', [
            'logs' => $logs,
            'actions' => $actions,
        ]);
    }
}
