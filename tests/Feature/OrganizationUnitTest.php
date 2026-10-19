<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use App\Livewire\Settings\OrganizationIndex;
use App\Livewire\Registry\Outgoing\Create as OutgoingCreate;
use App\Livewire\Registry\Internal\Create as InternalCreate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_filter_units_by_type_division_and_department()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->set('unitTypeFilter', 'division')
            ->assertViewHas('units', function ($units) {
                return $units->every(fn ($u) => $u->type === 'division');
            })
            ->set('unitTypeFilter', 'department')
            ->assertViewHas('units', function ($units) {
                return $units->every(fn ($u) => $u->type === 'department');
            });
    }

    public function test_admin_can_create_division_and_department_with_parent()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Create Division
        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->call('createUnit')
            ->set('unitCode', 'MKT')
            ->set('unitName', 'Divisi Pemasaran')
            ->set('unitType', 'division')
            ->set('unitStatus', 'active')
            ->call('saveUnit')
            ->assertHasNoErrors();

        $division = Unit::where('code', 'MKT')->first();
        $this->assertNotNull($division);
        $this->assertEquals('division', $division->type);
        $this->assertNull($division->parent_id);

        // 2. Create Department under Division
        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->call('createUnit')
            ->set('unitCode', 'MKT-DIG')
            ->set('unitName', 'Departemen Digital Marketing')
            ->set('unitType', 'department')
            ->set('parentUnitId', $division->id)
            ->set('unitStatus', 'active')
            ->call('saveUnit')
            ->assertHasNoErrors();

        $department = Unit::where('code', 'MKT-DIG')->first();
        $this->assertNotNull($department);
        $this->assertEquals('department', $department->type);
        $this->assertEquals($division->id, $department->parent_id);

        // 3. Department validation fails if parent division is missing
        Livewire::actingAs($superadmin)
            ->test(OrganizationIndex::class)
            ->call('createUnit')
            ->set('unitCode', 'MKT-ERR')
            ->set('unitName', 'Departemen Tanpa Induk')
            ->set('unitType', 'department')
            ->set('parentUnitId', null)
            ->call('saveUnit')
            ->assertHasErrors(['parentUnitId']);
    }

    public function test_outgoing_create_only_contains_division_units()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $component = Livewire::actingAs($superadmin)
            ->test(OutgoingCreate::class);

        $units = $component->get('units');
        $this->assertNotEmpty($units);
        foreach ($units as $unit) {
            $this->assertEquals('division', $unit->type, "Unit {$unit->code} harus bertipe division");
        }
    }

    public function test_internal_create_only_contains_department_units()
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $component = Livewire::actingAs($superadmin)
            ->test(InternalCreate::class);

        $units = $component->get('units');
        $this->assertNotEmpty($units);
        foreach ($units as $unit) {
            $this->assertEquals('department', $unit->type, "Unit {$unit->code} harus bertipe department");
        }
    }
}
