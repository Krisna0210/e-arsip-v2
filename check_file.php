<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$version = App\Models\DocumentVersion::first();
if ($version) {
    $path = storage_path('app/public/' . $version->file_path);
    echo "Path: " . $path . "\n";
    echo "Exists: " . (file_exists($path) ? 'Yes' : 'No') . "\n";
} else {
    echo "No versions found\n";
}
