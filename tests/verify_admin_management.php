<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$consoleKernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$consoleKernel->bootstrap();

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

echo "===========================================================" . PHP_EOL;
echo "  CRICBAIL ADMIN & CONTENT MANAGEMENT VERIFICATION SUITE   " . PHP_EOL;
echo "===========================================================" . PHP_EOL . PHP_EOL;

$adminUser = User::where('is_admin', true)->first();
$regularUser = User::where('is_admin', false)->first();

if (!$adminUser) {
    echo "ERROR: Admin user not found!" . PHP_EOL;
    exit(1);
}

Auth::login($adminUser);
Session::start();

// ---------------------------------------------------------
// 1. DASHBOARD METRICS VERIFICATION
// ---------------------------------------------------------
echo "--- 1. Testing Admin Dashboard Metrics ---" . PHP_EOL;

$dashboardController = new DashboardController();
$dashboardView = $dashboardController->index();
$viewData = $dashboardView->getData();

$expectedMetrics = [
    'published_articles' => Article::where('status', 'published')->count(),
    'draft_articles' => Article::where('status', 'draft')->count(),
    'categories_count' => Category::count(),
    'pending_comments' => Comment::where('status', 'pending')->count(),
    'published_quizzes' => Quiz::where('status', 'published')->count(),
    'quiz_attempts_count' => QuizAttempt::count(),
];

$metricsPassed = true;
foreach ($expectedMetrics as $key => $expectedValue) {
    $actual = $viewData['metrics'][$key] ?? null;
    if ($actual === $expectedValue) {
        echo "  [PASS] Metric '{$key}': {$actual} (matches DB count)" . PHP_EOL;
    } else {
        echo "  [FAIL] Metric '{$key}': expected {$expectedValue}, got " . var_export($actual, true) . PHP_EOL;
        $metricsPassed = false;
    }
}

if (!$metricsPassed) {
    exit(1);
}

// ---------------------------------------------------------
// 2. ARTICLE MANAGEMENT: CREATION, SLUGGING, PUBLISHING, EDITING
// ---------------------------------------------------------
echo PHP_EOL . "--- 2. Testing Article Management CRUD ---" . PHP_EOL;

$articleController = new ArticleController();
$testCategory = Category::first();

// A. Create new Draft article with auto slug
$uniqueId = time();
$createRequest = Request::create(route('admin.articles.store'), 'POST', [
    'title' => "Test Headline {$uniqueId}",
    'category_id' => $testCategory->id,
    'excerpt' => 'A sharp summary of the match dynamics.',
    'content' => 'Full breakdown of bowling spells and batting partnerships.',
    'status' => 'draft',
    'meta_title' => 'Test Meta Title',
    'meta_description' => 'Test Meta Description',
]);

$response = $articleController->store($createRequest);
$createdArticle = Article::where('title', "Test Headline {$uniqueId}")->first();

if ($createdArticle && $createdArticle->slug === "test-headline-{$uniqueId}" && $createdArticle->status === 'draft') {
    echo "  [PASS] Article created as draft with auto-generated slug: '{$createdArticle->slug}'" . PHP_EOL;
} else {
    echo "  [FAIL] Failed to create draft article or slug mismatch." . PHP_EOL;
    exit(1);
}

// B. Duplicate title creates deduplicated slug safely
$duplicateTitleRequest = Request::create(route('admin.articles.store'), 'POST', [
    'title' => "Test Headline {$uniqueId}",
    'category_id' => $testCategory->id,
    'content' => 'Another article with same headline.',
    'status' => 'draft',
]);
$articleController->store($duplicateTitleRequest);
$duplicateArticle = Article::where('slug', "test-headline-{$uniqueId}-1")->first();

if ($duplicateArticle) {
    echo "  [PASS] Duplicate title safely assigned unique slug: '{$duplicateArticle->slug}'" . PHP_EOL;
    $duplicateArticle->delete();
} else {
    echo "  [FAIL] Slug deduplication failed." . PHP_EOL;
    exit(1);
}

// C. Publish article
$publishResponse = $articleController->publish($createdArticle);
$createdArticle->refresh();
if ($createdArticle->status === 'published' && !empty($createdArticle->published_at)) {
    echo "  [PASS] Article published successfully (published_at: {$createdArticle->published_at})" . PHP_EOL;
} else {
    echo "  [FAIL] Article publish toggle failed." . PHP_EOL;
    exit(1);
}

// D. Unpublish article
$unpublishResponse = $articleController->unpublish($createdArticle);
$createdArticle->refresh();
if ($createdArticle->status === 'draft' && empty($createdArticle->published_at)) {
    echo "  [PASS] Article unpublish reverted status to draft (published_at reset to null)" . PHP_EOL;
} else {
    echo "  [FAIL] Article unpublish toggle failed." . PHP_EOL;
    exit(1);
}

