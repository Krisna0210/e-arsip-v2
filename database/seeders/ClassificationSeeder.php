<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classification;

class ClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $classifications = [
            ['code' => 'HM', 'subject' => 'Hukum & Manajerial', 'active_years' => 5, 'inactive_years' => 5, 'outcome' => 'permanen'],
            ['code' => 'HM.00', 'subject' => 'Peraturan Perusahaan', 'parent_code' => 'HM', 'active_years' => 5, 'inactive_years' => 5, 'outcome' => 'permanen'],
            ['code' => 'KP', 'subject' => 'Kepegawaian', 'active_years' => 2, 'inactive_years' => 3, 'outcome' => 'musnah'],
        ];

        foreach ($classifications as $classData) {
            $parentCode = $classData['parent_code'] ?? null;
            unset($classData['parent_code']);
            
            $classification = Classification::updateOrCreate(['code' => $classData['code']], $classData);
            
            if ($parentCode) {
                $parent = Classification::where('code', $parentCode)->first();
                if ($parent) {
                    $classification->parent_id = $parent->id;
                    $classification->save();
                }
            }
        }
    }
}
