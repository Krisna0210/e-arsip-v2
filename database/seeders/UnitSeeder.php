<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'IT', 'name' => 'Information Technology', 'status' => 'active'],
            ['code' => 'HRD', 'name' => 'Human Resources', 'status' => 'active'],
            ['code' => 'FIN', 'name' => 'Finance', 'status' => 'active'],
            ['code' => 'LGL', 'name' => 'Legal', 'status' => 'active'],
            ['code' => 'OPS', 'name' => 'Operasional', 'status' => 'active'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['code' => $unit['code']], $unit);
        }
    }
}