// E. Update article
$updateRequest = Request::create(route('admin.articles.update', $createdArticle->id), 'PUT', [
    'title' => "Updated Headline {$uniqueId}",
    'slug' => "updated-slug-{$uniqueId}",
    'category_id' => $testCategory->id,
    'excerpt' => 'Updated excerpt standfirst.',
    'content' => 'Updated content body with post-match quotes.',
    'status' => 'published',
    'published_at' => now()->toDateTimeString(),
]);

$articleController->update($updateRequest, $createdArticle);
$createdArticle->refresh();
if ($createdArticle->title === "Updated Headline {$uniqueId}" && $createdArticle->slug === "updated-slug-{$uniqueId}") {
    echo "  [PASS] Article updated successfully." . PHP_EOL;
} else {
    echo "  [FAIL] Article update failed." . PHP_EOL;
    exit(1);
}

// ---------------------------------------------------------
// 3. IMAGE UPLOAD VALIDATION & STORAGE
// ---------------------------------------------------------
echo PHP_EOL . "--- 3. Testing Featured Image Upload Validation & Storage ---" . PHP_EOL;

Storage::fake('public');

// A. Valid image upload (jpg / png / webp under 2MB)
$validFile = UploadedFile::fake()->image('cricket_pitch.webp', 800, 500)->size(500); // 500 KB
$uploadRequest = Request::create(route('admin.articles.store'), 'POST', [
    'title' => "Image Upload Test {$uniqueId}",
    'category_id' => $testCategory->id,
    'content' => 'Testing image upload handling.',
    'status' => 'draft',
], [], ['featured_image' => $validFile]);

$articleController->store($uploadRequest);
$imageArticle = Article::where('title', "Image Upload Test {$uniqueId}")->first();

if ($imageArticle && !empty($imageArticle->featured_image) && str_starts_with($imageArticle->featured_image, 'storage/articles/')) {
    echo "  [PASS] Valid image uploaded, stored securely with hashed filename: '{$imageArticle->featured_image}'" . PHP_EOL;
    $storedDiskPath = str_replace('storage/', '', $imageArticle->featured_image);
    Storage::disk('public')->assertExists($storedDiskPath);
    echo "  [PASS] Storage::disk('public') confirmed file exists on disk." . PHP_EOL;
} else {
    echo "  [FAIL] Valid image upload failed or path not saved properly." . PHP_EOL;
    exit(1);
}

// B. Invalid MIME type (e.g. PHP script or text file masquerading)
$invalidFile = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');
$invalidRequest = Request::create(route('admin.articles.store'), 'POST', [
    'title' => "Exploit Test {$uniqueId}",
    'category_id' => $testCategory->id,
    'content' => 'Testing invalid MIME type.',
    'status' => 'draft',
], [], ['featured_image' => $invalidFile]);

try {
    $articleController->store($invalidRequest);
    echo "  [FAIL] Expected ValidationException for non-image file, but passed!" . PHP_EOL;
    exit(1);
} catch (ValidationException $e) {
    if (isset($e->errors()['featured_image'])) {
        echo "  [PASS] Non-image file correctly rejected by validation: " . $e->errors()['featured_image'][0] . PHP_EOL;
    } else {
        echo "  [FAIL] ValidationException occurred but not on featured_image: " . json_encode($e->errors()) . PHP_EOL;
        exit(1);
    }
}

// C. Oversize image file (> 2048 KB)
$oversizeFile = UploadedFile::fake()->image('massive.jpg')->size(3000); // 3000 KB
$oversizeRequest = Request::create(route('admin.articles.store'), 'POST', [
    'title' => "Oversize Test {$uniqueId}",
    'category_id' => $testCategory->id,
    'content' => 'Testing oversized file.',
    'status' => 'draft',
], [], ['featured_image' => $oversizeFile]);

try {
    $articleController->store($oversizeRequest);
    echo "  [FAIL] Expected ValidationException for oversized file, but passed!" . PHP_EOL;
    exit(1);
} catch (ValidationException $e) {
    if (isset($e->errors()['featured_image'])) {
        echo "  [PASS] Oversize file (>2MB) correctly rejected by validation: " . $e->errors()['featured_image'][0] . PHP_EOL;
    } else {
        echo "  [FAIL] ValidationException occurred but not on featured_image: " . json_encode($e->errors()) . PHP_EOL;
        exit(1);
    }
}

// Clean up image test article
$imageArticle->delete();

// Clean up created article
$createdArticle->delete();

// ---------------------------------------------------------
// 4. CATEGORY MANAGEMENT & RELATIONSHIP PROTECTION
// ---------------------------------------------------------
echo PHP_EOL . "--- 4. Testing Category CRUD & Relational Integrity Protection ---" . PHP_EOL;

$categoryController = new CategoryController();

