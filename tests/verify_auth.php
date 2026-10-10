<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

echo "--- AUTHENTICATION & AUTHORIZATION SECURITY TESTS ---" . PHP_EOL;

// 1. Test unauthenticated guest visiting /admin/dashboard
$request1 = Request::create('http://cricbail.vkrshna.in:8084/admin/dashboard', 'GET');
$response1 = $kernel->handle($request1);
echo "1. Guest visiting /admin/dashboard: Status " . $response1->getStatusCode() . 
    " (Redirects to: " . $response1->headers->get('Location') . ")" . 
    ($response1->getStatusCode() === 302 && str_contains($response1->headers->get('Location') ?? '', '/admin/login') ? " [PASSED]" : " [FAILED]") . PHP_EOL;
$kernel->terminate($request1, $response1);

// 2. Test regular (non-admin) user attempting admin access
$regularUser = User::firstOrCreate(
    ['email' => 'regular@example.com'],
    [
        'name' => 'Regular Member',
        'password' => Hash::make('MemberSecret123!'),
        'is_admin' => false,
    ]
);

$request2 = Request::create('http://cricbail.vkrshna.in:8084/admin/dashboard', 'GET');
Auth::login($regularUser);
try {
    $response2 = $kernel->handle($request2);
    $statusCode = $response2->getStatusCode();
    echo "2. Non-admin accessing /admin/dashboard: Status " . $statusCode . 
        ($statusCode === 403 ? " [PASSED: 403 FORBIDDEN]" : " [FAILED]") . PHP_EOL;
    $kernel->terminate($request2, $response2);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    echo "2. Non-admin accessing /admin/dashboard: Status " . $e->getStatusCode() . 
        ($e->getStatusCode() === 403 ? " [PASSED: 403 FORBIDDEN]" : " [FAILED]") . PHP_EOL;
}
Auth::logout();

// 3. Test admin user accessing /admin/dashboard
$adminUser = User::where('email', 'admin@cricbail.com')->first();
$request3 = Request::create('http://cricbail.vkrshna.in:8084/admin/dashboard', 'GET');
Auth::login($adminUser);
$response3 = $kernel->handle($request3);
$content3 = $response3->getContent();
echo "3. Admin accessing /admin/dashboard: Status " . $response3->getStatusCode() . 
    (str_contains($content3, 'Administrative Dashboard') ? " [PASSED: Content Verified]" : " [FAILED]") . PHP_EOL;
$kernel->terminate($request3, $response3);
Auth::logout();

// 4. Test admin login page accessibility
$request4 = Request::create('http://cricbail.vkrshna.in:8084/admin/login', 'GET');
$response4 = $kernel->handle($request4);
$content4 = $response4->getContent();
echo "4. Admin login page render: Status " . $response4->getStatusCode() . 
    (str_contains($content4, 'Administrator Login') ? " [PASSED: Login Form Rendered]" : " [FAILED]") . PHP_EOL;
$kernel->terminate($request4, $response4);

echo "--- SECURITY TESTS COMPLETED SUCCESSFULLY ---" . PHP_EOL;
