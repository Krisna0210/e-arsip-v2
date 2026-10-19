<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class NasHealthService
{
    const CACHE_KEY = 'nas_storage_health_status';
    const CACHE_TTL_SECONDS = 30;

    /**
     * Memeriksa kesehatan dan konektivitas storage NAS.
     *
     * @param bool $forceRefresh
     * @return array
     */
    public static function check(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return self::performCheck();
        });
    }

    /**
     * Eksekusi pengecekan langsung ke filesystem NAS.
     *
     * @return array
     */
    protected static function performCheck(): array
    {
        $checkedAt = now()->format('H:i:s');
        $rootPath = config('filesystems.disks.nas.root', storage_path('app/public'));

        try {
            $disk = Storage::disk('nas');

            // 1. Uji tulis dan baca file detak jantung (heartbeat)
            $testFileName = '.healthcheck_' . bin2hex(random_bytes(4)) . '.tmp';
            $writeSuccess = $disk->put($testFileName, 'nas_healthy_' . time());

            if (!$writeSuccess) {
                return [
                    'online' => false,
                    'status' => 'disconnected',
                    'message' => 'Gagal menulis berkas uji ke direktori NAS.',
                    'error' => 'Disk write test failed',
                    'root_path' => (string) $rootPath,
                    'free_space' => null,
                    'total_space' => null,
                    'used_percent' => null,
                    'checked_at' => $checkedAt,
                ];
            }

            // Hapus file uji segera setelah berhasil
            $disk->delete($testFileName);

            // 2. Baca informasi kapasitas storage jika root path lokal/UNC valid
            $freeSpace = null;
            $totalSpace = null;
            $usedPercent = null;

            if (is_string($rootPath) && is_dir($rootPath)) {
                $freeBytes = @disk_free_space($rootPath);
                $totalBytes = @disk_total_space($rootPath);

                if ($freeBytes !== false && $totalBytes !== false && $totalBytes > 0) {
                    $freeSpace = self::formatBytes($freeBytes);
                    $totalSpace = self::formatBytes($totalBytes);
                    $usedBytes = $totalBytes - $freeBytes;
                    $usedPercent = round(($usedBytes / $totalBytes) * 100, 1);
                }
            }

            return [
                'online' => true,
                'status' => 'connected',
                'message' => 'Penyimpanan NAS Terhubung & Siap',
                'error' => null,
                'root_path' => (string) $rootPath,
                'free_space' => $freeSpace,
                'total_space' => $totalSpace,
                'used_percent' => $usedPercent,
                'checked_at' => $checkedAt,
            ];
        } catch (Throwable $e) {
            return [
                'online' => false,
                'status' => 'disconnected',
                'message' => 'Penyimpanan NAS Terputus atau Tidak Dapat Diakses: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'root_path' => (string) $rootPath,
                'free_space' => null,
                'total_space' => null,
                'used_percent' => null,
                'checked_at' => $checkedAt,
            ];
        }
    }

    /**
     * Format byte menjadi satuan yang mudah dibaca (KB, MB, GB, TB).
     *
     * @param float|int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatBytes($bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
