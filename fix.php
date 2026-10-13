<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$incomingDocs = App\Models\Document::where('type', 'incoming')->where('status', 'pending_verification')->get();
$verifierId = App\Models\User::whereHas('role', function($q) { $q->where('name', 'Superadmin'); })->first()->id ?? 1;

foreach ($incomingDocs as $doc) {
    if (!App\Models\WorkflowInstance::where('document_id', $doc->id)->exists()) {
        $workflow = App\Models\WorkflowInstance::create([
            'document_id' => $doc->id,
            'workflow_type' => 'surat_masuk_verification',
            'status' => 'active',
            'current_step_sequence' => 1,
        ]);

        App\Models\WorkflowStep::create([
            'workflow_instance_id' => $workflow->id,
            'step_type' => 'verification',
            'sequence' => 1,
            'status' => 'active',
            'assignee_type' => App\Models\User::class,
            'assignee_id' => $verifierId,
            'started_at' => now(),
        ]);
        echo "Fixed Doc ID: " . $doc->id . "\n";
    }
}
