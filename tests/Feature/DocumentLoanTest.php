<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\Folder;
use App\Models\Role;
use App\Models\User;
use App\Livewire\Registry\DocumentLoanIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentLoanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_access_borrowings_page()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superadmin)->get(route('borrowings.index'));

        $response->assertStatus(200);
        $response->assertSeeText('Cari & Pinjam Berkas Arsip Fisik');
    }

    public function test_user_can_submit_loan_request_and_ticket_is_generated()
    {
        $user = User::first();
        $folder = Folder::create([
            'name' => 'Loker A-2026',
            'type' => 'incoming',
        ]);

        $document = Document::create([
            'number' => '001/TEST/2026',
            'subject' => 'Surat Pengujian Peminjaman Fisik',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'folder_id' => $folder->id,
            'document_date' => now(),
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(DocumentLoanIndex::class)
            ->call('openLoanModal', $document->id)
            ->set('borrowDate', now()->format('Y-m-d'))
            ->set('expectedReturnDate', now()->addDays(5)->format('Y-m-d'))
            ->set('purpose', 'Pemeriksaan audit KAP internal kantor')
            ->call('submitLoanRequest')
            ->assertDispatched('open-modal', 'modal-loan-ticket');

        $this->assertDatabaseHas('document_loans', [
            'document_id' => $document->id,
            'user_id' => $user->id,
            'purpose' => 'Pemeriksaan audit KAP internal kantor',
            'status' => 'requested',
        ]);

        $loan = DocumentLoan::where('document_id', $document->id)->first();
        $this->assertNotNull($loan);
        $this->assertStringStartsWith('TIKET-' . date('Ym') . '-', $loan->loan_code);
    }

    public function test_officer_can_approve_and_return_loan()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $user = User::where('id', '!=', $superadmin->id)->first() ?? $superadmin;

        $document = Document::create([
            'number' => '002/TEST/2026',
            'subject' => 'Surat Pengujian Sirkulasi Tapersip',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now(),
            'created_by' => $user->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $user->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(7),
            'purpose' => 'Urusan persidangan',
            'status' => 'requested',
        ]);

        $this->assertTrue($document->loans()->whereIn('status', ['requested', 'borrowed'])->exists());

        // 1. Officer Serahkan Berkas (approve)
        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('approveLoan', $loan->id);

        $loan->refresh();
        $this->assertEquals('borrowed', $loan->status);
        $this->assertEquals($superadmin->id, $loan->officer_id);
        $this->assertTrue($document->isCurrentlyBorrowed());

        // 2. Officer Terima dan Kembalikan ke Loker
        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->set('selectedLoanId', $loan->id)
            ->set('returnNotes', 'Berkas dikembalikan lengkap dan rapi')
            ->call('confirmReturn');

        $loan->refresh();
        $this->assertEquals('returned', $loan->status);
        $this->assertNotNull($loan->returned_date);
        $this->assertEquals('Berkas dikembalikan lengkap dan rapi', $loan->notes);
        $this->assertFalse($document->isCurrentlyBorrowed());

        // 3. Memastikan setelah status 'returned' (Closed), kolom Tindakan Petugas berubah menjadi '-'
        $component = Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('setTab', 'circulation');

        $component->assertSeeHtml('<span class="text-slate-400 font-mono text-sm inline-block select-none pr-3">-</span>');
    }

    public function test_regular_user_without_loan_circulation_permission_cannot_see_or_access_circulation_tab()
    {
        $role = Role::create([
            'name' => 'Staf Biasa Tanpa Kepatuhan',
            'status' => 'active',
            'module_visibility' => ['document_borrowing'], // Hanya punya akses modul pencarian, tanpa sirkulasi
        ]);

        $user = User::create([
            'name' => 'Staf Biasa',
            'username' => 'stafbiasa',
            'email' => 'staf@arsip.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $document = Document::create([
            'number' => '003/TEST/2026',
            'subject' => 'Surat Uji Proteksi Sirkulasi',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now(),
            'created_by' => $user->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $user->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(3),
            'purpose' => 'Uji Coba',
            'status' => 'requested',
        ]);

        // 1. Memastikan tab Sirkulasi & Persetujuan Tapersip TIDAK muncul di tampilan staf biasa
        $component = Livewire::actingAs($user)
            ->test(DocumentLoanIndex::class);

        $this->assertFalse($component->get('canManageCirculation'));
        $component->assertDontSeeText('Sirkulasi & Persetujuan Tapersip');

        // 2. Jika mencoba memaksa setTab ke 'circulation', otomatis dikembalikan ke 'catalog'
        $component->call('setTab', 'circulation');
        $this->assertEquals('catalog', $component->get('activeTab'));

        // 3. Jika mencoba memanggil aksi persetujuan secara paksa, ditolak dengan 403 Forbidden
        $component->call('approveLoan', $loan->id)
            ->assertStatus(403);
    }

    public function test_user_with_loan_circulation_permission_can_access_and_manage_circulation()
    {
        $role = Role::create([
            'name' => 'Petugas Sirkulasi Kepatuhan',
            'status' => 'active',
            'module_visibility' => ['document_borrowing', 'loan_circulation'],
        ]);

        $officer = User::create([
            'name' => 'Petugas Kepatuhan',
            'username' => 'petugaskepatuhan',
            'email' => 'officer@arsip.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $document = Document::create([
            'number' => '004/TEST/2026',
            'subject' => 'Surat Uji Akses Petugas Kepatuhan',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now(),
            'created_by' => $officer->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $officer->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Audit Kepatuhan',
            'status' => 'requested',
        ]);

        // Memastikan tab Sirkulasi muncul dan bisa diproses
        $component = Livewire::actingAs($officer)
            ->test(DocumentLoanIndex::class);

        $this->assertTrue($component->get('canManageCirculation'));
        $component->assertSee('Sirkulasi & Persetujuan Tapersip', false)
            ->call('setTab', 'circulation');

        $this->assertEquals('circulation', $component->get('activeTab'));

        // Petugas berhasil menyetujui peminjaman
        $component->call('approveLoan', $loan->id);
        $loan->refresh();
        $this->assertEquals('borrowed', $loan->status);
        $this->assertEquals($officer->id, $loan->officer_id);
    }

    public function test_user_without_document_borrowing_permission_cannot_see_catalog_and_my_loans_tabs()
    {
        $role = Role::create([
            'name' => 'Petugas Sirkulasi Saja',
            'status' => 'active',
            'module_visibility' => ['loan_circulation'], // Hanya sirkulasi tanpa pinjam/katalog
        ]);

        $officer = User::create([
            'name' => 'Petugas Khusus Sirkulasi',
            'username' => 'sirkulasisaja',
            'email' => 'sirkulasi@arsip.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $component = Livewire::actingAs($officer)
            ->test(DocumentLoanIndex::class);

        // canBorrow harus false
        $this->assertFalse($component->get('canBorrow'));
        $this->assertTrue($component->get('canManageCirculation'));

        // Tab Katalog & Pinjaman Saya tidak muncul
        $component->assertDontSee('Katalog & Cari Surat', false);
        $component->assertDontSee('Pinjaman Saya');
        $component->assertSee('Sirkulasi & Persetujuan Tapersip', false);

        // Tab aktif awal otomatis dialihkan ke 'circulation'
        $this->assertEquals('circulation', $component->get('activeTab'));

        // Jika mencoba memaksa setTab ke 'catalog' atau 'my_loans', ditolak
        $component->call('setTab', 'catalog');
        $this->assertEquals('circulation', $component->get('activeTab'));

        $component->call('setTab', 'my_loans');
        $this->assertEquals('circulation', $component->get('activeTab'));
    }

    public function test_user_with_document_borrowing_permission_can_see_catalog_and_my_loans_tabs()
    {
        $role = Role::create([
            'name' => 'Peminjam Saja',
            'status' => 'active',
            'module_visibility' => ['document_borrowing'],
        ]);

        $borrower = User::create([
            'name' => 'Staf Peminjam',
            'username' => 'stafpeminjam',
            'email' => 'peminjam@arsip.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $component = Livewire::actingAs($borrower)
            ->test(DocumentLoanIndex::class);

        $this->assertTrue($component->get('canBorrow'));
        $this->assertFalse($component->get('canManageCirculation'));

        $component->assertSee('Katalog & Cari Surat', false);
        $component->assertSee('Pinjaman Saya');
        $component->assertDontSee('Sirkulasi & Persetujuan Tapersip', false);
        $this->assertEquals('catalog', $component->get('activeTab'));
    }

    public function test_user_can_cancel_their_own_pending_loan_request()
    {
        $role = Role::create([
            'name' => 'Staf Peminjam Aktif',
            'status' => 'active',
            'module_visibility' => ['document_borrowing'],
        ]);

        $borrower = User::create([
            'name' => 'Staf Pembatal',
            'username' => 'stafpembatal',
            'email' => 'pembatal@arsip.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $otherUser = User::create([
            'name' => 'Staf Lain',
            'username' => 'staflain',
            'email' => 'lain@arsip.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $document = Document::create([
            'number' => '005/TEST/2026',
            'subject' => 'Surat Uji Pembatalan Permohonan Pinjam',
            'type' => 'incoming',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now(),
            'created_by' => $borrower->id,
        ]);

        $loan = DocumentLoan::create([
            'loan_code' => DocumentLoan::generateTicketCode(),
            'document_id' => $document->id,
            'user_id' => $borrower->id,
            'borrow_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'purpose' => 'Uji Batal Pinjam',
            'status' => 'requested',
        ]);

        // 1. User lain tidak boleh membatalkan
        Livewire::actingAs($otherUser)
            ->test(DocumentLoanIndex::class)
            ->call('cancelLoanRequest', $loan->id)
            ->assertStatus(403);

        // 2. Pemilik sah berhasil membatalkan permohonan
        Livewire::actingAs($borrower)
            ->test(DocumentLoanIndex::class)
            ->call('cancelLoanRequest', $loan->id)
            ->assertDispatched('notify');

        $loan->refresh();
        $this->assertEquals('cancelled', $loan->status);
        $this->assertEquals('Dibatalkan oleh pemohon', $loan->notes);

        // Pastikan audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'user_id' => $borrower->id,
        ]);
    }
}
