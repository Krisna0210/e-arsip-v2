<?php

namespace App\Livewire\Registry\Outgoing;

use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public $number;
    public $document_date;
    public $unit_id; // Sender unit
    public $recipient_name;
    public $subject;
    public $classification_id;
    public $document_type_id;
    public $folder_id;
    public $sifat = 'biasa';
    public $signer_type = 'pimpinan_unit'; // 'pimpinan_unit' or 'direktur'
    public $file;
    public $showSuccessModal = false;
    public $showArchiveSuccessModal = false;

    // Data ringkasan setelah berhasil diarsipkan langsung
    public $savedDocumentNumber = '';
    public $savedDocumentSubject = '';
    public $savedDocumentRecipient = '';
    public $savedDocumentUnitName = '';
    public $savedDocumentFolderName = '';
    public $savedDocumentSignerLabel = '';

    public $classifications;
    public $documentTypes;
    public $units;
    public $folders;
    public function mount()
    {
        $this->document_date = date('Y-m-d');
        $this->classifications = Classification::whereNotNull('parent_id')->get();
        if ($this->classifications->isEmpty()) {
            $this->classifications = Classification::all();
        }

        $outgoingType = \App\Models\DocumentType::where('name', 'like', '%Surat Keluar%')->first();
        if (! $outgoingType) {
            $outgoingType = \App\Models\DocumentType::firstOrCreate(
                ['name' => 'Surat Keluar'],
                [
                    'code' => 'SK',
                    'default_sifat' => 'biasa',
                    'numbering_format' => '{NO}/{DIR}{UNIT}-{CLASS}/{MM}/{YYYY}',
                    'starting_sequence' => 0,
                    'starting_sequence_dir' => 0,
                    'starting_sequence_year' => (int) date('Y'),
                ]
            );
        }
        $this->document_type_id = $outgoingType?->id;
        $this->documentTypes = \App\Models\DocumentType::all();
        $this->units = Unit::divisions()->where('status', 'active')->orderBy('name')->get();
        $this->folders = \App\Models\Folder::forType('outgoing')->get();

        // Otomatisasi default divisi dari akun staf yang sedang login
        $userDivision = Auth::user()?->getDivision();
        if ($userDivision && $this->units->contains('id', $userDivision->id)) {
            $this->unit_id = $userDivision->id;
        }
    }

    protected function baseRules($isStrict = false)
    {
        return [
            'document_date' => 'required|date|before_or_equal:today',
            'unit_id' => 'required|exists:units,id',
            'signer_type' => 'required|in:pimpinan_unit,direktur',
            'recipient_name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'classification_id' => 'required|exists:classifications,id',
            'folder_id' => 'nullable|exists:folders,id',
            'sifat' => 'required|in:biasa,penting,rahasia',
            'file' => 'nullable|file|mimes:pdf|max:10240',
        ];
    }

    public function getCanArchiveProperty(): bool
    {
        return Auth::user()->hasDocumentPermission('archive', $this->sifat ?? 'biasa');
    }

    protected function generateNumber(?int &$assignedSequence = null): string
    {
        $year = $this->document_date ? date('Y', strtotime($this->document_date)) : date('Y');
        
        $docTypeRecord = \App\Models\DocumentType::find($this->document_type_id);
        $format = $docTypeRecord && $docTypeRecord->numbering_format ? $docTypeRecord->numbering_format : '{NO}/{DIR}{UNIT}-{CLASS}/{MM}/{YYYY}';
        
        // 1. Ambil nomor urut tertinggi di database tahun ini SESUAI PEJABAT PENANDATANGAN (Opsi B: Terpisah)
        $maxInDb = \App\Models\Document::where('document_type_id', $this->document_type_id)
            ->where('type', 'outgoing')
            ->where('signer_type', $this->signer_type)
            ->whereYear('document_date', $year)
            ->where('sequence_number', '<=', 99999)
            ->max('sequence_number') ?? 0;

        // 2. Ambil sequence awal manual dari master data yang sesuai pejabat penandatangan
        $startingSequence = 0;
        if ($docTypeRecord && $docTypeRecord->starting_sequence_year == $year) {
            $startingSequence = ($this->signer_type === 'direktur')
                ? (int) ($docTypeRecord->starting_sequence_dir ?? 0)
                : (int) ($docTypeRecord->starting_sequence ?? 0);
        }

        // 3. Ambil angka terbesar di antara keduanya + 1
        $nextSequence = max($maxInDb, $startingSequence) + 1;
        $assignedSequence = $nextSequence;

        // 4. Format 2 DIGIT (01, 02, dst.) sesuai kebutuhan baku kantor
        $sequenceNo = str_pad($nextSequence, 2, '0', STR_PAD_LEFT);

        $classification = \App\Models\Classification::find($this->classification_id);
        $classCode = $classification ? $classification->code : 'UM';
        $unit = \App\Models\Unit::find($this->unit_id);
        $unitCode = $unit ? $unit->code : 'ORG';
        $month = $this->document_date ? date('m', strtotime($this->document_date)) : date('m');
        
        $dirPrefix = ($this->signer_type === 'direktur') ? 'DIR-' : '';
        
        // Cek apakah format memiliki placeholder {DIR}
        if (str_contains($format, '{DIR}')) {
            $format = str_replace('{DIR}', $dirPrefix, $format);
            $finalUnitCode = $unitCode;
        } else {
            // Jika format di master data belum memuat {DIR}, sematkan prefix DIR- pada unit jika penandatangan adalah Direktur
            $finalUnitCode = ($this->signer_type === 'direktur') ? ('DIR-' . $unitCode) : $unitCode;
        }

        $kosur = $docTypeRecord?->code ?: 'SK';

        // Resolusi Divisi otomatis dari akun login (dengan fallback ke unit formulir)
        $divCode = Auth::user()?->getDivisionCode();
        if (empty($divCode)) {
            $divCode = $unit ? $unit->getDivisionCode() : 'DIV';
        }

        return str_replace(
            ['{NO}', '{KOSUR}', '{kosur}', '{DIV}', '{div}', '{CLASS}', '{kodeklasi}', '{UNIT}', '{YYYY}', '{MM}'],
            [$sequenceNo, $kosur, $kosur, $divCode, $divCode, $classCode, $classCode, $finalUnitCode, $year, $month],
            $format
        );
    }

    public function bookNumber()
    {
        $this->validate($this->baseRules(false));

        $assignedSequence = null;
        $this->number = $this->generateNumber($assignedSequence);
        $this->file = null; // Do not attach file for booking

        $document = $this->createDocument('waiting_for_file', $assignedSequence);

        \App\Models\AuditLog::record(
            action: 'Booking Nomor Surat Keluar: ' . $this->number,
            documentId: $document->id,
            metadata: [
                'subject' => $this->subject,
                'type' => 'outgoing',
                'recipient' => $this->recipient_name,
            ]
        );

        $this->showSuccessModal = true;
    }

    public function saveAndArchive()
    {
        // Server-side security check: must have archive permission for this sifat
        if (!Auth::user()->hasDocumentPermission('archive', $this->sifat ?? 'biasa')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengarsipkan dokumen secara langsung.');
        }

        $rules = $this->baseRules(false);
        $rules['file'] = 'required|file|mimes:pdf|max:10240';
        $this->validate($rules);

        $assignedSequence = null;

        // Jika Tapersip mengisi nomor manual (digitalisasi dokumen lama)
        if (!empty(trim((string)$this->number))) {
            $this->number = trim((string)$this->number);

            // Ekstrak angka urut dari teks nomor fisik jika diawali atau mengandung angka wajar (<= 99999)
            if (preg_match('/^\D*(\d+)/', $this->number, $matches)) {
                $parsedSequence = (int) $matches[1];
                if ($parsedSequence <= 99999) {
                    $assignedSequence = $parsedSequence;
                }
            }
        } else {
            // Jika dikosongkan, terbitkan nomor resmi otomatis
            $this->number = $this->generateNumber($assignedSequence);
        }

        $document = $this->createDocument('archived', $assignedSequence);
        $this->handleFileAndVersion($document);

        \App\Models\AuditLog::record(
            action: 'Registrasi & Arsip Surat Keluar: ' . $this->number,
            documentId: $document->id,
            metadata: [
                'subject' => $this->subject,
                'type' => 'outgoing',
                'recipient' => $this->recipient_name,
            ]
        );

        $document->load(['unit', 'folder']);
        $this->savedDocumentNumber = $document->number ?: 'Tanpa Nomor';
        $this->savedDocumentSubject = $document->subject;
        $this->savedDocumentRecipient = $document->recipient_name;
        $this->savedDocumentUnitName = $document->unit ? $document->unit->name : '-';
        $this->savedDocumentFolderName = $document->folder ? $document->folder->name : 'Tanpa Loker / Root';
        $this->savedDocumentSignerLabel = $document->signer_type === 'direktur' ? 'Direktur' : 'Pimpinan Unit Kerja';
        $this->showArchiveSuccessModal = true;
    }

    public function resetFormAndInputAgain()
    {
        $this->reset([
            'number',
            'recipient_name',
            'subject',
            'classification_id',
            'folder_id',
            'file',
            'showSuccessModal',
            'showArchiveSuccessModal',
            'savedDocumentNumber',
            'savedDocumentSubject',
            'savedDocumentRecipient',
            'savedDocumentUnitName',
            'savedDocumentFolderName',
            'savedDocumentSignerLabel',
        ]);

        $this->document_date = date('Y-m-d');
        $this->sifat = 'biasa';
        $this->signer_type = 'pimpinan_unit';

        $userDivision = Auth::user()?->getDivision();
        if ($userDivision && $this->units->contains('id', $userDivision->id)) {
            $this->unit_id = $userDivision->id;
        }
    }

    public function redirectToIndex()
    {
        return redirect()->route('registry.outgoing.index');
    }

    public function save()
    {
        return $this->bookNumber();
    }

    public function finishAndRedirect()
    {
        return redirect()->route('dashboard');
    }

    protected function createDocument($status, ?int $sequenceNumber = null)
    {
        $classification = Classification::find($this->classification_id);
        $retentionStartDate = null;
        $retentionEndDate = null;
        if ($classification && $classification->active_years > 0) {
            $retentionStartDate = $this->document_date ?: date('Y-m-d');
            $retentionEndDate = date('Y-m-d', strtotime("+{$classification->active_years} years", strtotime($retentionStartDate)));
        }

        return Document::create([
            'document_type_id' => $this->document_type_id,
            'type' => 'outgoing',
            'number' => $this->number,
            'sequence_number' => $sequenceNumber,
            'document_date' => $this->document_date,
            'unit_id' => $this->unit_id,
            'signer_type' => $this->signer_type,
            'recipient_name' => $this->recipient_name,
            'subject' => $this->subject,
            'classification_id' => $this->classification_id,
            'folder_id' => $this->folder_id,
            'sifat' => $this->sifat,
            'status' => $status,
            'retention_start_date' => $retentionStartDate,
            'retention_end_date' => $retentionEndDate,
            'created_by' => Auth::id(),
        ]);
    }

    protected function handleFileAndVersion(Document $document)
    {
        if ($this->file) {
            $document->load('folder.parent');
            $directory = $document->getStorageDirectoryPath();
            $fileName = $document->generateArchiveFileName('pdf', '1.0');
            $path = $this->file->storeAs($directory, $fileName, 'nas');

            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => 1,
                'file_path' => $path,
                'created_by' => Auth::id(),
                'status' => 'active',
            ]);

            $document->update([
                'current_version_id' => $version->id,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.registry.outgoing.create');
    }
}













