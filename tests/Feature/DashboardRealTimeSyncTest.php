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

    public function test_dashboard_statistics_cards_distinguish_between_staff_personal_count_and_tapersip_global_count()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        // 1. Role staf dengan kartu statistik dashboard aktif
        $roleStaff = Role::create([
            'name' => 'Staf Operasional Metrik ' . uniqid(),
            'status' => 'active',
            'module_visibility' => [
                'dashboard',
                'card_incoming',
                'card_outgoing',
                'card_internal',
                'card_waiting',
            ],
        ]);

        $staffA = User::factory()->create([
            'name' => 'Staf A',
            'role_id' => $roleStaff->id,
            'unit_id' => $unit->id,
        ]);

        $staffB = User::factory()->create([
            'name' => 'Staf B',
            'role_id' => $roleStaff->id,
            'unit_id' => $unit->id,
        ]);

        // Staff A membuat 1 surat keluar dan 1 surat khusus
        Document::create([
            'number' => 'SK-A-01/2026',
            'subject' => 'Surat Keluar Staff A',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staffA->id,
        ]);

        Document::create([
            'number' => 'SKH-A-01/2026',
            'subject' => 'Surat Khusus Staff A',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staffA->id,
        ]);

        // Staff B membuat 2 surat keluar dan 1 surat masuk
        Document::create([
            'number' => 'SK-B-01/2026',
            'subject' => 'Surat Keluar Staff B #1',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staffB->id,
        ]);

        Document::create([
            'number' => 'SK-B-02/2026',
            'subject' => 'Surat Keluar Staff B #2',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staffB->id,
        ]);

        Document::create([
            'number' => 'SM-B-01/2026',
            'subject' => 'Surat Masuk Staff B',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staffB->id,
        ]);

        // 2. Login sebagai Staff A: nilai kartu harus eksklusif menghitung surat milik Staff A saja dan tidak dapat diklik (cursor-default, no href)
        $componentStaffA = Livewire::actingAs($staffA)->test(Dashboard::class);

        $this->assertEquals(1, $componentStaffA->viewData('countOutgoing'));
        $this->assertEquals(1, $componentStaffA->viewData('countInternal'));
        $this->assertEquals(0, $componentStaffA->viewData('countIncoming'));
        $componentStaffA->assertSee('Surat Keluar Saya')
            ->assertSee('Surat Khusus Saya')
            ->assertSee('Surat Masuk Saya')
            ->assertSee('cursor-default')
            ->assertDontSee(route('registry.incoming.index'))
            ->assertDontSee(route('registry.outgoing.index'))
            ->assertDontSee(route('registry.special.index'))
            ->assertDontSee(route('registry.scan-queue'));

        // 3. Login sebagai Superadmin: nilai kartu harus menghitung akumulasi total seluruh kantor dan dapat diklik (href aktif)
        $componentSuper = Livewire::actingAs($superadmin)->test(Dashboard::class);

        // Total Surat Keluar kantor: 1 (Staff A) + 2 (Staff B) = 3
        $this->assertEquals(3, $componentSuper->viewData('countOutgoing'));
        $this->assertEquals(1, $componentSuper->viewData('countInternal'));
        $this->assertEquals(1, $componentSuper->viewData('countIncoming'));
        $componentSuper->assertSee('Total Surat Keluar')
            ->assertSee('Total Surat Khusus')
            ->assertSee('Total Surat Masuk')
            ->assertSee('cursor-pointer')
            ->assertSee(route('registry.incoming.index'))
            ->assertSee(route('registry.outgoing.index'))
            ->assertSee(route('registry.special.index'))
            ->assertSee(route('registry.scan-queue'));
    }
}
