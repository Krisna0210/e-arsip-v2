<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Classification;
use App\Models\Document;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardRealTimeSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_dashboard_has_loan_status_changed_listener_and_mounts_with_last_checked_at()
    {
        $roleUser = Role::where('name', 'User')->first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => Unit::first()->id,
        ]);

        Livewire::actingAs($staff)
            ->test(Dashboard::class)
            ->assertSet('lastCheckedAt', fn ($val) => !empty($val))
            ->dispatch('loan-status-changed')
            ->assertStatus(200);
    }

    public function test_dashboard_detects_archived_status_and_dispatches_success_toast()
    {
        $roleUser = Role::where('name', 'User')->first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => Unit::first()->id,
        ]);

        $classification = Classification::whereNotNull('parent_id')->first();

        $document = Document::create([
            'number' => '501/POLL-ARCH/2026',
            'subject' => 'Surat Menunggu Scan',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'classification_id' => $classification->id,
            'created_by' => $staff->id,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::actingAs($staff)->test(Dashboard::class);

        // Simulasi Tapersip mengunggah scan PDF (status -> archived)
        $document->update([
            'status' => 'archived',
            'updated_at' => now()->addSecond(),
        ]);

        // Background polling berjalan memanggil checkStatusUpdates
        $component->call('checkStatusUpdates')
            ->assertDispatched('notify', function ($event, $params) use ($document) {
                $data = is_array($params) && isset($params['message']) ? $params : ($params[0] ?? []);
                return ($data['type'] ?? '') === 'success'
                    && str_contains($data['message'] ?? '', $document->number)
                    && str_contains($data['message'] ?? '', 'resmi diarsipkan');
            });
    }

    public function test_dashboard_detects_cancellation_rejection_and_dispatches_warning_toast()
    {
        $roleUser = Role::where('name', 'User')->first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => Unit::first()->id,
        ]);

        $classification = Classification::whereNotNull('parent_id')->first();

        $document = Document::create([
            'number' => '502/POLL-REJECT/2026',
            'subject' => 'Surat Diajukan Batal',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Alasan batal dari staf',
            'document_date' => now()->format('Y-m-d'),
            'classification_id' => $classification->id,
            'created_by' => $staff->id,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::actingAs($staff)->test(Dashboard::class);

        // Simulasi Tapersip menolak pembatalan
        $document->update([
            'status' => 'waiting_for_file',
            'cancellation_rejection_notes' => 'Surat fisik sudah terlanjur dicetak resmi',
            'updated_at' => now()->addSecond(),
        ]);

        $component->call('checkStatusUpdates')
            ->assertDispatched('notify', function ($event, $params) use ($document) {
                $data = is_array($params) && isset($params['message']) ? $params : ($params[0] ?? []);
                return ($data['type'] ?? '') === 'warning'
                    && str_contains($data['message'] ?? '', $document->number)
                    && str_contains($data['message'] ?? '', 'ditolak Tapersip')
                    && str_contains($data['message'] ?? '', 'Surat fisik sudah terlanjur dicetak resmi');
            });
    }

    public function test_dashboard_detects_cancellation_approval_and_dispatches_info_toast()
    {
        $roleUser = Role::where('name', 'User')->first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => Unit::first()->id,
        ]);

        $classification = Classification::whereNotNull('parent_id')->first();

        $document = Document::create([
            'number' => '503/POLL-VOID/2026',
            'subject' => 'Surat Diajukan Batal Void',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Alasan pembatalan',
            'document_date' => now()->format('Y-m-d'),
            'classification_id' => $classification->id,
            'created_by' => $staff->id,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::actingAs($staff)->test(Dashboard::class);

        // Simulasi Tapersip menyetujui pembatalan
        $document->update([
            'status' => 'cancelled',
            'updated_at' => now()->addSecond(),
        ]);

        $component->call('checkStatusUpdates')
            ->assertDispatched('notify', function ($event, $params) use ($document) {
                $data = is_array($params) && isset($params['message']) ? $params : ($params[0] ?? []);
                return ($data['type'] ?? '') === 'info'
                    && str_contains($data['message'] ?? '', $document->number)
                    && str_contains($data['message'] ?? '', 'disetujui oleh Tapersip (Void)');
            });
    }

    public function test_dashboard_pauses_polling_notifications_when_modal_is_active()
    {
        $roleUser = Role::where('name', 'User')->first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => Unit::first()->id,
        ]);

        $document = Document::create([
            'number' => '504/POLL-PAUSE/2026',
            'subject' => 'Surat Uji Pause Modal',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::actingAs($staff)
            ->test(Dashboard::class)
            ->set('editDocId', $document->id); // Modal edit sedang aktif

        // Dokumen berubah status di background
        $document->update([
            'status' => 'archived',
            'updated_at' => now()->addSecond(),
        ]);

        // Polling berjalan saat modal terbuka
        $component->call('checkStatusUpdates')
            ->assertNotDispatched('notify');
    }

    public function test_dashboard_metrics_and_monitoring_table_update_on_poll()
    {
        $roleUser = Role::where('name', 'User')->first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => Unit::first()->id,
        ]);

        $document = Document::create([
            'number' => '505/POLL-METRIC/2026',
            'subject' => 'Surat Uji Perubahan Metrik Real-Time',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::actingAs($staff)->test(Dashboard::class);

        // Sebelum upload: Menunggu Upload tertera di tabel
        $component->assertSee('Menunggu Upload');

        // Simulasi Tapersip mengarsipkan dokumen
        $document->update([
            'status' => 'archived',
            'updated_at' => now()->addSecond(),
        ]);

        // Background polling berjalan
        $component->call('checkStatusUpdates');

        // Setelah upload: tabel monitoring sekarang menampilkan Selesai Diarsipkan
        $component->assertSee('Selesai Diarsipkan');
    }
}
