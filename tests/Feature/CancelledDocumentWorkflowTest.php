<?php

namespace Tests\Feature;

use App\Livewire\Components\TapersipNotificationListener;
use App\Livewire\Dashboard;
use App\Livewire\Registry\CancelledDocumentIndex;
use App\Livewire\Registry\ScanQueue;
use App\Models\AuditLog;
use App\Models\Classification;
use App\Models\Document;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CancelledDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_staff_can_request_cancellation_from_dashboard()
    {
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $classification = Classification::whereNotNull('parent_id')->first();

        $document = Document::create([
            'number' => '401/STAFF/X/2026',
            'subject' => 'Surat Tugas Batal Terbit',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'classification_id' => $classification->id,
            'created_by' => $staff->id,
        ]);

        Livewire::actingAs($staff)
            ->test(Dashboard::class)
            ->call('openVoidDraftModal', $document->id)
            ->assertSet('voidDocId', $document->id)
            ->set('voidReason', 'Pembatalan penugasan oleh direksi')
            ->call('confirmVoidDraft')
            ->assertDispatched('close-modal', 'modal-dashboard-void-draft')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('cancellation_requested', $document->status);
        $this->assertEquals('Pembatalan penugasan oleh direksi', $document->notes);
        $this->assertNotNull($document->cancellation_requested_at);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'Permohonan Pembatalan Nomor Naskah: 401/STAFF/X/2026',
            'user_id' => $staff->id,
        ]);
    }

    public function test_staff_cancellation_request_appears_in_canceldoc_requests_tab()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleUser = Role::where('name', 'User')->first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '402/STAFF-REQ/X/2026',
            'subject' => 'Memo Internal Yang Diajukan Batal',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Salah format penomoran unit',
            'cancellation_requested_at' => now(),
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
            'unit_id' => $unit->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(CancelledDocumentIndex::class)
            ->assertSet('activeTab', 'requests')
            ->assertSee($document->number)
            ->assertSee($document->subject)
            ->assertSee('Salah format penomoran unit')
            ->assertSee($staff->name);
    }

    public function test_tapersip_can_approve_cancellation_request()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $staff = User::factory()->create([
            'role_id' => Role::where('name', 'User')->first()->id,
            'unit_id' => Unit::first()->id,
        ]);

        $document = Document::create([
            'number' => '403/APPROVE-VOID/2026',
            'subject' => 'Naskah Akan Disetujui Batal',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Kegiatan dibatalkan oleh sponsor',
            'cancellation_requested_at' => now(),
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(CancelledDocumentIndex::class)
            ->call('openApproveModal', $document->id)
            ->assertSet('approveDocId', $document->id)
            ->call('confirmApproveCancellation')
            ->assertDispatched('close-modal', 'modal-approve-cancellation')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('cancelled', $document->status);
        $this->assertEquals($superadmin->id, $document->cancelled_by);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'Persetujuan Pembatalan Nomor Naskah Resmi (Void): 403/APPROVE-VOID/2026',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_tapersip_can_reject_cancellation_request()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $staff = User::factory()->create([
            'role_id' => Role::where('name', 'User')->first()->id,
            'unit_id' => Unit::first()->id,
        ]);

        $document = Document::create([
            'number' => '404/REJECT-VOID/2026',
            'subject' => 'Naskah Yang Ditolak Pembatalannya',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Alasan staf kurang jelas',
            'cancellation_requested_at' => now(),
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(CancelledDocumentIndex::class)
            ->call('openRejectModal', $document->id)
            ->assertSet('rejectDocId', $document->id)
            ->set('rejectionNotes', 'Surat sudah diagendakan, gunakan ralat metadata bukan pembatalan')
            ->call('confirmRejectCancellation')
            ->assertDispatched('close-modal', 'modal-reject-cancellation')
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('waiting_for_file', $document->status);
        $this->assertNull($document->cancellation_requested_at);
        $this->assertEquals('Surat sudah diagendakan, gunakan ralat metadata bukan pembatalan', $document->cancellation_rejection_notes);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'Penolakan Permohonan Pembatalan Naskah: 404/REJECT-VOID/2026',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_tapersip_or_superadmin_can_directly_void_from_scan_queue()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $document = Document::create([
            'number' => '405/DIRECT-VOID/2026',
            'subject' => 'Naskah Langsung Divoid Tapersip',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(ScanQueue::class)
            ->call('openVoidModal', $document->id)
            ->set('voidReason', 'Kesalahan registrasi oleh staf')
            ->call('confirmVoidDocument')
            ->assertDispatched('close-modal', 'modal-void-queue-document');

        $document->refresh();
        $this->assertEquals('cancelled', $document->status);
        $this->assertEquals($superadmin->id, $document->cancelled_by);
    }

    public function test_canceldoc_tab_switching_shows_register_void_records()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $voidDoc = Document::create([
            'number' => '406/REGISTER-VOID/2026',
            'subject' => 'Naskah Void Resmi Masuk Buku Register',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancelled',
            'notes' => 'Batal resmi per instruksi pimpinan',
            'cancelled_by' => $superadmin->id,
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(CancelledDocumentIndex::class)
            ->call('setTab', 'register')
            ->assertSet('activeTab', 'register')
            ->assertSee($voidDoc->number)
            ->assertSee($voidDoc->subject)
            ->assertSee('BATAL (Void)')
            ->assertSee('Batal resmi per instruksi pimpinan');
    }

    public function test_unauthorized_user_cannot_approve_or_reject_cancellation()
    {
        $roleUser = Role::where('name', 'User')->first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => Unit::first()->id,
        ]);

        $document = Document::create([
            'number' => '407/UNAUTH/2026',
            'subject' => 'Naskah Terproteksi',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        Livewire::actingAs($staff)
            ->test(CancelledDocumentIndex::class)
            ->call('openApproveModal', $document->id)
            ->assertDispatched('notify');

        $document->refresh();
        $this->assertEquals('cancellation_requested', $document->status);
    }

    public function test_http_route_canceldoc_renders_successfully_with_app_layout()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superadmin)->get('/canceldoc');

        $response->assertStatus(200);
        $response->assertSee('Pembatalan Dokumen Dinas');
        $response->assertSee('Permohonan Batal');
        $response->assertSee('Buku Register Batal (Void)');
    }

    public function test_staff_cancellation_dispatches_desktop_notification_event()
    {
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();
        $staff = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => '408/NOTIF-TEST/2026',
            'subject' => 'Surat Uji Notifikasi Pembatalan',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'waiting_for_file',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        Livewire::actingAs($staff)
            ->test(Dashboard::class)
            ->call('openVoidDraftModal', $document->id)
            ->set('voidReason', 'Ralat penugasan dibatalkan pimpinan')
            ->call('confirmVoidDraft')
            ->assertDispatched('desktop-notify', function ($event, $params) use ($document) {
                $data = is_array($params) && isset($params['title']) ? $params : ($params[0] ?? []);
                return ($data['type'] ?? '') === 'cancellation_requested'
                    && ($data['loan_code'] ?? '') === $document->number;
            });
    }

    public function test_tapersip_notification_listener_includes_cancellation_requests_in_feed_and_badge()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $roleTapersip = Role::where('name', 'AdminTapersip')->first();
        $tapersip = User::factory()->create([
            'role_id' => $roleTapersip->id,
            'unit_id' => Unit::first()->id,
        ]);

        $staff = User::factory()->create([
            'role_id' => Role::where('name', 'User')->first()->id,
            'unit_id' => Unit::first()->id,
        ]);

        $cancelDoc = Document::create([
            'number' => '409/CANCEL-FEED/2026',
            'subject' => 'Surat Batal Masuk Feed Notifikasi',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Alasan masuk feed notifikasi',
            'cancellation_requested_at' => now(),
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        Livewire::actingAs($tapersip)
            ->test(TapersipNotificationListener::class)
            ->assertSet('pendingCount', 1)
            ->assertSee('Permohonan Batal Nomor')
            ->assertSee($cancelDoc->number)
            ->assertSee('Alasan masuk feed notifikasi');
    }

    public function test_tapersip_notification_dismiss_and_mark_all_as_read_for_cancellations()
    {
        $roleTapersip = Role::where('name', 'AdminTapersip')->first();
        $tapersip = User::factory()->create([
            'role_id' => $roleTapersip->id,
            'unit_id' => Unit::first()->id,
        ]);

        $staff = User::factory()->create([
            'role_id' => Role::where('name', 'User')->first()->id,
            'unit_id' => Unit::first()->id,
        ]);

        $cancelDoc = Document::create([
            'number' => '410/DISMISS-TEST/2026',
            'subject' => 'Surat Uji Dismiss Notifikasi',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Alasan dismiss test',
            'cancellation_requested_at' => now(),
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        // 1. Uji dismissAndRedirect
        Livewire::actingAs($tapersip)
            ->test(TapersipNotificationListener::class)
            ->assertSet('pendingCount', 1)
            ->call('dismissAndRedirect', "cancel_req_{$cancelDoc->id}", route('registry.cancelled.index', ['tab' => 'requests']))
            ->assertSet('pendingCount', 0);

        // 2. Buat dokumen pembatalan kedua untuk uji markAllAsRead
        $cancelDoc2 = Document::create([
            'number' => '411/READ-ALL/2026',
            'subject' => 'Surat Uji Mark All Read',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Alasan mark all read',
            'cancellation_requested_at' => now(),
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staff->id,
        ]);

        Livewire::actingAs($tapersip)
            ->test(TapersipNotificationListener::class)
            ->assertSet('pendingCount', 1)
            ->call('markAllAsRead')
            ->assertSet('pendingCount', 0);
    }

    public function test_read_notifications_persist_in_database_and_survive_cache_flush()
    {
        $roleTapersip = Role::where('name', 'AdminTapersip')->first();
        $tapersip = User::factory()->create([
            'role_id' => $roleTapersip->id,
            'unit_id' => Unit::first()->id,
        ]);

        $cancelDoc = Document::create([
            'number' => '412/PERSIST-TEST/2026',
            'subject' => 'Surat Uji Persistensi Database',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Alasan persist test',
            'cancellation_requested_at' => now(),
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $tapersip->id,
        ]);

        // 1. Tapersip membaca/dismiss notifikasi
        Livewire::actingAs($tapersip)
            ->test(TapersipNotificationListener::class)
            ->assertSet('pendingCount', 1)
            ->call('dismissAndRedirect', "cancel_req_{$cancelDoc->id}", route('registry.cancelled.index', ['tab' => 'requests']))
            ->assertSet('pendingCount', 0);

        // Verifikasi record tersimpan di tabel database user_notification_reads
        $this->assertDatabaseHas('user_notification_reads', [
            'user_id' => $tapersip->id,
            'notification_key' => "cancel_req_{$cancelDoc->id}",
        ]);

        // 2. Simulasi php artisan cache:clear / optimize:clear
        \Illuminate\Support\Facades\Cache::flush();

        // 3. Muat kembali komponen notifikasi setelah cache dibersihkan
        // pendingCount HARUS TETAP 0 dan dokumen tetap berada di tab histori (tidak bangkit kembali ke belum dibaca)
        $component = Livewire::actingAs($tapersip)
            ->test(TapersipNotificationListener::class)
            ->assertSet('pendingCount', 0);

        $unreadDocNumbers = collect($component->viewData('unreadItems'))->pluck('identifier')->toArray();
        $historyDocNumbers = collect($component->viewData('historyItems'))->pluck('identifier')->toArray();

        $this->assertNotContains('412/PERSIST-TEST/2026', $unreadDocNumbers);
        $this->assertContains('412/PERSIST-TEST/2026', $historyDocNumbers);
    }

    public function test_notification_cancellation_items_contain_highlight_url()
    {
        $roleTapersip = Role::where('name', 'AdminTapersip')->first();
        $tapersip = User::factory()->create([
            'role_id' => $roleTapersip->id,
            'unit_id' => Unit::first()->id,
        ]);

        $cancelDoc = Document::create([
            'number' => '413/NOTIF-HIGHLIGHT/2026',
            'subject' => 'Surat Uji Target URL Highlight',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'notes' => 'Alasan highlight test',
            'cancellation_requested_at' => now(),
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $tapersip->id,
        ]);

        $component = Livewire::actingAs($tapersip)
            ->test(TapersipNotificationListener::class);

        $unreadItems = collect($component->viewData('unreadItems'));
        $targetItem = $unreadItems->firstWhere('key', "cancel_req_{$cancelDoc->id}");

        $this->assertNotNull($targetItem);
        $this->assertStringContainsString("highlight=doc-{$cancelDoc->id}", $targetItem['target_url']);
        $this->assertStringContainsString('tab=requests', $targetItem['target_url']);
    }

    public function test_canceldoc_smart_page_resolver_navigates_to_correct_page_and_renders_spotlight()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // Buat 12 dokumen permohonan pembatalan berturut-turut
        $docs = [];
        for ($i = 1; $i <= 12; $i++) {
            $docs[] = Document::create([
                'number' => sprintf('414/PAGINATE-%02d/2026', $i),
                'subject' => "Surat Permohonan Batal {$i}",
                'type' => 'outgoing',
                'sifat' => 'biasa',
                'status' => 'cancellation_requested',
                'notes' => "Alasan nomor {$i}",
                'cancellation_requested_at' => now()->addMinutes($i),
                'document_date' => now()->format('Y-m-d'),
                'created_by' => $superadmin->id,
            ]);
        }

        $targetDoc = $docs[11]; // Dokumen ke-12 (berada di halaman 2 karena per halaman 10 data)

        // Akses dengan query highlight
        $response = $this->actingAs($superadmin)
            ->get(route('registry.cancelled.index', [
                'tab' => 'requests',
                'highlight' => "doc-{$targetDoc->id}",
            ]));

        $response->assertStatus(200);
        $response->assertSee("id=\"doc-row-{$targetDoc->id}\"", false);
        $response->assertSee($targetDoc->number);

        // Verifikasi melalui komponen Livewire langsung
        $this->get(route('registry.cancelled.index', [
            'tab' => 'requests',
            'highlight' => "doc-{$targetDoc->id}",
        ]));

        Livewire::withQueryParams([
            'tab' => 'requests',
            'highlight' => "doc-{$targetDoc->id}",
        ])->actingAs($superadmin)
            ->test(CancelledDocumentIndex::class)
            ->assertSet('activeTab', 'requests')
            ->assertDontSee($docs[0]->number) // Data halaman 1 tidak tampil di halaman 2
            ->assertSee($targetDoc->number) // Data target tampil di halaman 2
            ->assertSee("id=\"doc-row-{$targetDoc->id}\"", false);
    }

    public function test_canceldoc_smart_page_resolver_for_void_register_tab()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $voidDoc = Document::create([
            'number' => '415/VOID-RESOLVER/2026',
            'subject' => 'Surat Void Khusus Register',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancelled',
            'notes' => 'Alasan void resolver',
            'cancelled_by' => $superadmin->id,
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::withQueryParams([
            'highlight' => "doc-{$voidDoc->id}",
        ])->actingAs($superadmin)
            ->test(CancelledDocumentIndex::class)
            ->assertSet('activeTab', 'register')
            ->assertSee($voidDoc->number)
            ->assertSee("doc-row-{$voidDoc->id}");
    }

    public function test_registry_indexes_display_permohonan_batal_badge_for_cancellation_requested_documents()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Surat Keluar
        $outDoc = Document::create([
            'number' => '416/OUT-CANCEL-REQ/2026',
            'subject' => 'Surat Keluar Permohonan Batal',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Registry\Outgoing\Index::class)
            ->assertSee($outDoc->number)
            ->assertSee('Permohonan Batal')
            ->assertDontSee('Cancellation_requested');

        // 2. Surat Masuk
        $inDoc = Document::create([
            'number' => '417/IN-CANCEL-REQ/2026',
            'subject' => 'Surat Masuk Permohonan Batal',
            'type' => 'incoming',
            'sender' => 'Pengirim Batal',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Registry\Incoming\Index::class)
            ->assertSee($inDoc->number)
            ->assertSee('Permohonan Batal')
            ->assertDontSee('Cancellation_requested');

        // 3. Surat Khusus (Internal)
        $intDoc = Document::create([
            'number' => '418/INT-CANCEL-REQ/2026',
            'subject' => 'Surat Khusus Permohonan Batal',
            'type' => 'internal',
            'sifat' => 'biasa',
            'status' => 'cancellation_requested',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Registry\Internal\Index::class)
            ->assertSee($intDoc->number)
            ->assertSee('Permohonan Batal')
            ->assertDontSee('Cancellation_requested');
    }
}
