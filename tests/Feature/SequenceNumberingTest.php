<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Livewire\Registry\Outgoing\Create as OutgoingCreate;
use App\Livewire\Registry\Internal\Create as InternalCreate;
use App\Livewire\Settings\ArchiveClassificationIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SequenceNumberingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_set_starting_sequence_in_master_data()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::first();

        Livewire::actingAs($superadmin)
            ->test(ArchiveClassificationIndex::class)
            ->call('editDocType', $docType->id)
            ->set('typeStartingSequence', 85)
            ->set('typeStartingYear', (int) date('Y'))
            ->call('saveDocType');

        $docType->refresh();
        $this->assertEquals(85, $docType->starting_sequence);
        $this->assertEquals((int) date('Y'), $docType->starting_sequence_year);
    }

    public function test_booking_number_starts_from_starting_sequence_plus_one()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::first();
        $docType->update([
            'starting_sequence' => 85,
            'starting_sequence_year' => (int) date('Y'),
            'numbering_format' => '{NO}/SK/{UNIT}/{YYYY}',
        ]);

        $unit = Unit::first();
        $classification = Classification::whereNotNull('parent_id')->first() ?? Classification::first();

        // Staf klik ambil nomor surat
        $component = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unit->id)
            ->set('recipient_name', 'PT Rekanan Utama')
            ->set('subject', 'Uji Coba Penomoran Otomatis Berangkat dari Sequence Awal')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber');

        $component->assertSet('number', '086/SK/' . $unit->code . '/' . date('Y'));

        $docInDb = Document::where('type', 'outgoing')->latest('id')->first();
        $this->assertNotNull($docInDb);
        $this->assertEquals(86, $docInDb->sequence_number);
        $this->assertEquals('086/SK/' . $unit->code . '/' . date('Y'), $docInDb->number);

        // Ambil nomor kedua -> harus 087
        $component2 = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unit->id)
            ->set('recipient_name', 'PT Mitra Kedua')
            ->set('subject', 'Surat Kedua Otomatis')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber');

        $component2->assertSet('number', '087/SK/' . $unit->code . '/' . date('Y'));
    }

    public function test_tapersip_can_archive_directly_with_manual_existing_number()
    {
        Storage::fake('nas');
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::first();
        $unit = Unit::first();
        $classification = Classification::whereNotNull('parent_id')->first() ?? Classification::first();

        $manualNumber = '085/SK-DIR/VIII/2026';
        $file = UploadedFile::fake()->create('surat_lama.pdf', 100, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('number', $manualNumber)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unit->id)
            ->set('recipient_name', 'Kementerian Keuangan')
            ->set('subject', 'Arsip Surat Lama Fisik Langsung')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->set('file', $file)
            ->call('saveAndArchive');

        $docInDb = Document::where('number', $manualNumber)->first();
        $this->assertNotNull($docInDb);
        $this->assertEquals($manualNumber, $docInDb->number);
        $this->assertEquals('archived', $docInDb->status);
        $this->assertEquals(85, $docInDb->sequence_number);
    }
}
