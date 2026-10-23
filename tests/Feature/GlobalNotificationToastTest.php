<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalNotificationToastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_global_layout_renders_toast_container_and_listens_to_notify_event()
    {
        $user = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('GLOBAL IN-APP TOAST BANNER CONTAINER');
        $response->assertSee("window.addEventListener('notify'", false);
        $response->assertSee('toasts: []', false);
    }

    public function test_session_flash_message_is_rendered_in_global_toast()
    {
        $user = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($user)
            ->withSession(['success' => 'Data arsip berhasil diperbarui.'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Data arsip berhasil diperbarui.');
        $response->assertSee("add('Data arsip berhasil diperbarui.', 'success');", false);
    }

    public function test_session_error_message_is_rendered_in_global_toast()
    {
        $user = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($user)
            ->withSession(['error' => 'Gagal menghubungkan ke direktori NAS.'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Gagal menghubungkan ke direktori NAS.');
        $response->assertSee("add('Gagal menghubungkan ke direktori NAS.', 'error');", false);
    }
}
