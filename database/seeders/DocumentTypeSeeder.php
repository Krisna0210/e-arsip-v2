<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Surat Keluar',
                'code' => 'SK',
                'default_sifat' => 'biasa',
                'numbering_format' => '{NO}/{DIR}{UNIT}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Edaran',
                'code' => 'SE',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}/{UNIT}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Perintah',
                'code' => 'SPRIN',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Memo Internal',
                'code' => 'M',
                'default_sifat' => 'biasa',
                'numbering_format' => '{NO}/{KOSUR}-{DIV}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Keputusan',
                'code' => 'KPTS',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Tugas',
                'code' => 'STG',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Pengumuman',
                'code' => 'PENG',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Perjanjian Kerja ',
                'code' => 'SPJK',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'SuratPerjalananDinas',
                'code' => 'SPD',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Berita Acara',
                'code' => 'BA',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Kuasa',
                'code' => 'SKS',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Keterangan',
                'code' => 'SKTR',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Notulen',
                'code' => 'NTL',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Pernyataan',
                'code' => 'SPN',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
            [
                'name' => 'Surat Perintah Kerja',
                'code' => 'SPK',
                'default_sifat' => 'penting',
                'numbering_format' => '{NO}/{KOSUR}-{CLASS}/{MM}/{YYYY}',
                'starting_sequence' => 0,
                'starting_sequence_dir' => 0,
                'starting_sequence_year' => 2026,
            ],
        ];

        foreach ($types as $typeData) {
            DocumentType::updateOrCreate(
                ['name' => $typeData['name']],
                $typeData
            );
        }
    }
}
