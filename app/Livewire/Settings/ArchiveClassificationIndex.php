<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Classification;
use App\Models\DocumentType;

class ArchiveClassificationIndex extends Component
{
    use WithPagination;

    public $activeTab = 'classification';

    // Classification Properties
    public $classId;
    public $classCode;
    public $classSubject;
    public $parentClassId;
    public $activeYears = 0;
    public $inactiveYears = 0;
    public $outcome = 'musnah';

    // Document Type Properties
    public $typeId;
    public $typeName;
    public $typeSifat = 'biasa';
    public $typeNumbering;
    public $typeStartingSequence = 0;
    public $typeStartingYear;

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // --- Classification Methods ---

    public function createClassification()
    {
        $this->resetClassificationForm();
        $this->dispatch('open-modal', id: 'slide-classification');
    }

    public function editClassification($id)
    {
        $this->resetClassificationForm();
        $classification = Classification::findOrFail($id);
        $this->classId = $classification->id;
        $this->classCode = $classification->code;
        $this->classSubject = $classification->subject;
        $this->parentClassId = $classification->parent_id;
        $this->activeYears = $classification->active_years ?? 0;
        $this->inactiveYears = $classification->inactive_years ?? 0;
        $this->outcome = $classification->outcome ?? 'musnah';
        $this->dispatch('open-modal', id: 'slide-classification');
    }

    public function saveClassification()
    {
        $this->validate([
            'classCode' => 'required|string|max:255|unique:classifications,code,' . $this->classId,
            'classSubject' => 'required|string|max:255',
            'parentClassId' => 'nullable|exists:classifications,id',
            'activeYears' => 'required|integer|min:0',
            'inactiveYears' => 'required|integer|min:0',
            'outcome' => 'required|in:musnah,permanen,dinilai_kembali',
        ]);

        Classification::updateOrCreate(
            ['id' => $this->classId],
            [
                'code' => $this->classCode,
                'subject' => $this->classSubject,
                'parent_id' => $this->parentClassId ?: null,
                'active_years' => $this->activeYears,
                'inactive_years' => $this->inactiveYears,
                'outcome' => $this->outcome,
            ]
        );

        $this->dispatch('close-modal', id: 'slide-classification');
        $this->resetClassificationForm();
    }

    public function deleteClassification($id)
    {
        Classification::findOrFail($id)->delete();
    }

    private function resetClassificationForm()
    {
        $this->reset(['classId', 'classCode', 'classSubject', 'parentClassId', 'activeYears', 'inactiveYears', 'outcome']);
        $this->outcome = 'musnah';
        $this->activeYears = 0;
        $this->inactiveYears = 0;
    }

    // --- Document Type Methods ---

    public function createDocType()
    {
        $this->resetDocTypeForm();
        $this->typeStartingSequence = 0;
        $this->typeStartingYear = (int) date('Y');
        $this->dispatch('open-modal', id: 'slide-doctype');
    }

    public function editDocType($id)
    {
        $this->resetDocTypeForm();
        $docType = DocumentType::findOrFail($id);
        $this->typeId = $docType->id;
        $this->typeName = $docType->name;
        $this->typeSifat = $docType->default_sifat;
        $this->typeNumbering = $docType->numbering_format;
        $this->typeStartingSequence = $docType->starting_sequence ?? 0;
        $this->typeStartingYear = $docType->starting_sequence_year ?? (int) date('Y');
        $this->dispatch('open-modal', id: 'slide-doctype');
    }

    public function saveDocType()
    {
        $this->validate([
            'typeName' => 'required|string|max:255',
            'typeSifat' => 'required|in:biasa,penting,rahasia',
            'typeNumbering' => 'nullable|string|max:255',
            'typeStartingSequence' => 'nullable|integer|min:0',
            'typeStartingYear' => 'nullable|integer|min:2000|max:2100',
        ]);

        DocumentType::updateOrCreate(
            ['id' => $this->typeId],
            [
                'name' => $this->typeName,
                'default_sifat' => $this->typeSifat,
                'numbering_format' => $this->typeNumbering,
                'starting_sequence' => $this->typeStartingSequence !== null && $this->typeStartingSequence !== '' ? (int) $this->typeStartingSequence : 0,
                'starting_sequence_year' => $this->typeStartingYear ?: (int) date('Y'),
            ]
        );

        $this->dispatch('close-modal', id: 'slide-doctype');
        $this->resetDocTypeForm();
    }

    public function deleteDocType($id)
    {
        DocumentType::findOrFail($id)->delete();
    }

    private function resetDocTypeForm()
    {
        $this->reset(['typeId', 'typeName', 'typeSifat', 'typeNumbering', 'typeStartingSequence', 'typeStartingYear']);
        $this->typeSifat = 'biasa';
        $this->typeStartingSequence = 0;
        $this->typeStartingYear = (int) date('Y');
    }

    public function render()
    {
        $classifications = Classification::with('parent')->paginate(10, ['*'], 'classificationsPage');
        $allClassifications = Classification::whereNull('parent_id')->get();
        $documentTypes = DocumentType::paginate(10, ['*'], 'documentTypesPage');

        return view('livewire.settings.archive-classification-index', [
            'classifications' => $classifications,
            'allClassifications' => $allClassifications,
            'documentTypes' => $documentTypes,
        ]);
    }
}


