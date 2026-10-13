<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Unit;
use App\Models\Position;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UnitSeeder::class,
            PositionSeeder::class,
            ClassificationSeeder::class,
            DocumentTypeSeeder::class,
        ]);

        $superadminRole = Role::where('name', 'Superadmin')->first();
        $itUnit = Unit::where('code', 'IT')->first();
        $direkturPosition = Position::where('name', 'Direktur')->first();

        // Use updateOrCreate so it doesn't fail on second run
        User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'IT Superadmin',
                'email' => 'superadmin@tapersip.local',
                'password' => bcrypt('password'),
                'role_id' => $superadminRole->id,
                'unit_id' => $itUnit->id,
                'position_id' => $direkturPosition->id,
                'status' => 'active',
            ]
        );
    }
}
