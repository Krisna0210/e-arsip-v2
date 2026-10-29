<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::all()->keyBy('code');

        $rolesData = [
            [
                'name' => 'Superadmin',
                'description' => 'System Administrator with full access',
                'status' => 'active',
                'module_visibility' => null,
                'permissions' => [
                    ['code' => 'view', 'sifat' => 'biasa'],
                    ['code' => 'view', 'sifat' => 'penting'],
                    ['code' => 'view', 'sifat' => 'rahasia'],
                    ['code' => 'download', 'sifat' => 'biasa'],
                    ['code' => 'download', 'sifat' => 'penting'],
                    ['code' => 'download', 'sifat' => 'rahasia'],
                    ['code' => 'create', 'sifat' => 'biasa'],
                    ['code' => 'create', 'sifat' => 'penting'],
                    ['code' => 'create', 'sifat' => 'rahasia'],
                    ['code' => 'edit', 'sifat' => 'biasa'],
                    ['code' => 'edit', 'sifat' => 'penting'],
                    ['code' => 'edit', 'sifat' => 'rahasia'],
                    ['code' => 'verify', 'sifat' => 'biasa'],
                    ['code' => 'verify', 'sifat' => 'penting'],
                    ['code' => 'verify', 'sifat' => 'rahasia'],
                    ['code' => 'approve', 'sifat' => 'biasa'],
                    ['code' => 'approve', 'sifat' => 'penting'],
                    ['code' => 'approve', 'sifat' => 'rahasia'],
                    ['code' => 'disposition', 'sifat' => 'biasa'],
                    ['code' => 'disposition', 'sifat' => 'penting'],
                    ['code' => 'disposition', 'sifat' => 'rahasia'],
                    ['code' => 'archive', 'sifat' => 'biasa'],
                    ['code' => 'archive', 'sifat' => 'penting'],
                    ['code' => 'archive', 'sifat' => 'rahasia'],
                ],
            ],
            [
                'name' => 'Admin',
                'description' => 'Application Administrator',
                'status' => 'active',
                'module_visibility' => null,
                'permissions' => [],
            ],
            [
                'name' => 'Manager',
                'description' => 'Department Manager',
                'status' => 'active',
                'module_visibility' => [
                    'dashboard',
                    'in_out_mail',
                    'internal_memo',
                    'archive_queue',
                    'document_borrowing',
                    'classification',
                    'destruction',
                    'approve_destruction',
                    'audit',
                ],
                'permissions' => [
                    ['code' => 'view', 'sifat' => 'biasa'],
                    ['code' => 'view', 'sifat' => 'penting'],
                    ['code' => 'view', 'sifat' => 'rahasia'],
                ],
            ],
            [
                'name' => 'User',
                'description' => 'Regular User',
                'status' => 'active',
                'module_visibility' => [
                    'dashboard',
                    'input_in_out',
                    'input_internal',
                    'input_outgoing',
                ],
                'permissions' => [
                    ['code' => 'create', 'sifat' => 'biasa'],
                    ['code' => 'create', 'sifat' => 'penting'],
                    ['code' => 'create', 'sifat' => 'rahasia'],
                ],
            ],
            [
                'name' => 'AdminTapersip',
                'description' => 'Petugas Tata Persuratan & Kearsipan',
                'status' => 'active',
                'module_visibility' => [
                    'dashboard',
                    'input_in_out',
                    'input_internal',
                    'in_out_mail',
                    'internal_memo',
                    'classification',
                    'destruction',
                    'approve_destruction',
                    'audit',
                    'archive_queue',
                    'input_incoming',
                    'input_outgoing',
                    'document_borrowing',
                    'loan_circulation',
                ],
                'permissions' => [
                    ['code' => 'view', 'sifat' => 'biasa'],
                    ['code' => 'view', 'sifat' => 'penting'],
                    ['code' => 'view', 'sifat' => 'rahasia'],
                    ['code' => 'download', 'sifat' => 'biasa'],
                    ['code' => 'download', 'sifat' => 'penting'],
                    ['code' => 'download', 'sifat' => 'rahasia'],
                    ['code' => 'create', 'sifat' => 'biasa'],
                    ['code' => 'create', 'sifat' => 'penting'],
                    ['code' => 'create', 'sifat' => 'rahasia'],
                    ['code' => 'edit', 'sifat' => 'biasa'],
                    ['code' => 'edit', 'sifat' => 'penting'],
                    ['code' => 'edit', 'sifat' => 'rahasia'],
                    ['code' => 'verify', 'sifat' => 'biasa'],
                    ['code' => 'verify', 'sifat' => 'penting'],
                    ['code' => 'verify', 'sifat' => 'rahasia'],
                    ['code' => 'archive', 'sifat' => 'biasa'],
                    ['code' => 'archive', 'sifat' => 'penting'],
                    ['code' => 'archive', 'sifat' => 'rahasia'],
                ],
            ],
            [
                'name' => 'Staf',
                'description' => 'Staf Pembuat Naskah',
                'status' => 'active',
                'module_visibility' => [
                    'dashboard',
                    'input_outgoing',
                    'input_internal',
                    'document_borrowing',
                ],
                'permissions' => [
                    ['code' => 'create', 'sifat' => 'biasa'],
                    ['code' => 'create', 'sifat' => 'penting'],
                ],
            ],
        ];

        foreach ($rolesData as $data) {
            $role = Role::updateOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'status' => $data['status'],
                    'module_visibility' => $data['module_visibility'],
                ]
            );

            // Detach and re-attach permissions to avoid duplicates
            $role->permissions()->detach();

            foreach ($data['permissions'] as $pData) {
                if (isset($permissions[$pData['code']])) {
                    $role->permissions()->attach($permissions[$pData['code']]->id, ['sifat' => $pData['sifat']]);
                }
            }
        }
    }
}
