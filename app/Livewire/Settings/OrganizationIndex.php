<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Unit;
use App\Models\Position;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class OrganizationIndex extends Component
{
    use WithPagination;

    public $activeTab = 'unit'; // 'unit' or 'position'

    // Unit Filter & Search
    public $unitTypeFilter = 'all'; // 'all', 'division', 'department'
    public $searchUnit = '';

    // Unit Form Properties
    public $unitId;
    public $unitCode;
    public $unitName;
    public $unitType = 'division'; // 'division' or 'department'
    public $parentUnitId;
    public $unitStatus = 'active';

    // Position Properties
    public $searchPosition = '';
    public $positionId;
    public $posName;
    public $posLevel;
    public $posAuthority;

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function setUnitTypeFilter($filter)
    {
        $this->unitTypeFilter = $filter;
        $this->resetPage('unitPage');
    }

    // --- Unit Methods ---

    public function createUnit()
    {
        $this->reset(['unitId', 'unitCode', 'unitName', 'parentUnitId']);
        $this->unitType = 'division';
        $this->unitStatus = 'active';
        $this->dispatch('open-modal', id: 'slide-unit');
    }

    public function editUnit($id)
    {
        $unit = Unit::findOrFail($id);
        $this->unitId = $unit->id;
        $this->unitCode = $unit->code;
        $this->unitName = $unit->name;
        $this->unitType = $unit->type ?? 'division';
        $this->parentUnitId = $unit->parent_id;
        $this->unitStatus = $unit->status;

        $this->dispatch('open-modal', id: 'slide-unit');
    }

    public function saveUnit()
    {
        $rules = [
            'unitCode' => 'required|string|max:255|unique:units,code,' . $this->unitId,
            'unitName' => 'required|string|max:255',
            'unitType' => 'required|in:division,department',
            'unitStatus' => 'required|string|in:active,inactive',
        ];

        if ($this->unitType === 'department') {
            $rules['parentUnitId'] = 'required|exists:units,id';
        } else {
            $rules['parentUnitId'] = 'nullable';
        }

        $this->validate($rules, [
            'parentUnitId.required' => 'Divisi Induk wajib dipilih untuk unit bertipe Departemen.',
        ]);

        $isNew = !$this->unitId;
        $unit = Unit::updateOrCreate(
            ['id' => $this->unitId],
            [
                'code' => $this->unitCode,
                'name' => $this->unitName,
                'type' => $this->unitType,
                'parent_id' => $this->unitType === 'department' ? $this->parentUnitId : null,
                'status' => $this->unitStatus,
            ]
        );

        $typeLabel = $unit->type === 'division' ? 'Divisi' : 'Departemen';
        $action = $isNew ? "Pembuatan Unit Kerja: {$unit->name} ({$typeLabel})" : "Pembaruan Unit Kerja: {$unit->name} ({$typeLabel})";

        AuditLog::record(
            action: $action,
            documentId: null,
            metadata: [
                'unit_id' => $unit->id,
                'code' => $unit->code,
                'type' => $unit->type,
                'parent_id' => $unit->parent_id,
                'status' => $unit->status,
            ]
        );

        $msg = $isNew ? "Unit kerja '{$unit->name}' berhasil ditambahkan." : "Data unit kerja '{$unit->name}' berhasil diperbarui.";
        $this->dispatch('close-modal', id: 'slide-unit');
        $this->dispatch('notify', ['message' => $msg, 'type' => 'success']);
        $this->reset(['unitId', 'unitCode', 'unitName', 'parentUnitId']);
        $this->unitType = 'division';
        $this->unitStatus = 'active';
    }

    public function deleteUnit($id)
    {
        $unit = Unit::withCount(['users', 'children', 'documents'])->findOrFail($id);

        if ($unit->users_count > 0) {
            $this->dispatch('notify', [
                'message' => "Unit kerja '{$unit->name}' tidak dapat dihapus karena masih memiliki {$unit->users_count} pengguna aktif.",
                'type' => 'error'
            ]);
            return;
        }

        if ($unit->children_count > 0) {
            $this->dispatch('notify', [
                'message' => "Divisi '{$unit->name}' tidak dapat dihapus karena masih membawahi {$unit->children_count} departemen.",
                'type' => 'error'
            ]);
            return;
        }

        if ($unit->documents_count > 0) {
            $this->dispatch('notify', [
                'message' => "Unit kerja '{$unit->name}' tidak dapat dihapus karena masih terkait dengan {$unit->documents_count} dokumen arsip.",
                'type' => 'error'
            ]);
            return;
        }

        $unitName = $unit->name;
        $unitCode = $unit->code;
        $unit->delete();

        AuditLog::record(
            action: "Penghapusan Unit Kerja: {$unitName}",
            documentId: null,
            metadata: [
                'deleted_unit_id' => $id,
                'code' => $unitCode,
            ]
        );

        $this->dispatch('notify', ['message' => "Unit kerja '{$unitName}' berhasil dihapus.", 'type' => 'success']);
    }

    // --- Position Methods ---

    public function createPosition()
    {
        $this->reset(['positionId', 'posName', 'posLevel', 'posAuthority']);
        $this->dispatch('open-modal', id: 'slide-position');
    }

    public function editPosition($id)
    {
        $position = Position::findOrFail($id);
        $this->positionId = $position->id;
        $this->posName = $position->name;
        $this->posLevel = $position->level;
        $this->posAuthority = $position->authority_config;

        $this->dispatch('open-modal', id: 'slide-position');
    }

    public function savePosition()
    {
        $this->validate([
            'posName' => 'required|string|max:255',
            'posLevel' => 'required|integer',
            'posAuthority' => 'nullable|string',
        ]);

        $isNew = !$this->positionId;
        $position = Position::updateOrCreate(
            ['id' => $this->positionId],
            [
                'name' => $this->posName,
                'level' => $this->posLevel,
                'authority_config' => $this->posAuthority,
            ]
        );

        $action = $isNew ? "Pembuatan Jabatan Baru: {$position->name}" : "Pembaruan Jabatan: {$position->name}";
        AuditLog::record(
            action: $action,
            documentId: null,
            metadata: [
                'position_id' => $position->id,
                'level' => $position->level,
                'authority' => $position->authority_config,
            ]
        );

        $msg = $isNew ? "Jabatan '{$position->name}' berhasil ditambahkan." : "Data jabatan '{$position->name}' berhasil diperbarui.";
        $this->dispatch('close-modal', id: 'slide-position');
        $this->dispatch('notify', ['message' => $msg, 'type' => 'success']);
        $this->reset(['positionId', 'posName', 'posLevel', 'posAuthority']);
    }

    public function deletePosition($id)
    {
        $position = Position::withCount('users')->findOrFail($id);

        if ($position->users_count > 0) {
            $this->dispatch('notify', [
                'message' => "Jabatan '{$position->name}' tidak dapat dihapus karena masih digunakan oleh {$position->users_count} pengguna aktif.",
                'type' => 'error'
            ]);
            return;
        }

        $posName = $position->name;
        $position->delete();

        AuditLog::record(
            action: "Penghapusan Jabatan: {$posName}",
            documentId: null,
            metadata: [
                'deleted_position_id' => $id,
                'name' => $posName,
            ]
        );

        $this->dispatch('notify', ['message' => "Jabatan '{$posName}' berhasil dihapus.", 'type' => 'success']);
    }

    public function render()
    {
        $unitsQuery = Unit::with('parent');

        if ($this->unitTypeFilter === 'division') {
            $unitsQuery->where('type', 'division');
        } elseif ($this->unitTypeFilter === 'department') {
            $unitsQuery->where('type', 'department');
        }

        if (!empty(trim($this->searchUnit))) {
            $term = trim($this->searchUnit);
            $unitsQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            });
        }

        $units = $unitsQuery->latest()->paginate(10, ['*'], 'unitPage');

        $positionsQuery = Position::query();
        if (!empty(trim($this->searchPosition))) {
            $term = trim($this->searchPosition);
            $positionsQuery->where('name', 'like', "%{$term}%");
        }
        $positions = $positionsQuery->latest()->paginate(10, ['*'], 'positionPage');

        $divisionOptions = Unit::where('type', 'division')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $allUnits = Unit::all();

        return view('livewire.settings.organization-index', [
            'units' => $units,
            'positions' => $positions,
            'divisionOptions' => $divisionOptions,
            'allUnits' => $allUnits,
        ]);
    }
}
