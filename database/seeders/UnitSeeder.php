<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            // Divisi (Induk)
            ['code' => 'TEK', 'name' => 'Divisi Teknik', 'type' => 'division', 'parent_code' => null, 'status' => 'active'],
            ['code' => 'OPS', 'name' => 'Divisi Operasional', 'type' => 'division', 'parent_code' => null, 'status' => 'active'],
            ['code' => 'SDM', 'name' => 'Divisi Umum,Humas & Hukum', 'type' => 'division', 'parent_code' => null, 'status' => 'active'],
            ['code' => 'KEU', 'name' => 'Divisi Keuangan & Akuntansi', 'type' => 'division', 'parent_code' => null, 'status' => 'active'],

            // Departemen (Sub-Unit)
            ['code' => 'MGTTI', 'name' => 'Manajemen Gerbang Tol & TI', 'type' => 'department', 'parent_code' => 'OPS', 'status' => 'active'],
            ['code' => 'PP', 'name' => 'Pelayanan & Pemeliharaan', 'type' => 'department', 'parent_code' => 'OPS', 'status' => 'active'],
            ['code' => 'HR', 'name' => 'Sumber Daya Manusia', 'type' => 'department', 'parent_code' => 'SDM', 'status' => 'active'],
            ['code' => 'FIN', 'name' => 'Keuangan', 'type' => 'department', 'parent_code' => 'KEU', 'status' => 'active'],
            ['code' => 'AKT', 'name' => 'Akuntansi', 'type' => 'department', 'parent_code' => 'KEU', 'status' => 'active'],
            ['code' => 'HMS', 'name' => 'Humas', 'type' => 'department', 'parent_code' => 'SDM', 'status' => 'active'],
            ['code' => 'HK', 'name' => 'Hukum', 'type' => 'department', 'parent_code' => 'SDM', 'status' => 'active'],
            ['code' => 'UM', 'name' => 'Umum', 'type' => 'department', 'parent_code' => 'SDM', 'status' => 'active'],
            ['code' => 'SEKR', 'name' => 'Kesekretariatan', 'type' => 'department', 'parent_code' => 'SDM', 'status' => 'active'],
        ];

        // 1. Buat atau perbarui entitas unit
        foreach ($units as $u) {
            Unit::updateOrCreate(
                ['code' => $u['code']],
                [
                    'name' => $u['name'],
                    'type' => $u['type'],
                    'status' => $u['status'],
                ]
            );
        }

        // 2. Hubungkan relasi parent_id secara dinamis
        foreach ($units as $u) {
            if (!empty($u['parent_code'])) {
                $parent = Unit::where('code', $u['parent_code'])->first();
                if ($parent) {
                    Unit::where('code', $u['code'])->update(['parent_id' => $parent->id]);
                }
            } else {
                Unit::where('code', $u['code'])->update(['parent_id' => null]);
            }
        }
    }
}
