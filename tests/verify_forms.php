<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

echo "=== TESTING FORMS & SUBMISSIONS WITH CSRF TOKEN ===" . PHP_EOL;

Session::start();
$token = Session::token();

// 1. Comment Submission with CSRF token
$commentReq = Request::create(
    'http://cricbail.vkrshna.in:8084/news/tactical-evolution-middle-overs-spin-chokes-modern-t20/comment',
    'POST',
    [
        '_token' => $token,
        'visitor_name' => 'Anil Kumble Fan',
        'email' => 'anilfan@example.com',
        'comment_body' => 'Fascinating analysis of the wrist spin trajectory and field adjustments!'
    ]
);
$commentReq->setLaravelSession(Session::driver());

$commentRes = $httpKernel->handle($commentReq);
echo "Comment Submit Response Status: " . $commentRes->getStatusCode() . 
    ($commentRes->getStatusCode() === 302 ? " [PASSED: 302 Redirect with flash]" : " [FAILED]") . PHP_EOL;

$savedComment = Comment::where('visitor_name', 'Anil Kumble Fan')->first();
echo "Comment Stored in DB: " . ($savedComment ? "PASSED (ID: {$savedComment->id}, Status: {$savedComment->status})" : "FAILED") . PHP_EOL;

// 2. Contact Form Submission with CSRF token
$contactReq = Request::create(
    'http://cricbail.vkrshna.in:8084/contact',
    'POST',
    [
        '_token' => $token,
        'name' => 'Sports Reader',
        'email' => 'reader@example.com',
        'subject' => 'Ranji Trophy coverage inquiry',
        'message' => 'Would love to see more coverage of upcoming domestic red ball fixtures.'
    ]
);
$contactReq->setLaravelSession(Session::driver());

$contactRes = $httpKernel->handle($contactReq);
echo "Contact Submit Response Status: " . $contactRes->getStatusCode() . 
    ($contactRes->getStatusCode() === 302 ? " [PASSED: 302 Redirect with flash]" : " [FAILED]") . PHP_EOL;

echo "=== FORMS TEST COMPLETED ===" . PHP_EOL;
