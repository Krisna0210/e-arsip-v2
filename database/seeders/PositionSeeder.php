<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Direktur', 'level' => 1],
            ['name' => 'Manajer', 'level' => 2],
            ['name' => 'Supervisor', 'level' => 3],
            ['name' => 'Staff', 'level' => 4],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(['name' => $position['name']], $position);
        }
    }
}
