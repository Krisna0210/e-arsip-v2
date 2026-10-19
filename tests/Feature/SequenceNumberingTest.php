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

        // Staf klik ambil nomor surat (2 digit)
        $component = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unit->id)
            ->set('signer_type', 'pimpinan_unit')
            ->set('recipient_name', 'PT Rekanan Utama')
            ->set('subject', 'Uji Coba Penomoran Otomatis Berangkat dari Sequence Awal')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber');

        $component->assertSet('number', '86/SK/' . $unit->code . '/' . date('Y'));

        $docInDb = Document::where('type', 'outgoing')->latest('id')->first();
        $this->assertNotNull($docInDb);
        $this->assertEquals(86, $docInDb->sequence_number);
        $this->assertEquals('86/SK/' . $unit->code . '/' . date('Y'), $docInDb->number);

        // Ambil nomor kedua -> harus 87
        $component2 = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unit->id)
            ->set('signer_type', 'pimpinan_unit')
            ->set('recipient_name', 'PT Mitra Kedua')
            ->set('subject', 'Surat Kedua Otomatis')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber');

        $component2->assertSet('number', '87/SK/' . $unit->code . '/' . date('Y'));
    }

    public function test_outgoing_dual_condition_numbering_for_director_and_unit_head()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // Siapkan unit OPS
        $unitOps = Unit::firstOrCreate(['code' => 'OPS'], ['name' => 'Operasional', 'status' => 'active']);

        // Siapkan klasifikasi HM.00
        $classHm = Classification::firstOrCreate(['code' => 'HM.00'], [
            'subject' => 'Hukum & Organisasi',
            'active_years' => 2,
            'inactive_years' => 5,
            'outcome' => 'permanen'
        ]);

        // Siapkan jenis dokumen dengan format penomoran sesuai kasus user:
        // Format: {NO}/{DIR}{UNIT}-{CLASS}/{MM}/{YYYY}
        $docType = DocumentType::create([
            'name' => 'Surat Keluar Standar Dinas',
            'default_sifat' => 'biasa',
            'numbering_format' => '{NO}/{DIR}{UNIT}-{CLASS}/{MM}/{YYYY}',
            'starting_sequence' => 0,
            'starting_sequence_dir' => 0,
            'starting_sequence_year' => 2026,
        ]);

        $testDate = '2026-10-12'; // Bulan 10, Tahun 2026

        // KONDISI 1: Surat Keluar ditandatangani oleh DIREKTUR
        $compDir = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unitOps->id)
            ->set('signer_type', 'direktur')
            ->set('recipient_name', 'Kementerian BUMN')
            ->set('subject', 'Surat Keputusan Direktur')
            ->set('classification_id', $classHm->id)
            ->set('document_date', $testDate)
            ->set('sifat', 'biasa')
            ->call('bookNumber');

        // Harus menghasilkan nomor 01/DIR-OPS-HM.00/10/2026 (2 digit, prefix DIR-)
        $compDir->assertSet('number', '01/DIR-OPS-HM.00/10/2026');

        $docDir = Document::where('number', '01/DIR-OPS-HM.00/10/2026')->first();
        $this->assertNotNull($docDir);
        $this->assertEquals('direktur', $docDir->signer_type);
        $this->assertEquals(1, $docDir->sequence_number);

        // KONDISI 2: Surat Keluar ditandatangani oleh PIMPINAN UNIT KERJA (Opsi B: Urutan Terpisah)
        $compUnit = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unitOps->id)
            ->set('signer_type', 'pimpinan_unit')
            ->set('recipient_name', 'Dinas Perhubungan')
            ->set('subject', 'Surat Pengantar Operasional')
            ->set('classification_id', $classHm->id)
            ->set('document_date', $testDate)
            ->set('sifat', 'biasa')
            ->call('bookNumber');

        // Harus menghasilkan nomor 01/OPS-HM.00/10/2026 (2 digit, tanpa DIR-, mulai dari 01 mandiri)
        $compUnit->assertSet('number', '01/OPS-HM.00/10/2026');

        $docUnit = Document::where('number', '01/OPS-HM.00/10/2026')->first();
        $this->assertNotNull($docUnit);
        $this->assertEquals('pimpinan_unit', $docUnit->signer_type);
        $this->assertEquals(1, $docUnit->sequence_number);

        // AMBIL NOMOR KEDUA UNTUK DIREKTUR -> Harus naik ke 02/DIR-OPS-HM.00/10/2026
        $compDir2 = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unitOps->id)
            ->set('signer_type', 'direktur')
            ->set('recipient_name', 'Otoritas Jasa Keuangan')
            ->set('subject', 'Surat Edaran Direksi Kedua')
            ->set('classification_id', $classHm->id)
            ->set('document_date', $testDate)
            ->set('sifat', 'biasa')
            ->call('bookNumber');

        $compDir2->assertSet('number', '02/DIR-OPS-HM.00/10/2026');
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

    public function test_outgoing_create_defaults_to_surat_keluar_document_type()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $component = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class);

        $outgoingType = DocumentType::where('name', 'like', '%Surat Keluar%')->first();
        $this->assertNotNull($outgoingType);
        $component->assertSet('document_type_id', $outgoingType->id);
    }

    public function test_admin_can_set_document_type_code_in_master_data()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::first();

        Livewire::actingAs($superadmin)
            ->test(ArchiveClassificationIndex::class)
            ->call('editDocType', $docType->id)
            ->set('typeCode', 'ND')
            ->call('saveDocType')
            ->assertHasNoErrors();

        $docType->refresh();
        $this->assertEquals('ND', $docType->code);
    }

    public function test_numbering_format_replaces_kosur_placeholder()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::where('name', 'like', '%Surat Keluar%')->first();
        $docType->update([
            'code' => 'SK-DIR',
            'numbering_format' => '{NO}/{KOSUR}/{UNIT}/{YYYY}',
        ]);

        $unit = Unit::where('type', 'division')->first() ?? Unit::first();
        $classification = Classification::whereNotNull('parent_id')->first() ?? Classification::first();

        $component = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unit->id)
            ->set('signer_type', 'pimpinan_unit')
            ->set('recipient_name', 'PT Mitra Nusantara')
            ->set('subject', 'Uji Coba Variabel KOSUR pada Format Penomoran')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber')
            ->assertHasNoErrors();

        $generatedNumber = $component->get('number');
        $this->assertStringContainsString('/SK-DIR/' . $unit->code . '/' . date('Y'), $generatedNumber);
    }

    public function test_duplicate_document_type_name_or_code_returns_validation_error_gracefully()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // Ensure 2 distinct document types exist
        $docType1 = DocumentType::first();
        $docType2 = DocumentType::skip(1)->first();

        // Try changing docType2's name to docType1's name -> should trigger validation error
        Livewire::actingAs($superadmin)
            ->test(ArchiveClassificationIndex::class)
            ->call('editDocType', $docType2->id)
            ->set('typeName', $docType1->name)
            ->call('saveDocType')
            ->assertHasErrors(['typeName']);

        // Try changing docType2's code to docType1's code -> should trigger validation error
        Livewire::actingAs($superadmin)
            ->test(ArchiveClassificationIndex::class)
            ->call('editDocType', $docType2->id)
            ->set('typeCode', $docType1->code)
            ->call('saveDocType')
            ->assertHasErrors(['typeCode']);
    }

    public function test_numbering_format_replaces_div_placeholder_from_user_division()
    {
        $department = Unit::where('type', 'department')->whereNotNull('parent_id')->first();
        $division = $department->parent;

        // 1. User belongs to a department under a division
        $staffUser = User::factory()->create([
            'unit_id' => $department->id,
        ]);

        $docType = DocumentType::where('name', 'like', '%Surat Keluar%')->first();
        $docType->update([
            'code' => 'SK',
            'numbering_format' => '{NO}/{KOSUR}/{DIV}/{UNIT}/{YYYY}',
        ]);

        $classification = Classification::whereNotNull('parent_id')->first() ?? Classification::first();

        $component = Livewire::actingAs($staffUser)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $division->id)
            ->set('signer_type', 'pimpinan_unit')
            ->set('recipient_name', 'Bank BNI')
            ->set('subject', 'Uji Coba Resolusi Otomatis {DIV} dari Akun')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber')
            ->assertHasNoErrors();

        $generatedNumber = $component->get('number');
        // Must contain /SK/{division_code}/{unit_code}/{YYYY}
        $this->assertStringContainsString("/SK/{$division->code}/{$division->code}/" . date('Y'), $generatedNumber);
    }
}
