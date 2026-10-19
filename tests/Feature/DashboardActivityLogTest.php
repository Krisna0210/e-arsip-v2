<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Classification;
use App\Models\Document;
use App\Models\DocumentLoan;
use App\Models\DocumentType;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Livewire\Dashboard;
use App\Livewire\Registry\Outgoing\Create as OutgoingCreate;
use App\Livewire\Registry\DocumentLoanIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_activity_is_recorded_when_outgoing_letter_is_booked()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $docType = DocumentType::where('name', 'like', '%Surat Keluar%')->first();
        $unit = Unit::where('type', 'division')->first() ?? Unit::first();
        $classification = Classification::whereNotNull('parent_id')->first() ?? Classification::first();

        Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class)
            ->set('document_type_id', $docType->id)
            ->set('unit_id', $unit->id)
            ->set('signer_type', 'pimpinan_unit')
            ->set('recipient_name', 'Kementerian BUMN')
            ->set('subject', 'Uji Coba Audit Trail Booking Surat')
            ->set('classification_id', $classification->id)
            ->set('document_date', date('Y-m-d'))
            ->set('sifat', 'biasa')
            ->call('bookNumber')
            ->assertHasNoErrors();

        $log = AuditLog::where('user_id', $superadmin->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Booking Nomor Surat Keluar', $log->action);
        $this->assertEquals('Uji Coba Audit Trail Booking Surat', $log->metadata['subject']);
    }

    public function test_staff_only_sees_their_own_activities_on_dashboard()
    {
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();

        $userA = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        $userB = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        // Activity by User A
        AuditLog::record(
            action: 'Aktivitas User A',
            documentId: null,
            metadata: [],
            userId: $userA->id
        );

        // Activity by User B
        AuditLog::record(
            action: 'Aktivitas User B',
            documentId: null,
            metadata: [],
            userId: $userB->id
        );

        // User A tests Dashboard
        $component = Livewire::actingAs($userA)->test(Dashboard::class);
        $component->assertViewHas('canViewAllActivities', false);
        $component->assertViewHas('recentActivities', function ($activities) use ($userA) {
            return $activities->count() === 1 && $activities->first()->user_id === $userA->id;
        });
        $component->assertSee('Aktivitas User A');
        $component->assertDontSee('Aktivitas User B');
    }

    public function test_superadmin_sees_all_activities_on_dashboard()
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $roleUser = Role::where('name', 'User')->first();
        $unit = Unit::first();

        $userA = User::factory()->create([
            'role_id' => $roleUser->id,
            'unit_id' => $unit->id,
        ]);

        AuditLog::record(action: 'Aktivitas Spesifik User A', documentId: null, metadata: [], userId: $userA->id);
        AuditLog::record(action: 'Aktivitas Superadmin', documentId: null, metadata: [], userId: $superadmin->id);

        $component = Livewire::actingAs($superadmin)->test(Dashboard::class);
        $component->assertViewHas('canViewAllActivities', true);
        $component->assertSee('Seluruh Sistem');
        $component->assertSee('Aktivitas Spesifik User A');
        $component->assertSee('Aktivitas Superadmin');
    }

    public function test_tapersip_sees_all_activities_on_dashboard()
    {
        $unit = Unit::first();

        // Create a role with archive_queue access
        $tapersipRole = Role::create([
            'name' => 'Petugas Tapersip',
            'description' => 'Staff Pengarsipan',
            'status' => 'active',
            'module_visibility' => ['archive_queue', 'dashboard'],
        ]);

        $tapersipUser = User::factory()->create([
            'role_id' => $tapersipRole->id,
            'unit_id' => $unit->id,
        ]);

        $otherUser = User::factory()->create(['unit_id' => $unit->id]);

        AuditLog::record(action: 'Surat Masuk dari Rekanan Luar', documentId: null, metadata: [], userId: $otherUser->id);

        $component = Livewire::actingAs($tapersipUser)->test(Dashboard::class);
        $component->assertViewHas('canViewAllActivities', true);
        $component->assertSee('Seluruh Sistem');
        $component->assertSee('Surat Masuk dari Rekanan Luar');
    }
}
