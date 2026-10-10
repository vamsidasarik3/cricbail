<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Frontend Routes
|--------------------------------------------------------------------------
*/

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// News Articles & Categories
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/category/{slug}', [NewsController::class, 'category'])->name('news.category');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

// Public Comment Submission with Rate Limiting (5 per minute per IP)
Route::post('/news/{slug}/comment', [NewsController::class, 'storeComment'])
    ->middleware('throttle:5,1')
    ->name('news.comment.store');

// Cricket Quizzes (10 Questions, 120 Seconds, Max Score 10)
Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
Route::get('/quizzes/{slug}', [QuizController::class, 'show'])->name('quizzes.show');
Route::get('/quizzes/{slug}/play', [QuizController::class, 'play'])->name('quizzes.play');
Route::post('/quizzes/{slug}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
Route::get('/quizzes/{slug}/result/{attempt}', [QuizController::class, 'result'])->name('quizzes.result');

// Static Informational Pages
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy');

// SEO XML Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

/*
|--------------------------------------------------------------------------
| Administrative Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest Authentication Routes
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');

    // Authenticated & Authorized Admin Routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [AdminDashboardController::class, 'index']);
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Article Management Routes
        Route::get('articles', [\App\Http\Controllers\Admin\ArticleController::class, 'index'])->name('articles.index');
        Route::get('articles/create', [\App\Http\Controllers\Admin\ArticleController::class, 'create'])->name('articles.create');
        Route::post('articles', [\App\Http\Controllers\Admin\ArticleController::class, 'store'])->name('articles.store');
        Route::get('articles/{article}/edit', [\App\Http\Controllers\Admin\ArticleController::class, 'edit'])->name('articles.edit');
        Route::put('articles/{article}', [\App\Http\Controllers\Admin\ArticleController::class, 'update'])->name('articles.update');
        Route::delete('articles/{article}', [\App\Http\Controllers\Admin\ArticleController::class, 'destroy'])->name('articles.destroy');
        Route::post('articles/{article}/publish', [\App\Http\Controllers\Admin\ArticleController::class, 'publish'])->name('articles.publish');
        Route::post('articles/{article}/unpublish', [\App\Http\Controllers\Admin\ArticleController::class, 'unpublish'])->name('articles.unpublish');

        // Category Management Routes
        Route::get('categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/create', [\App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

        // Comment Moderation Routes
        Route::get('comments', [AdminCommentController::class, 'index'])->name('comments.index');
        Route::post('comments/{comment}/approve', [AdminCommentController::class, 'approve'])->name('comments.approve');
        Route::post('comments/{comment}/reject', [AdminCommentController::class, 'reject'])->name('comments.reject');
        Route::delete('comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');

        // Quiz Management Routes
        Route::get('quizzes', [AdminQuizController::class, 'index'])->name('quizzes.index');
        Route::get('quizzes/create', [AdminQuizController::class, 'create'])->name('quizzes.create');
        Route::post('quizzes', [AdminQuizController::class, 'store'])->name('quizzes.store');
        Route::get('quizzes/{quiz}/edit', [AdminQuizController::class, 'edit'])->name('quizzes.edit');
        Route::put('quizzes/{quiz}', [AdminQuizController::class, 'update'])->name('quizzes.update');
        Route::delete('quizzes/{quiz}', [AdminQuizController::class, 'destroy'])->name('quizzes.destroy');
        Route::post('quizzes/{quiz}/publish', [AdminQuizController::class, 'publish'])->name('quizzes.publish');
        Route::post('quizzes/{quiz}/unpublish', [AdminQuizController::class, 'unpublish'])->name('quizzes.unpublish');

        // Quiz Question Management Routes
        Route::get('quizzes/{quiz}/questions', [AdminQuestionController::class, 'index'])->name('quizzes.questions.index');
        Route::post('quizzes/{quiz}/questions', [AdminQuestionController::class, 'store'])->name('quizzes.questions.store');
        Route::put('quizzes/{quiz}/questions/{question}', [AdminQuestionController::class, 'update'])->name('quizzes.questions.update');
        Route::delete('quizzes/{quiz}/questions/{question}', [AdminQuestionController::class, 'destroy'])->name('quizzes.questions.destroy');
        Route::post('quizzes/{quiz}/questions/reorder', [AdminQuestionController::class, 'reorder'])->name('quizzes.questions.reorder');
    });
});
