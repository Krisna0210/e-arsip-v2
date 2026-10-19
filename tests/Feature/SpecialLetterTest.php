<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
