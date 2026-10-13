<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Superadmin', 'description' => 'System Administrator with full access', 'status' => 'active'],
            ['name' => 'Admin', 'description' => 'Application Administrator', 'status' => 'active'],
            ['name' => 'Manager', 'description' => 'Department Manager', 'status' => 'active'],
            ['name' => 'User', 'description' => 'Regular User', 'status' => 'active'],
        ];

        foreach ($roles as $roleData) {
            $role = Role::updateOrCreate(['name' => $roleData['name']], $roleData);
            
            // For superadmin, attach all permissions with all sifat
            if ($role->name === 'Superadmin') {
                $permissions = Permission::all();
                $sifats = ['biasa', 'penting', 'rahasia'];
                foreach ($permissions as $permission) {
                    foreach ($sifats as $sifat) {
                        // Use syncWithoutDetaching to avoid duplicates
                        $role->permissions()->attach($permission->id, ['sifat' => $sifat]);
                    }
                }
            }
        }
    }
}
