<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Classification;
use App\Models\Document;
use App\Models\Unit;
use App\Models\User;
use App\Livewire\Registry\Incoming\Create as IncomingCreate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class IncomingMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_incoming_create_mounts_with_default_dates_and_classifications()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $component = Livewire::actingAs($superadmin)
            ->test(IncomingCreate::class);

        $component->assertSet('document_date', date('Y-m-d'));
        $component->assertSet('received_date', date('Y-m-d'));
        $component->assertSet('sifat', 'biasa');

        $classifications = $component->get('classifications');
        $this->assertNotEmpty($classifications);
    }

    public function test_user_can_register_incoming_mail_with_classification_and_retention()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $classification = Classification::where('active_years', '>', 0)->first() ?? Classification::first();
        $classification->update(['active_years' => 5]);

        $file = UploadedFile::fake()->create('surat_masuk.pdf', 100, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(IncomingCreate::class)
            ->set('number', '099/EXT-KEMENKEU/X/2026')
            ->set('document_date', date('Y-m-d'))
            ->set('received_date', date('Y-m-d'))
            ->set('sender', 'Kementerian Keuangan RI')
            ->set('subject', 'Undangan Rapat Koordinasi Anggaran')
            ->set('classification_id', $classification->id)
            ->set('unit_id', $unit->id)
            ->set('sifat', 'biasa')
            ->set('file', $file)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true)
            ->assertSet('savedDocumentNumber', '099/EXT-KEMENKEU/X/2026')
            ->call('redirectToIndex')
            ->assertRedirect(route('registry.incoming.index'));

        $doc = Document::where('number', '099/EXT-KEMENKEU/X/2026')->first();
        $this->assertNotNull($doc);
        $this->assertEquals('incoming', $doc->type);
        $this->assertEquals($classification->id, $doc->classification_id);
        $this->assertEquals('archived', $doc->status);
        $this->assertEquals(date('Y-m-d'), $doc->retention_start_date->format('Y-m-d'));
        $this->assertEquals(date('Y-m-d', strtotime('+5 years')), $doc->retention_end_date->format('Y-m-d'));

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $doc->id,
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_user_can_reset_form_to_input_another_incoming_mail()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $classification = Classification::first();
        $file = UploadedFile::fake()->create('surat1.pdf', 50, 'application/pdf');

        $component = Livewire::actingAs($superadmin)
            ->test(IncomingCreate::class)
            ->set('number', '001/IN/2026')
            ->set('document_date', date('Y-m-d'))
            ->set('received_date', date('Y-m-d'))
            ->set('sender', 'Vendor A')
            ->set('subject', 'Faktur Pembayaran')
            ->set('classification_id', $classification->id)
            ->set('unit_id', $unit->id)
            ->set('sifat', 'biasa')
            ->set('file', $file)
            ->call('save')
            ->assertSet('showSuccessModal', true);

        // Click "Input Surat Masuk Lagi"
        $component->call('resetFormAndInputAgain');
        $component->assertSet('showSuccessModal', false);
        $component->assertSet('number', null);
        $component->assertSet('sender', null);
        $component->assertSet('subject', null);
        $component->assertSet('document_date', date('Y-m-d'));
        $component->assertSet('received_date', date('Y-m-d'));
    }

    public function test_incoming_mail_can_be_registered_without_classification()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $file = UploadedFile::fake()->create('surat_masuk.pdf', 100, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(IncomingCreate::class)
            ->set('number', '099/EXT/2026')
            ->set('document_date', date('Y-m-d'))
            ->set('received_date', date('Y-m-d'))
            ->set('sender', 'Mitra Kerja')
            ->set('subject', 'Surat Masuk Tanpa Klasifikasi')
            ->set('classification_id', null)
            ->set('unit_id', $unit->id)
            ->set('file', $file)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true);

        $doc = Document::where('number', '099/EXT/2026')->first();
        $this->assertNotNull($doc);
        $this->assertNull($doc->classification_id);
        $this->assertNull($doc->retention_start_date);
        $this->assertNull($doc->retention_end_date);
    }

    public function test_user_can_register_incoming_mail_with_hardcopy_only_without_file()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        Livewire::actingAs($superadmin)
            ->test(IncomingCreate::class)
            ->set('number', '088/HARDCOPY-ONLY/2026')
            ->set('document_date', date('Y-m-d'))
            ->set('received_date', date('Y-m-d'))
            ->set('sender', 'Instansi Fisik')
            ->set('subject', 'Surat Masuk Fisik Saja')
            ->set('unit_id', $unit->id)
            ->set('available_hardcopy', true)
            ->set('available_softcopy', false)
            ->set('file', null)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true);

        $doc = Document::where('number', '088/HARDCOPY-ONLY/2026')->first();
        $this->assertNotNull($doc);
        $this->assertTrue($doc->available_hardcopy);
        $this->assertFalse($doc->available_softcopy);
        $this->assertNull($doc->current_version_id);
    }

    public function test_user_cannot_register_incoming_mail_if_both_formats_are_unchecked()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        Livewire::actingAs($superadmin)
            ->test(IncomingCreate::class)
            ->set('number', '077/NO-FORMAT/2026')
            ->set('document_date', date('Y-m-d'))
            ->set('received_date', date('Y-m-d'))
            ->set('sender', 'Instansi')
            ->set('subject', 'Tanpa Format')
            ->set('unit_id', $unit->id)
            ->set('available_hardcopy', false)
            ->set('available_softcopy', false)
            ->call('save')
            ->assertHasErrors(['available_hardcopy']);
    }

    public function test_user_can_register_incoming_mail_with_both_hardcopy_and_softcopy()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $file = UploadedFile::fake()->create('surat_keduanya.pdf', 100, 'application/pdf');

        Livewire::actingAs($superadmin)
            ->test(IncomingCreate::class)
            ->set('number', '066/BOTH-FORMATS/2026')
            ->set('document_date', date('Y-m-d'))
            ->set('received_date', date('Y-m-d'))
            ->set('sender', 'Instansi Lengkap')
            ->set('subject', 'Surat Masuk Hardcopy dan Softcopy')
            ->set('unit_id', $unit->id)
            ->set('available_hardcopy', true)
            ->set('available_softcopy', true)
            ->set('file', $file)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showSuccessModal', true);

        $doc = Document::where('number', '066/BOTH-FORMATS/2026')->first();
        $this->assertNotNull($doc);
        $this->assertTrue($doc->available_hardcopy);
        $this->assertTrue($doc->available_softcopy);
        $this->assertNotNull($doc->current_version_id);
    }
}
