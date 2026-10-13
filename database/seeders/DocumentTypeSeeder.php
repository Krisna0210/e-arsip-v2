<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Surat Edaran', 'default_sifat' => 'biasa', 'numbering_format' => '{NO}/SE/{UNIT}/{YYYY}'],
            ['name' => 'PKS', 'default_sifat' => 'penting', 'numbering_format' => '{NO}/PKS/{UNIT}/{YYYY}'],
            ['name' => 'Memo Internal', 'default_sifat' => 'biasa', 'numbering_format' => '{NO}/MI/{UNIT}/{YYYY}'],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
