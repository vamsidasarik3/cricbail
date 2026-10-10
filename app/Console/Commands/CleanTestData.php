<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Console\Command;

class CleanTestData extends Command
{
    protected $signature = 'seo:clean-test-data';
    protected $description = 'Clean up thin test data (ipl-2026 category and ipl-2026-auction-highlights test article)';

    public function handle(): int
    {
        $this->info('========================================================================');
        $this->info('                 CRICBAIL SEO: TEST DATA CLEANUP                        ');
        $this->info('========================================================================');

        // 1. Resolve primary IPL category
        $primaryIpl = Category::firstOrCreate(
            ['slug' => 'ipl'],
            [
                'name' => 'IPL',
                'description' => 'Comprehensive tournament coverage, auction reports, franchise moves, and Indian Premier League match analysis.',
            ]
        );

        // 2. Clean up test article: ipl-2026-auction-highlights
        $testArticle = Article::where('slug', 'ipl-2026-auction-highlights')->first();
        if ($testArticle) {
            $testArticle->delete();
            $this->info('[CLEANUP] Deleted thin test article: ipl-2026-auction-highlights');
        } else {
            $this->line('[INFO] Test article ipl-2026-auction-highlights not present or already removed.');
        }

        // 3. Clean up test category: ipl-2026
        $testCategory = Category::where('slug', 'ipl-2026')->first();
        if ($testCategory) {
            // Reassign any remaining articles
            Article::where('category_id', $testCategory->id)->update(['category_id' => $primaryIpl->id]);
            $testCategory->delete();
            $this->info('[CLEANUP] Deleted duplicate test category: ipl-2026');
        } else {
            $this->line('[INFO] Test category ipl-2026 not present or already removed.');
        }

        $this->info('Test data cleanup finished successfully!');
        return 0;
    }
}