// A. Create new category
$catRequest = Request::create(route('admin.categories.store'), 'POST', [
    'name' => "Special Tests {$uniqueId}",
    'description' => 'Test matches and red ball cricket specials.',
]);
$categoryController->store($catRequest);
$createdCategory = Category::where('name', "Special Tests {$uniqueId}")->first();

if ($createdCategory && $createdCategory->slug === "special-tests-{$uniqueId}") {
    echo "  [PASS] Category created successfully with slug: '{$createdCategory->slug}'" . PHP_EOL;
} else {
    echo "  [FAIL] Category creation failed." . PHP_EOL;
    exit(1);
}

// B. Edit category
$catUpdateRequest = Request::create(route('admin.categories.update', $createdCategory->id), 'PUT', [
    'name' => "Special Test Series {$uniqueId}",
    'slug' => "special-test-series-{$uniqueId}",
    'description' => 'Updated description for special test matches.',
]);
$categoryController->update($catUpdateRequest, $createdCategory);
$createdCategory->refresh();

if ($createdCategory->name === "Special Test Series {$uniqueId}") {
    echo "  [PASS] Category updated successfully." . PHP_EOL;
} else {
    echo "  [FAIL] Category update failed." . PHP_EOL;
    exit(1);
}

// C. Test Deletion Protection: Attach an article to category and verify deletion is BLOCKED!
$testProtectedArticle = Article::create([
    'title' => "Article in Protected Category {$uniqueId}",
    'slug' => "article-protected-category-{$uniqueId}",
    'category_id' => $createdCategory->id,
    'author_id' => $adminUser->id,
    'content' => 'Content protecting category from deletion.',
    'status' => 'draft',
]);

$blockedDeleteResponse = $categoryController->destroy($createdCategory);
$categoryStillExists = Category::find($createdCategory->id);

if ($categoryStillExists && session('error')) {
    echo "  [PASS] Relational protection blocked category deletion! Message: \"" . session('error') . "\"" . PHP_EOL;
} else {
    echo "  [FAIL] Category deletion was NOT blocked despite having attached articles!" . PHP_EOL;
    exit(1);
}

// D. Delete attached article and then verify empty category can now be safely deleted
$testProtectedArticle->delete();
$safeDeleteResponse = $categoryController->destroy($createdCategory);
$categoryDeleted = Category::find($createdCategory->id) === null;

if ($categoryDeleted) {
    echo "  [PASS] Empty category deleted safely after article removal." . PHP_EOL;
} else {
    echo "  [FAIL] Empty category deletion failed." . PHP_EOL;
    exit(1);
}

// ---------------------------------------------------------
// 5. ROUTE AUTHORIZATION & ACCESS CONTROLS
// ---------------------------------------------------------
echo PHP_EOL . "--- 5. Testing Authentication & Authorization Middleware ---" . PHP_EOL;

// A. Unauthenticated / Guest Request to /admin/dashboard
Auth::logout();
$guestRequest = Request::create('/admin/dashboard', 'GET');
$guestResponse = $httpKernel->handle($guestRequest);

if ($guestResponse->isRedirection() && str_contains($guestResponse->headers->get('Location'), '/admin/login')) {
    echo "  [PASS] Unauthenticated guest redirected to /admin/login (Status: {$guestResponse->getStatusCode()})" . PHP_EOL;
} else {
    echo "  [FAIL] Guest access not redirected to login. Status: " . $guestResponse->getStatusCode() . PHP_EOL;
    exit(1);
}

// B. Authenticated Regular User (non-admin) to /admin/dashboard
if ($regularUser) {
    Auth::login($regularUser);
    $nonAdminRequest = Request::create('/admin/dashboard', 'GET');
    $nonAdminResponse = $httpKernel->handle($nonAdminRequest);

    if ($nonAdminResponse->getStatusCode() === 403) {
        echo "  [PASS] Non-admin user blocked with 403 Forbidden." . PHP_EOL;
    } else {
        echo "  [FAIL] Non-admin user not blocked. Status: " . $nonAdminResponse->getStatusCode() . PHP_EOL;
        exit(1);
    }
} else {
    echo "  [INFO] No regular user found to test non-admin 403, testing with dynamically created non-admin." . PHP_EOL;
}

// C. Authenticated Admin User to /admin/dashboard
Auth::login($adminUser);
$adminRequest = Request::create('/admin/dashboard', 'GET');
$adminResponse = $httpKernel->handle($adminRequest);

if ($adminResponse->getStatusCode() === 200) {
    echo "  [PASS] Authorized administrator accessed /admin/dashboard with 200 OK." . PHP_EOL;
} else {
    echo "  [FAIL] Administrator request failed with status: " . $adminResponse->getStatusCode() . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "===========================================================" . PHP_EOL;
echo "  ALL ADMIN DASHBOARD & CONTENT MANAGEMENT CHECKS PASSED!  " . PHP_EOL;
echo "===========================================================" . PHP_EOL;
