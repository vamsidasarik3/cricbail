<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\QuizAttempt;

$past = now()->subSeconds(45);
$a = QuizAttempt::create([
    'quiz_id' => 2,
    'started_at' => $past,
    'status' => 'in_progress'
]);

echo "Created started_at: " . $a->started_at . PHP_EOL;
echo "now(): " . now() . PHP_EOL;
echo "now()->diffInSeconds(\$a->started_at): " . now()->diffInSeconds($a->started_at) . PHP_EOL;
echo "now()->diffInSeconds(\$a->started_at, false): " . now()->diffInSeconds($a->started_at, false) . PHP_EOL;
echo "\$a->started_at->diffInSeconds(now(), false): " . $a->started_at->diffInSeconds(now(), false) . PHP_EOL;
echo "\$a->remaining_seconds: " . $a->remaining_seconds . PHP_EOL;

$a->delete();
