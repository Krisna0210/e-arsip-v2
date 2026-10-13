<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Unit;
use App\Models\Position;

class OrganizationIndex extends Component
{
    use WithPagination;

    public $activeTab = 'unit'; // 'unit' or 'position'

    // Unit Properties
    public $unitId;
    public $unitCode;
    public $unitName;
    public $parentUnitId;
    public $unitStatus = 'active';

    // Position Properties
    public $positionId;
    public $posName;
    public $posLevel;
    public $posAuthority;

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // --- Unit Methods ---

    public function createUnit()
    {
        $this->reset(['unitId', 'unitCode', 'unitName', 'parentUnitId']);
        $this->unitStatus = 'active';
        $this->dispatch('open-modal', id: 'slide-unit');
    }

    public function editUnit($id)
    {
        $unit = Unit::findOrFail($id);
        $this->unitId = $unit->id;
        $this->unitCode = $unit->code;
        $this->unitName = $unit->name;
        $this->parentUnitId = $unit->parent_id;
        $this->unitStatus = $unit->status;

        $this->dispatch('open-modal', id: 'slide-unit');
    }

    public function saveUnit()
    {
        $this->validate([
            'unitCode' => 'required|string|max:255|unique:units,code,' . $this->unitId,
            'unitName' => 'required|string|max:255',
            'parentUnitId' => 'nullable|exists:units,id',
            'unitStatus' => 'required|string|in:active,inactive',
        ]);

        Unit::updateOrCreate(
            ['id' => $this->unitId],
            [
                'code' => $this->unitCode,
                'name' => $this->unitName,
                'parent_id' => $this->parentUnitId ?: null,
                'status' => $this->unitStatus,
            ]
        );

        $this->dispatch('close-modal', id: 'slide-unit');
        $this->reset(['unitId', 'unitCode', 'unitName', 'parentUnitId']);
        $this->unitStatus = 'active';
    }

    public function deleteUnit($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();
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

        Position::updateOrCreate(
            ['id' => $this->positionId],
            [
                'name' => $this->posName,
                'level' => $this->posLevel,
                'authority_config' => $this->posAuthority,
            ]
        );

        $this->dispatch('close-modal', id: 'slide-position');
        $this->reset(['positionId', 'posName', 'posLevel', 'posAuthority']);
    }

    public function deletePosition($id)
    {
        $position = Position::findOrFail($id);
        $position->delete();
    }

    public function render()
    {
        $units = Unit::with('parent')->latest()->paginate(10, ['*'], 'unitPage');
        $positions = Position::latest()->paginate(10, ['*'], 'positionPage');
        $allUnits = Unit::all();

        return view('livewire.settings.organization-index', [
            'units' => $units,
            'positions' => $positions,
            'allUnits' => $allUnits,
        ]);
    }
}
