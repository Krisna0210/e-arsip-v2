<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$document = App\Models\Document::with('classification', 'unit', 'documentType')->find(5);

$year = date('Y');
$countThisYear = App\Models\Document::where('type', $document->type)
    ->whereYear('document_date', $year)
    ->where('status', 'verified')
    ->count() + 1;

$sequenceNo = str_pad($countThisYear, 3, '0', STR_PAD_LEFT);
$classCode = $document->classification->code ?? 'UM';
$unitCode = $document->unit->code ?? 'ORG';

$format = '{NO}/{CLASS}/{UNIT}/{YYYY}';
if ($document->documentType && $document->documentType->numbering_format) {
    $format = $document->documentType->numbering_format;
}

$generatedNumber = str_replace(
    ['{NO}', '{CLASS}', '{UNIT}', '{YYYY}'],
    [$sequenceNo, $classCode, $unitCode, $year],
    $format
);

echo "Success: " . $generatedNumber;
