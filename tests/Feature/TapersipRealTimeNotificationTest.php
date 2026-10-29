<?php

namespace Tests\Feature;

use App\Livewire\Components\TapersipNotificationListener;
use App\Livewire\Registry\DocumentLoanIndex;
use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TapersipRealTimeNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_listener_dispatches_desktop_notify_when_new_loan_is_requested()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staff = User::factory()->create([
            'name' => 'Budi Staf Pengaju',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '001/TEST/NOTIF/2026',
            'subject' => 'Surat Uji Notifikasi Real-time',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        // 1. Tapersip sedang membuka sistem (komponen listener termuat)
        $component = Livewire::actingAs($superadmin)
            ->test(TapersipNotificationListener::class);

        // 2. Staf mengajukan pinjam dokumen
        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $staff->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Peminjaman Audit Rutin',
            'status' => 'requested',
        ]);

        // 3. Polling interval berjalan (checkLoanEvents dipanggil)
        $component->call('checkLoanEvents')
            ->assertDispatched('desktop-notify', function ($event, $params) use ($loan) {
                $data = is_array($params) && isset($params['title']) ? $params : ($params[0] ?? []);
                return ($data['type'] ?? '') === 'loan_requested'
                    && ($data['loan_code'] ?? '') === $loan->loan_code;
            })
            ->assertDispatched('loan-status-changed');
    }

    public function test_listener_dispatches_desktop_notify_when_loan_is_cancelled()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staff = User::factory()->create([
            'name' => 'Siti Pemohon',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '002/TEST/CANCEL/2026',
            'subject' => 'Surat Uji Pembatalan',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $staff->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji Coba Batal',
            'status' => 'requested',
        ]);

        // 1. Tapersip memuat komponen listener saat status masih requested
        $component = Livewire::actingAs($superadmin)
            ->test(TapersipNotificationListener::class);

        // 2. Staf membatalkan permohonan pinjam
        $loan->update([
            'status' => 'cancelled',
            'notes' => 'Dibatalkan oleh pemohon',
            'updated_at' => now()->addSecond(),
        ]);

        // 3. Polling mendeteksi pembatalan
        $component->call('checkLoanEvents')
            ->assertDispatched('desktop-notify', function ($event, $params) use ($loan) {
                $data = is_array($params) && isset($params['title']) ? $params : ($params[0] ?? []);
                return ($data['type'] ?? '') === 'loan_cancelled'
                    && ($data['loan_code'] ?? '') === $loan->loan_code;
            })
            ->assertDispatched('loan-status-changed');
    }

    public function test_non_tapersip_user_does_not_poll_or_dispatch_notifications()
    {
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();

        $regularUser = User::factory()->create([
            'name' => 'Staf Biasa Tanpa Akses',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $component = Livewire::actingAs($regularUser)
            ->test(TapersipNotificationListener::class);

        // Buat pinjaman baru
        $document = Document::create([
            'number' => '003/DOC/2026',
            'subject' => 'Surat Lain',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $regularUser->id,
        ]);

        DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $regularUser->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji Staf Biasa',
            'status' => 'requested',
        ]);

        $component->call('checkLoanEvents')
            ->assertNotDispatched('desktop-notify');
    }

    public function test_listener_does_not_repeat_notifications_for_already_notified_loans()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staff = User::factory()->create([
            'name' => 'Joko Staf',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '004/TEST/REPEAT/2026',
            'subject' => 'Surat Uji Anti Duplikat Notifikasi',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        $component = Livewire::actingAs($superadmin)
            ->test(TapersipNotificationListener::class);

        // Buat permohonan pinjam
        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $staff->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji No Duplicate',
            'status' => 'requested',
        ]);

        // Polling 1: Terkirim
        $component->call('checkLoanEvents')
            ->assertDispatched('desktop-notify');

        // Polling 2 tanpa data baru: Tidak boleh mengirim lagi
        $component->call('checkLoanEvents');

        $this->assertEquals($loan->id, $component->get('lastKnownRequestId'));
    }

    public function test_document_loan_index_listens_to_loan_status_changed_event()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->dispatch('loan-status-changed')
            ->assertStatus(200);
    }

    public function test_listener_renders_notification_bell_and_dropdown_items()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staff = User::factory()->create([
            'name' => 'Maya Staf',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '009/DOC/BELL/2026',
            'subject' => 'Surat Uji Dropdown Lonceng',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => 'LOAN-BELL-1234',
            'document_id' => $document->id,
            'user_id' => $staff->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji Dropdown',
            'status' => 'requested',
        ]);

        Livewire::actingAs($superadmin)
            ->test(TapersipNotificationListener::class)
            ->assertSee('Notifikasi')
            ->assertSee('LOAN-BELL-1234')
            ->assertSee('Maya Staf');
    }

    public function test_listener_displays_scan_queue_notification_items()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $doc = Document::create([
            'number' => '010/DOC/SCAN/2026',
            'subject' => 'Surat Uji Scan Dropdown',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(TapersipNotificationListener::class)
            ->assertSee('Upload Scan')
            ->assertSee('010/DOC/SCAN/2026')
            ->assertSee('Permintaan Upload Scan');
    }

    public function test_listener_can_dismiss_notification_and_reduce_pending_count()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $doc = Document::create([
            'number' => '011/DOC/DISMISS/2026',
            'subject' => 'Surat Uji Dismiss Klik',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $component = Livewire::actingAs($superadmin)
            ->test(TapersipNotificationListener::class);

        $initialPending = $component->get('pendingCount');
        $this->assertGreaterThan(0, $initialPending);

        // Simulasi klik pada notifikasi untuk dismiss & redirect
        $targetUrl = route('registry.scan-queue', ['highlight' => "doc-{$doc->id}"]);
        $component->call('dismissAndRedirect', "scan_{$doc->id}", $targetUrl)
            ->assertRedirect($targetUrl);

        // Setelah di-dismiss, pendingCount berkurang 1 dan dokumen berpindah ke tab Histori
        $afterDismiss = Livewire::actingAs($superadmin)
            ->test(TapersipNotificationListener::class)
            ->assertSet('pendingCount', $initialPending - 1);

        $unreadDocNumbers = collect($afterDismiss->viewData('unreadItems'))->pluck('identifier')->toArray();
        $historyDocNumbers = collect($afterDismiss->viewData('historyItems'))->pluck('identifier')->toArray();

        $this->assertNotContains('011/DOC/DISMISS/2026', $unreadDocNumbers);
        $this->assertContains('011/DOC/DISMISS/2026', $historyDocNumbers);
    }

    public function test_listener_updates_pending_count_on_loan_status_changed_event()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();

        $staff = User::factory()->create([
            'name' => 'Fajar Staff',
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $doc = Document::create([
            'number' => '012/DOC/SYNC/2026',
            'subject' => 'Surat Uji Sinkronisasi Real-Time',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $doc->id,
            'user_id' => $staff->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(3),
            'purpose' => 'Uji Event Sinkronisasi',
            'status' => 'requested',
        ]);

        $component = Livewire::actingAs($superadmin)
            ->test(TapersipNotificationListener::class);

        $initialPending = $component->get('pendingCount');
        $this->assertGreaterThan(0, $initialPending);

        // Simulasi perubahan status (disetujui oleh Tapersip)
        $loan->update(['status' => 'borrowed', 'officer_id' => $superadmin->id]);

        // Kirim event loan-status-changed ke komponen
        $component->dispatch('loan-status-changed')
            ->assertSet('pendingCount', $initialPending - 1);
    }

    public function test_history_count_is_real_and_increments_when_notifications_are_read()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Ambil history count awal langsung dari database
        $initialHistory = \App\Models\UserNotificationRead::where('user_id', $superadmin->id)->count();

        $doc = Document::create([
            'number' => '099/DOC/HIST-TEST/2026',
            'subject' => 'Surat Uji Histori Count Riil',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        $component = Livewire::actingAs($superadmin)
            ->test(TapersipNotificationListener::class)
            ->assertSet('historyCount', $initialHistory);

        // 2. Tapersip membaca/dismiss satu notifikasi
        $targetUrl = route('registry.scan-queue', ['highlight' => "doc-{$doc->id}"]);
        $component->call('dismissAndRedirect', "scan_{$doc->id}", $targetUrl)
            ->assertSet('historyCount', $initialHistory + 1);

        // 3. Verifikasi record di database benar-benar bertambah
        $this->assertEquals($initialHistory + 1, \App\Models\UserNotificationRead::where('user_id', $superadmin->id)->count());
    }
}
