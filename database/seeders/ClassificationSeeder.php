<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classification;

class ClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $classifications = [
            ['code' => 'PP', 'subject' => 'Pembangunan / Pengembangan', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PP.00', 'subject' => 'Investasi', 'parent_code' => 'PP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PP.01', 'subject' => 'Pembangunan', 'parent_code' => 'PP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PP.02', 'subject' => 'Pengembangan', 'parent_code' => 'PP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PU', 'subject' => 'Pengembangan Usaha', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PU.00', 'subject' => 'Survey', 'parent_code' => 'PU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PU.01', 'subject' => 'Kerjasama', 'parent_code' => 'PU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PU.02', 'subject' => 'K3', 'parent_code' => 'PU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PM', 'subject' => 'Pemeliharaan', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PM.00', 'subject' => 'Jalan & Jembatan', 'parent_code' => 'PM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PM.01', 'subject' => 'Bangunan', 'parent_code' => 'PM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PM.02', 'subject' => 'Lingkungan', 'parent_code' => 'PM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PM.03', 'subject' => 'Peralatan', 'parent_code' => 'PM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PM.04', 'subject' => 'Penerangan Jalan', 'parent_code' => 'PM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PM.05', 'subject' => 'Sarana Lalu Lintas', 'parent_code' => 'PM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PM.06', 'subject' => 'Sarana Elektronik', 'parent_code' => 'PM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'MGT', 'subject' => 'Manajemen Gerbang Tol', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'MGT.00', 'subject' => 'Tarif Tol', 'parent_code' => 'MGT', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'MGT.01', 'subject' => 'Kartu Tol', 'parent_code' => 'MGT', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'MGT.02', 'subject' => 'Pendapatan Tol', 'parent_code' => 'MGT', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'MGT.03', 'subject' => 'Laporan Lalu Lintas & Pendapatan', 'parent_code' => 'MGT', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'LL', 'subject' => 'Lalu Lintas', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'LL.00', 'subject' => 'Pelayanan', 'parent_code' => 'LL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'LL.01', 'subject' => 'Kecelakaan ', 'parent_code' => 'LL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'LL.02', 'subject' => 'Pelanggaran ', 'parent_code' => 'LL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TK', 'subject' => 'Teknik', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TK.00', 'subject' => 'Verifikasi Teknik', 'parent_code' => 'TK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TK.01', 'subject' => 'Standar Pelayanan Minimal', 'parent_code' => 'TK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TK.02', 'subject' => ' Inventarisasi Aset Jalan Tol', 'parent_code' => 'TK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'DL', 'subject' => 'Pendidikan & Pelatihan', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'DL.00', 'subject' => 'Program Diklat', 'parent_code' => 'DL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'DL.01', 'subject' => 'Kurikulum & Silabus', 'parent_code' => 'DL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'DL.02', 'subject' => 'Bantuan Tenaga', 'parent_code' => 'DL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'DL.03', 'subject' => 'Penyelenggara Diklat', 'parent_code' => 'DL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'DL.04', 'subject' => 'Seminar & Simposium', 'parent_code' => 'DL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'DL.05', 'subject' => 'Magang', 'parent_code' => 'DL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HK', 'subject' => 'Hukum', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HK.00', 'subject' => 'Produk Hukum Internal', 'parent_code' => 'HK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HK.01', 'subject' => 'Dokumentasi Hukum', 'parent_code' => 'HK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HK.02', 'subject' => 'Perijinan', 'parent_code' => 'HK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HK.03', 'subject' => 'Bantuan Hukum', 'parent_code' => 'HK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HK.04', 'subject' => 'Kontrak', 'parent_code' => 'HK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HK.05', 'subject' => 'RUPS', 'parent_code' => 'HK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HK.06', 'subject' => 'Sertifikat', 'parent_code' => 'HK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HK.07', 'subject' => 'Asuransi', 'parent_code' => 'HK', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HM', 'subject' => 'Hubungan Masyarakat', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HM.00', 'subject' => 'Sosialisasi & Publikasi', 'parent_code' => 'HM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HM.01', 'subject' => 'Dokumentasi & Perpustakaan', 'parent_code' => 'HM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HM.02', 'subject' => 'Protokoler', 'parent_code' => 'HM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HM.03', 'subject' => 'Kunjungan', 'parent_code' => 'HM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HM.04', 'subject' => 'Penerbitan & Percetakan', 'parent_code' => 'HM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'HM.05', 'subject' => 'Bantuan Sosial', 'parent_code' => 'HM', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP', 'subject' => 'Kepegawaian', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.00', 'subject' => 'Perencanaan / Formasi', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.01', 'subject' => 'Penerimaan Pegawai', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.02', 'subject' => 'Pengangkatan & Penempatan', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.03', 'subject' => 'Analisa Pengawai', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.04', 'subject' => 'Alih Tugas / Rotasi / Mutasi', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.05', 'subject' => 'Pendelegasian Wewenang', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.06', 'subject' => 'Penugasan', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.07', 'subject' => 'Data Pegawai', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.08', 'subject' => 'Pembinaan Pegawai', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.09', 'subject' => 'Kesejahteraan', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.10', 'subject' => 'Pemberhentian Pegawai', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KP.11', 'subject' => 'Hubungan Industrial', 'parent_code' => 'KP', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU', 'subject' => 'Keuangan', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.00', 'subject' => 'Penetapan Anggaran', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.01', 'subject' => 'Revisi Anggaran', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.02', 'subject' => 'Realisasi Anggaran', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.03', 'subject' => 'Verifikasi', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.04', 'subject' => 'Bukti Transaksi', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.05', 'subject' => 'Tagihan', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.06', 'subject' => 'Daftar Piutang', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.07', 'subject' => 'Pajak', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.08', 'subject' => 'Aktiva Tetap', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.09', 'subject' => 'Tuntutan Ganti Rugi', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.10', 'subject' => 'Surat Berharga', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.11', 'subject' => 'Pendapatan', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.12', 'subject' => 'Perbankan', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'KU.13', 'subject' => 'Laporan Keuangan', 'parent_code' => 'KU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'OT', 'subject' => 'Organisasi & Tata Laksana', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'OT.00', 'subject' => 'Organisasi', 'parent_code' => 'OT', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'OT.01', 'subject' => 'Pembakuan Sistem Kerja', 'parent_code' => 'OT', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'OT.02', 'subject' => 'Perencanaan Program Kerja', 'parent_code' => 'OT', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'OT.03', 'subject' => 'Laporan', 'parent_code' => 'OT', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PW', 'subject' => 'Pengawasan ', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PW.00', 'subject' => 'Internal', 'parent_code' => 'PW', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PW.01', 'subject' => 'Eksternal', 'parent_code' => 'PW', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PW.02', 'subject' => 'Laporan', 'parent_code' => 'PW', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PL', 'subject' => 'Perlengkapan', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PL.00', 'subject' => 'Rencana Kebutuhan', 'parent_code' => 'PL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PL.01', 'subject' => 'Penawaran', 'parent_code' => 'PL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PL.02', 'subject' => 'Pengadaan', 'parent_code' => 'PL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PL.03', 'subject' => 'Penyimpanan & Distribusi', 'parent_code' => 'PL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PL.04', 'subject' => 'Perawatan', 'parent_code' => 'PL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PL.05', 'subject' => 'Inventaris', 'parent_code' => 'PL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'PL.06', 'subject' => 'Penghapusan', 'parent_code' => 'PL', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TU', 'subject' => 'Ketatausahaan', 'parent_code' => null, 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TU.00', 'subject' => 'Persuratan & Kearsipan', 'parent_code' => 'TU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TU.01', 'subject' => 'Telekomunikasi', 'parent_code' => 'TU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TU.02', 'subject' => 'Perjalanan Dinas', 'parent_code' => 'TU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TU.03', 'subject' => 'Rapat', 'parent_code' => 'TU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TU.04', 'subject' => 'Hari dan Jam Kerja', 'parent_code' => 'TU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TU.05', 'subject' => 'Kendaraan Dinas', 'parent_code' => 'TU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
            ['code' => 'TU.06', 'subject' => 'Keamanan dan Ketertiban', 'parent_code' => 'TU', 'active_years' => 0, 'inactive_years' => 0, 'outcome' => 'permanen'],
        ];

        // Tahap 1: Buat atau perbarui seluruh entitas klasifikasi
        foreach ($classifications as $classData) {
            Classification::updateOrCreate(
                ['code' => $classData['code']],
                [
                    'subject' => $classData['subject'],
                    'active_years' => $classData['active_years'],
                    'inactive_years' => $classData['inactive_years'],
                    'outcome' => $classData['outcome'],
                ]
            );
        }

        // Tahap 2: Sambungkan relasi parent_id secara dinamis berdasarkan parent_code
        foreach ($classifications as $classData) {
            if (!empty($classData['parent_code'])) {
                $parent = Classification::where('code', $classData['parent_code'])->first();
                if ($parent) {
                    Classification::where('code', $classData['code'])->update(['parent_id' => $parent->id]);
                }
            } else {
                Classification::where('code', $classData['code'])->update(['parent_id' => null]);
            }
        }
    }
}
