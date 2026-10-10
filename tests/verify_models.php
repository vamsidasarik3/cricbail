<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;

echo "--- MODEL RELATIONSHIPS VERIFICATION ---" . PHP_EOL;

// 1. User
$user = User::first();
echo "1. User Check: " . ($user ? "{$user->name} ({$user->email}), Is Admin: " . ($user->isAdmin() ? "TRUE" : "FALSE") : "FAIL") . PHP_EOL;

// 2. Category
$category = Category::firstOrCreate(
    ['slug' => 'ipl-2026'],
    ['name' => 'IPL 2026', 'description' => 'Indian Premier League cricket news']
);
echo "2. Category Check: ID #{$category->id}, Name: {$category->name}" . PHP_EOL;

// 3. Article
$article = Article::firstOrCreate(
    ['slug' => 'ipl-2026-auction-highlights'],
    [
        'title' => 'IPL 2026 Auction Highlights',
        'excerpt' => 'Record breaking bids and squads.',
        'content' => 'Complete coverage of the tournament auction results.',
        'category_id' => $category->id,
        'author_id' => $user->id,
        'status' => 'published',
        'published_at' => now(),
    ]
);
echo "3. Article Check: ID #{$article->id}, Title: {$article->title}" . PHP_EOL;
echo "   -> Relationship Category: " . ($article->category->name === 'IPL 2026' ? "PASSED" : "FAILED") . PHP_EOL;
echo "   -> Relationship Author: " . ($article->author->email === $user->email ? "PASSED" : "FAILED") . PHP_EOL;

// 4. Comment
$comment = $article->comments()->firstOrCreate(
    ['email' => 'fan@example.com'],
    [
        'visitor_name' => 'Cricket Fan',
        'comment_body' => 'Great squad review!',
        'status' => 'pending',
    ]
);
echo "4. Comment Check: ID #{$comment->id}, Visitor: {$comment->visitor_name}" . PHP_EOL;
echo "   -> Relationship Article: " . ($comment->article->title === $article->title ? "PASSED" : "FAILED") . PHP_EOL;

// 5. Quiz
$quiz = Quiz::firstOrCreate(
    ['slug' => 'world-cup-legends-quiz'],
    [
        'title' => 'World Cup Legends Quiz',
        'description' => 'Test your knowledge on World Cup finals and historic records.',
        'status' => 'published',
        'published_at' => now(),
    ]
);
echo "5. Quiz Check: ID #{$quiz->id}, Title: {$quiz->title}" . PHP_EOL;

// 6. Question
$question = $quiz->questions()->firstOrCreate(
    ['question_text' => 'Who won the inaugural Cricket World Cup in 1975?'],
    [
        'option_a' => 'Australia',
        'option_b' => 'West Indies',
        'option_c' => 'England',
        'option_d' => 'India',
        'correct_option' => 'b',
        'explanation' => 'West Indies defeated Australia at Lords in 1975.',
        'display_order' => 1,
    ]
);
echo "6. Question Check: ID #{$question->id}, Correct Option: {$question->correct_option}" . PHP_EOL;
echo "   -> Relationship Quiz: " . ($question->quiz->title === $quiz->title ? "PASSED" : "FAILED") . PHP_EOL;

// 7. QuizAttempt
$attempt = $quiz->attempts()->firstOrCreate(
    ['session_id' => 'sess_test_101'],
    [
        'score' => 1,
        'time_taken' => 18,
    ]
);
echo "7. QuizAttempt Check: ID #{$attempt->id}, Score: {$attempt->score}" . PHP_EOL;
echo "   -> Relationship Quiz: " . ($attempt->quiz->title === $quiz->title ? "PASSED" : "FAILED") . PHP_EOL;

echo "--- ALL MODEL CHECKS COMPLETED SUCCESSFULLY ---" . PHP_EOL;
