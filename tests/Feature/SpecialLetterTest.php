<?php

namespace Tests\Feature;

use App\Livewire\Registry\Internal\Create as InternalCreate;
use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SpecialLetterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_access_special_letter_routes()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. New route /registry/special returns 200 OK
        $responseIndex = $this->actingAs($superadmin)->get('/registry/special');
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Surat Khusus');

        // 2. New route /registry/special/create returns 200 OK
        $responseCreate = $this->actingAs($superadmin)->get('/registry/special/create');
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Registrasi Surat Khusus');

        // 3. Backward compatibility route /registry/internal redirects to /registry/special
        $responseRedirectIndex = $this->actingAs($superadmin)->get('/registry/internal');
        $responseRedirectIndex->assertRedirect(route('registry.special.index'));

        // 4. Backward compatibility route /registry/internal/create redirects to /registry/special/create
        $responseRedirectCreate = $this->actingAs($superadmin)->get('/registry/internal/create');
        $responseRedirectCreate->assertRedirect(route('registry.special.create'));
    }

    public function test_document_storage_directory_uses_surat_khusus_for_internal_type()
    {
        $doc = new Document(['type' => 'internal']);
        $this->assertStringStartsWith('documents/Surat Khusus/', $doc->getStorageDirectoryPath());
    }

    public function test_special_letter_numbering_uses_two_digit_format()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::where('name', 'like', '%Memo%')->first()
            ?? DocumentType::firstOrCreate([
                'name' => 'Memo Internal',
                'code' => 'MI',
                'numbering_format' => '{NO}/MI/{UNIT}/{YYYY}',
            ]);
        $department = Unit::departments()->first() ?? Unit::first();
        $recipient = Unit::departments()->where('id', '!=', $department->id)->first() ?? $department;
        $classification = Classification::whereNotNull('parent_id')->first() ?? Classification::first();

        $component = Livewire::actingAs($superadmin)
            ->test(InternalCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $department->id)
            ->set('recipient_id', $recipient->id)
            ->set('subject', 'Uji Coba Penomoran Surat Khusus 2 Digit')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber')
            ->assertHasNoErrors();

        $generatedNumber = $component->get('number');

        // Nomor yang diterbitkan harus menggunakan format 2 digit (misal: 01/, 02/) bukan 3 digit (001/)
        $this->assertMatchesRegularExpression('/^\d{2}\//', $generatedNumber);
        $this->assertDoesNotMatchRegularExpression('/^\d{3}\//', $generatedNumber);
    }
}
