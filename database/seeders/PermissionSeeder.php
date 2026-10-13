<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['code' => 'view', 'name' => 'View Document'],
            ['code' => 'download', 'name' => 'Download Document'],
            ['code' => 'create', 'name' => 'Create Document'],
            ['code' => 'edit', 'name' => 'Edit Document'],
            ['code' => 'verify', 'name' => 'Verify Document'],
            ['code' => 'approve', 'name' => 'Approve Document'],
            ['code' => 'disposition', 'name' => 'Disposition'],
            ['code' => 'archive', 'name' => 'Archive Document'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['code' => $permission['code']], $permission);
        }
    }
}
