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

    public function test_incoming_mail_validation_fails_without_classification()
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
            ->set('subject', 'Surat Tanpa Klasifikasi')
            ->set('classification_id', null)
            ->set('unit_id', $unit->id)
            ->set('file', $file)
            ->call('save')
            ->assertHasErrors(['classification_id']);
    }
}
