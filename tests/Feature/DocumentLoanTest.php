<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\Role;
use App\Models\Unit;
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
        $response->assertSeeText('Pinjam Berkas Arsip Fisik');
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
            ->assertDispatched('close-modal', 'modal-loan-request')
            ->assertNotDispatched('open-modal', 'modal-loan-ticket')
            ->assertSet('activeTab', 'my_loans')
            ->assertDispatched('loan-status-changed')
            ->assertDispatched('notify');

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
        $component->assertSee('Persetujuan Tapersip', false)
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
        $component->assertDontSee('Cari Surat', false);
        $component->assertDontSee('Pinjaman Saya');
        $component->assertSee('Persetujuan Tapersip', false);

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

        $component->assertSee('Cari Surat', false);
        $component->assertSee('Pinjaman Saya');
        $component->assertDontSee('Persetujuan Tapersip', false);
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
            ->assertDispatched('loan-status-changed')
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

    public function test_catalog_table_displays_specific_document_type_name_for_internal_documents()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = \App\Models\DocumentType::where('name', 'like', '%Surat Perintah%')->first()
            ?? \App\Models\DocumentType::firstOrCreate(['name' => 'Surat Perintah', 'code' => 'SPRIN', 'numbering_format' => '{NO}/SPRIN/{YYYY}']);

        $document = Document::create([
            'number' => '888/SPRIN/LOAN/2026',
            'subject' => 'Surat Perintah Arsip Katalog',
            'type' => 'internal',
            'document_type_id' => $docType->id,
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->assertSee('888/SPRIN/LOAN/2026')
            ->assertSee($docType->name);
    }

    public function test_catalog_table_renders_custom_pagination_view()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // Buat 12 dokumen arsip agar memicu paginasi (> 10 item per halaman)
        for ($i = 1; $i <= 12; $i++) {
            Document::create([
                'number' => "DOC-PAGI-{$i}/2026",
                'subject' => "Surat Uji Paginasi {$i}",
                'type' => 'incoming',
                'sifat' => 'biasa',
                'status' => 'archived',
                'document_date' => now()->subDays($i)->format('Y-m-d'),
                'created_by' => $superadmin->id,
            ]);
        }

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->assertSee('Menampilkan')
            ->assertSee('dari')
            ->assertSee('data')
            ->assertSee('Prev');
    }

    public function test_document_loan_index_mounts_circulation_tab_when_tab_query_param_is_provided()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $this->actingAs($superadmin);

        // Akses dengan query string ?tab=circulation
        $component = Livewire::withQueryParams(['tab' => 'circulation'])
            ->test(DocumentLoanIndex::class);

        $this->assertEquals('circulation', $component->get('activeTab'));
    }

    public function test_regular_user_cannot_open_ticket_modal_and_only_tapersip_can_print()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $role = Role::create([
            'name' => 'Staf Peminjam Biasa',
            'status' => 'active',
            'module_visibility' => ['document_borrowing'], // Tidak punya loan_circulation
        ]);

        $borrower = User::create([
            'name' => 'Staf Pemohon',
            'username' => 'stafpemohon',
            'email' => 'stafpemohon@arsip.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $document = Document::create([
            'number' => '099/DOC/TEST/2026',
            'subject' => 'Surat Uji Proteksi Cetak Tiket',
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
            'purpose' => 'Uji Otorisasi Cetak Tiket',
            'status' => 'requested',
        ]);

        // 1. Staf biasa dilarang membuka modal cetak tiket (403)
        Livewire::actingAs($borrower)
            ->test(DocumentLoanIndex::class)
            ->call('openTicketModal', $loan->id)
            ->assertStatus(403);

        // 2. Petugas Tapersip / Superadmin berhasil membuka modal cetak tiket
        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->call('openTicketModal', $loan->id)
            ->assertDispatched('open-modal', 'modal-loan-ticket');
    }

    public function test_staff_cannot_see_confidential_rahasia_documents_in_catalog()
    {
        $roleBorrower = Role::create([
            'name' => 'Staf Peminjam Rahasia ' . uniqid(),
            'status' => 'active',
            'module_visibility' => ['document_borrowing'],
        ]);
        $unitA = Unit::first();

        $staffUser = User::factory()->create([
            'name' => 'Staf Operasional Biasa',
            'role_id' => $roleBorrower->id,
            'unit_id' => $unitA->id,
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Staf Pimpinan',
            'role_id' => $roleBorrower->id,
            'unit_id' => $unitA->id,
        ]);

        // Dokumen rahasia dibuat oleh orang lain
        $docRahasia = Document::create([
            'number' => '001/RAHASIA/SDM/2026',
            'subject' => 'Laporan Khusus Sanksi Indisipliner Karyawan',
            'type' => 'outgoing',
            'sifat' => 'rahasia',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $otherUser->id,
        ]);

        // Staf biasa tidak boleh melihat dokumen rahasia di katalog
        $component = Livewire::actingAs($staffUser)
            ->test(DocumentLoanIndex::class);

        $component->assertDontSee('001/RAHASIA/SDM/2026')
            ->assertDontSee('Laporan Khusus Sanksi Indisipliner Karyawan');

        // Jika mencoba meminjam via modal secara langsung, ditolak
        $component->call('openLoanModal', $docRahasia->id)
            ->assertDispatched('notify');
    }

    public function test_staff_cannot_see_important_penting_documents_from_other_divisions()
    {
        $roleBorrower = Role::create([
            'name' => 'Staf Peminjam Penting ' . uniqid(),
            'status' => 'active',
            'module_visibility' => ['document_borrowing'],
        ]);
        $divisiOps = Unit::where('type', 'division')->first() ?? Unit::create(['name' => 'Divisi Operasional', 'code' => 'OPS', 'type' => 'division']);
        $divisiKeu = Unit::where('type', 'division')->where('id', '!=', $divisiOps->id)->first() ?? Unit::create(['name' => 'Divisi Keuangan', 'code' => 'KEU', 'type' => 'division']);

        $staffOps = User::factory()->create([
            'name' => 'Budi Staf Ops',
            'role_id' => $roleBorrower->id,
            'unit_id' => $divisiOps->id,
        ]);

        $userKeu = User::factory()->create([
            'name' => 'Andi Staf Keuangan',
            'role_id' => $roleBorrower->id,
            'unit_id' => $divisiKeu->id,
        ]);

        // Surat penting milik Divisi Keuangan
        $docPentingKeu = Document::create([
            'number' => '002/PENTING/KEU/2026',
            'subject' => 'Audit Keuangan Internal Triwulan',
            'type' => 'outgoing',
            'unit_id' => $divisiKeu->id,
            'sifat' => 'penting',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $userKeu->id,
        ]);

        // 1. Staf Operasional TIDAK BISA melihat surat penting Divisi Keuangan
        Livewire::actingAs($staffOps)
            ->test(DocumentLoanIndex::class)
            ->assertDontSee('002/PENTING/KEU/2026')
            ->assertDontSee('Audit Keuangan Internal Triwulan');

        // 2. Staf Keuangan BISA melihat surat penting divisinya sendiri
        Livewire::actingAs($userKeu)
            ->test(DocumentLoanIndex::class)
            ->assertSee('002/PENTING/KEU/2026')
            ->assertSee('Audit Keuangan Internal Triwulan');
    }

    public function test_staff_can_see_ordinary_biasa_documents_across_all_divisions()
    {
        $roleBorrower = Role::create([
            'name' => 'Staf Peminjam Biasa ' . uniqid(),
            'status' => 'active',
            'module_visibility' => ['document_borrowing'],
        ]);
        $divisiOps = Unit::where('type', 'division')->first();
        $divisiKeu = Unit::where('type', 'division')->where('id', '!=', $divisiOps->id)->first() ?? Unit::first();

        $staffOps = User::factory()->create([
            'name' => 'Staf Operasional',
            'role_id' => $roleBorrower->id,
            'unit_id' => $divisiOps->id,
        ]);

        $userKeu = User::factory()->create([
            'name' => 'User Keu',
            'role_id' => $roleBorrower->id,
            'unit_id' => $divisiKeu->id,
        ]);

        $docBiasaKeu = Document::create([
            'number' => '003/BIASA/KEU/2026',
            'subject' => 'Surat Edaran Jam Kerja Puasa',
            'type' => 'outgoing',
            'unit_id' => $divisiKeu->id,
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $userKeu->id,
        ]);

        // Staf divisi lain bebas melihat naskah biasa
        Livewire::actingAs($staffOps)
            ->test(DocumentLoanIndex::class)
            ->assertSee('003/BIASA/KEU/2026')
            ->assertSee('Surat Edaran Jam Kerja Puasa');
    }

    public function test_superadmin_can_see_all_documents_including_rahasia_and_other_divisions_penting()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();

        $docRahasia = Document::create([
            'number' => '999/RAHASIA/ALL/2026',
            'subject' => 'Surat Rahasia Direksi Utama',
            'type' => 'outgoing',
            'unit_id' => $unit->id,
            'sifat' => 'rahasia',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->assertSee('999/RAHASIA/ALL/2026')
            ->assertSee('Surat Rahasia Direksi Utama');
    }

    public function test_staff_cannot_see_important_special_internal_documents_from_different_department_in_same_division()
    {
        $roleBorrower = Role::create([
            'name' => 'Staf Peminjam Dept ' . uniqid(),
            'status' => 'active',
            'module_visibility' => ['document_borrowing'],
        ]);

        // 1. Buat Divisi Induk TI
        $divisiTI = Unit::create([
            'name' => 'Divisi Teknologi Informasi',
            'code' => 'TI',
            'type' => 'division',
        ]);

        // 2. Buat 2 Departemen di bawah Divisi TI yang sama
        $deptDev = Unit::create([
            'name' => 'Departemen Pengembangan Aplikasi',
            'code' => 'TI-DEV',
            'type' => 'department',
            'parent_id' => $divisiTI->id,
        ]);

        $deptInf = Unit::create([
            'name' => 'Departemen Infrastruktur Jaringan',
            'code' => 'TI-INF',
            'type' => 'department',
            'parent_id' => $divisiTI->id,
        ]);

        // 3. User Programmer di Dept Dev & User Network di Dept Inf
        $staffDev = User::factory()->create([
            'name' => 'Fajar Programmer',
            'role_id' => $roleBorrower->id,
            'unit_id' => $deptDev->id,
        ]);

        $staffInf = User::factory()->create([
            'name' => 'Rian Network Engineer',
            'role_id' => $roleBorrower->id,
            'unit_id' => $deptInf->id,
        ]);

        $docTypeMemo = DocumentType::where('name', 'like', '%Memo%')->first()
            ?? DocumentType::firstOrCreate(['name' => 'Memo Internal', 'code' => 'MI', 'numbering_format' => '{NO}/MI/{UNIT}/{YYYY}']);

        // 4. Memo Internal PENTING diterbitkan oleh Departemen Pengembangan Aplikasi
        $memoPentingDev = Document::create([
            'number' => '055/MI/TI-DEV/10/2026',
            'subject' => 'Laporan Kerentanan Keamanan Source Code Aplikasi',
            'type' => 'internal', // Surat Khusus
            'document_type_id' => $docTypeMemo->id,
            'unit_id' => $deptDev->id,
            'sifat' => 'penting',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $staffDev->id,
        ]);

        // 5. Staf Departemen Infrastruktur (satu divisi TI tapi BEDA departemen) TIDAK BISA melihat
        $componentInf = Livewire::actingAs($staffInf)
            ->test(DocumentLoanIndex::class);

        $componentInf->assertDontSee('055/MI/TI-DEV/10/2026')
            ->assertDontSee('Laporan Kerentanan Keamanan Source Code Aplikasi');

        // Upaya membuka modal peminjaman langsung ditolak
        $componentInf->call('openLoanModal', $memoPentingDev->id)
            ->assertDispatched('notify');

        // 6. Staf sesama Departemen Pengembangan Aplikasi BISA melihat dan meminjam
        $otherStaffDev = User::factory()->create([
            'name' => 'Gita UI UX Designer',
            'role_id' => $roleBorrower->id,
            'unit_id' => $deptDev->id,
        ]);

        Livewire::actingAs($otherStaffDev)
            ->test(DocumentLoanIndex::class)
            ->assertSee('055/MI/TI-DEV/10/2026')
            ->assertSee('Laporan Kerentanan Keamanan Source Code Aplikasi');
    }

    public function test_hierarchical_department_isolation_and_division_upward_inheritance_for_important_documents()
    {
        $roleBorrower = Role::create([
            'name' => 'Role Peminjam ' . uniqid(),
            'status' => 'active',
            'module_visibility' => ['document_borrowing'],
        ]);

        // 1. Ambil Divisi Operasional dan Departemen MGTTI dari master data
        $divisiOps = Unit::where('code', 'OPS')->first() ?? Unit::create([
            'name' => 'Divisi Operasional',
            'code' => 'OPS-TEST',
            'type' => 'division',
        ]);

        $deptMgtti = Unit::where('code', 'MGTTI')->first() ?? Unit::create([
            'name' => 'Departemen MGTTI',
            'code' => 'MGTTI-TEST',
            'type' => 'department',
            'parent_id' => $divisiOps->id,
        ]);

        // 2. Buat Departemen Logistik di bawah Divisi Operasional yang sama
        $deptLogistik = Unit::firstOrCreate(
            ['code' => 'LOG-OPS'],
            [
                'name' => 'Departemen Logistik Operasional',
                'type' => 'department',
                'parent_id' => $divisiOps->id,
                'status' => 'active',
            ]
        );

        // 3. User pembuat surat: Krisna di Departemen MGTTI
        $krisna = User::factory()->create([
            'name' => 'Krisna MGTTI',
            'role_id' => $roleBorrower->id,
            'unit_id' => $deptMgtti->id,
        ]);

        // User sesama Departemen MGTTI
        $fellowMgtti = User::factory()->create([
            'name' => 'Rekan Krisna MGTTI',
            'role_id' => $roleBorrower->id,
            'unit_id' => $deptMgtti->id,
        ]);

        // User staf di Departemen Logistik (satu divisi Operasional tapi beda departemen)
        $stafLogistik = User::factory()->create([
            'name' => 'Staf Logistik Operasional',
            'role_id' => $roleBorrower->id,
            'unit_id' => $deptLogistik->id,
        ]);

        // User yang akunnya melekat langsung pada level Divisi Operasional (misal: Kepala Divisi / Sekretariat Divisi)
        $kadivOps = User::factory()->create([
            'name' => 'Kepala Divisi Operasional',
            'role_id' => $roleBorrower->id,
            'unit_id' => $divisiOps->id,
        ]);

        // 4. Buat Surat Keluar PENTING dibuat oleh Krisna (Departemen MGTTI)
        $suratKeluarPenting = Document::create([
            'number' => '01/SK-OPS/X/2026',
            'subject' => 'Surat Keluar Penting Pengadaan Lisensi MGTTI',
            'type' => 'outgoing',
            'unit_id' => $divisiOps->id,
            'sifat' => 'penting',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $krisna->id,
        ]);

        // 5. Buat Surat Khusus PENTING dibuat oleh Krisna (Departemen MGTTI)
        $suratKhususPenting = Document::create([
            'number' => '01/MI/MGTTI/X/2026',
            'subject' => 'Memo Khusus Internal Kerentanan Sistem MGTTI',
            'type' => 'internal',
            'unit_id' => $deptMgtti->id,
            'sifat' => 'penting',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $krisna->id,
        ]);

        // UJI 1: Staf Departemen Logistik TIDAK DAPAT melihat Surat Keluar maupun Surat Khusus buatan Krisna MGTTI
        $testLogistik = Livewire::actingAs($stafLogistik)->test(DocumentLoanIndex::class);
        $testLogistik->assertDontSee('01/SK-OPS/X/2026')
            ->assertDontSee('Surat Keluar Penting Pengadaan Lisensi MGTTI')
            ->assertDontSee('01/MI/MGTTI/X/2026')
            ->assertDontSee('Memo Khusus Internal Kerentanan Sistem MGTTI');

        // Upaya staf Logistik meminjam naskah ditolak oleh sistem keamanan
        $testLogistik->call('openLoanModal', $suratKeluarPenting->id)->assertDispatched('notify');
        $testLogistik->call('openLoanModal', $suratKhususPenting->id)->assertDispatched('notify');

        // UJI 2: Staf sesama Departemen MGTTI BISA melihat dan meminjam
        $testFellow = Livewire::actingAs($fellowMgtti)->test(DocumentLoanIndex::class);
        $testFellow->assertSee('01/SK-OPS/X/2026')
            ->assertSee('Surat Keluar Penting Pengadaan Lisensi MGTTI')
            ->assertSee('01/MI/MGTTI/X/2026')
            ->assertSee('Memo Khusus Internal Kerentanan Sistem MGTTI');

        // UJI 3: Akun level Divisi Operasional (Induk Divisi) BISA melihat dan meminjam seluruh naskah
        $testDivisi = Livewire::actingAs($kadivOps)->test(DocumentLoanIndex::class);
        $testDivisi->assertSee('01/SK-OPS/X/2026')
            ->assertSee('Surat Keluar Penting Pengadaan Lisensi MGTTI')
            ->assertSee('01/MI/MGTTI/X/2026')
            ->assertSee('Memo Khusus Internal Kerentanan Sistem MGTTI');
    }

    public function test_circulation_smart_page_resolver_navigates_to_correct_page_for_highlight_parameter()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $unit = Unit::first();
        $roleBorrower = Role::where('name', 'User')->first();

        $borrower = User::factory()->create([
            'role_id' => $roleBorrower->id,
            'unit_id' => $unit->id,
        ]);

        $document = Document::create([
            'number' => 'DOC-CIRC-PAGE/2026',
            'subject' => 'Surat Pengujian Sirkulasi Multi Page',
            'type' => 'outgoing',
            'sifat' => 'biasa',
            'status' => 'archived',
            'document_date' => now()->format('Y-m-d'),
            'created_by' => $superadmin->id,
        ]);

        // Buat 15 loans dengan waktu berurutan
        // Karena kueri sirkulasi menggunakan latest('created_at') (descending),
        // loan pertama yang dibuat (paling lama) akan berada di halaman ke-2 (posisi ke-15)
        $targetLoan = null;
        for ($i = 1; $i <= 15; $i++) {
            $loan = DocumentLoan::create([
                'loan_code' => "TIKET-PAGE-{$i}",
                'document_id' => $document->id,
                'user_id' => $borrower->id,
                'borrow_date' => now(),
                'expected_return_date' => now()->addDays(5),
                'purpose' => "Uji Sirkulasi Page {$i}",
                'status' => 'requested',
            ]);

            DocumentLoan::where('id', $loan->id)->update([
                'created_at' => now()->subMinutes(30 - $i),
            ]);

            if ($i === 1) {
                $targetLoan = $loan->fresh();
            }
        }

        $this->assertNotNull($targetLoan);

        // Simulasi klik notifikasi dengan query param highlight=loan-{id}
        // Smart Page Resolver harus otomatis membuka tab circulation dan menyajikan halaman 2
        Livewire::withQueryParams(['tab' => 'circulation', 'highlight' => "loan-{$targetLoan->id}"])
            ->actingAs($superadmin)
            ->test(DocumentLoanIndex::class)
            ->assertSet('activeTab', 'circulation')
            ->assertSee($targetLoan->loan_code)
            ->assertSee('id="loan-row-' . $targetLoan->id . '"', false);
    }
}
