<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CricketNewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or create Administrator User
        $admin = User::firstOrCreate(
            ['email' => 'admin@cricbail.com'],
            [
                'name' => 'CricBail Editorial Team',
                'password' => bcrypt('Password123!'),
                'is_admin' => true,
            ]
        );

        // 2. Categories requested in navigation
        $categoriesData = [
            [
                'name' => 'IPL',
                'slug' => 'ipl',
                'description' => 'Comprehensive tournament coverage, auction reports, franchise moves, and Indian Premier League match analysis.',
            ],
            [
                'name' => 'International Cricket',
                'slug' => 'international-cricket',
                'description' => 'Global bilateral series, ICC World Test Championship cycles, World Cup tournaments, and international rivalries.',
            ],
            [
                'name' => 'Domestic Cricket',
                'slug' => 'domestic-cricket',
                'description' => 'Ranji Trophy, County Championship, Sheffield Shield, and grassroot talent development worldwide.',
            ],
            [
                'name' => 'Players',
                'slug' => 'players',
                'description' => 'Exclusive player profiles, milestone trackers, fitness updates, and career retrospectives.',
            ],
            [
                'name' => 'Analysis',
                'slug' => 'analysis',
                'description' => 'Tactical breakdowns, pitch reports, analytical bowling data, and deep-dive strategic commentary.',
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['slug']] = Category::updateOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $cat['name'], 'description' => $cat['description']]
            );
        }

        // 3. Articles (Sample content clearly labeled for development)
        $articlesData = [
            [
                'title' => 'Tactical Evolution: How Middle-Overs Spin Chokes Define Modern T20 Encounters',
                'slug' => 'tactical-evolution-middle-overs-spin-chokes-modern-t20',
                'category_slug' => 'analysis',
                'excerpt' => 'A statistical inspection into how wrist-spinners and defensive lengths during overs 7 to 14 dictate modern victory margins across top franchise leagues.',
                'featured_image' => 'assets/images/cricket/data_analysis.svg',
                'status' => 'published',
                'published_at' => now()->subHours(2),
                'meta_title' => 'Tactical Evolution of Middle-Overs Spin in T20 Cricket | CricBail',
                'meta_description' => 'Detailed analysis of middle-overs bowling tactics, boundary percentages, and dot-ball ratios in modern T20 cricket.',
                'content' => "<p class='lead'>Over the last five years of elite Twenty20 cricket, tactical conversations have swung radically between powerplay aggression and death-overs fireworks. Yet the quiet crucible of most encounters consistently lies between overs 7 and 14.</p>

<h3>The Statistical Pivot in Overs 7–14</h3>
<p>Modern analytical data reveals that teams conceding fewer than 7.2 runs per over during the middle phases win approximately 73% of white-ball fixtures. The reliance on wrist-spinners possessing deceptive googlies has forced fielding captains to rethink boundary sweeps.</p>

<blockquote class='p-3 bg-light border-start border-4 border-success my-4'>
    <em>'The middle overs are no longer about consolidation; they are about manufacturing dot balls under pressure.'</em>
</blockquote>

<h3>Wicket-Taking Over Containment</h3>
<p>Captains are increasingly setting attacking fields in the 10th and 11th overs, enticing aerial strokeplay toward wider square boundaries. As boundary ropes remain standardized, bowling lengths pitched between 6 to 8 metres continue to yield high false-shot percentages.</p>

<p>As the tournament calendar intensifies, coaching staffs that build dynamic spin arsenals will retain a decisive tactical advantage on subcontinental and abrasive surfaces.</p>",
            ],
            [
                'title' => 'IPL 2026 Mega Retentions: Strategic Blueprint and Franchise Squad Balances',
                'slug' => 'ipl-2026-mega-retentions-strategic-blueprint-squad-balance',
                'category_slug' => 'ipl',
                'excerpt' => 'Analyzing key franchise retention matrices, purse allocations, and uncapped player policies ahead of the upcoming IPL season.',
                'featured_image' => 'assets/images/cricket/ipl_auction.svg',
                'status' => 'published',
                'published_at' => now()->subHours(5),
                'meta_title' => 'IPL 2026 Mega Retentions & Franchise Squad Balance | CricBail',
                'meta_description' => 'Comprehensive look at IPL 2026 retention guidelines, auction purse dynamics, and franchise squad structures.',
                'content' => "<p class='lead'>As the tournament council confirms final purse limits, all ten Indian Premier League franchises have finalized their core strategic retention strategies ahead of the grand player auction.</p>

<h3>Core Indian Talent Dictates Auction Power</h3>
<p>Franchises that preserved their experienced Indian domestic core have shielded their purse budgets from bidding escalations. Teams retaining seasoned top-order captains enter the auction room with unmatched tactical flexibility.</p>

<h3>Uncapped Gems and Fast-Bowling Slots</h3>
<p>With express pace bowlers commanding unprecedented auction premiums, several franchises have invested heavily into domestic pace academies. The emphasis on bowlers clocking 145+ km/h with reverse-swing capability remains the holy grail of modern scouting operations.</p>

<p>The auction dynamics promise exceptional competition as analysts prepare for multi-tiered bidding wars across all marquee sets.</p>",
            ],
            [
                'title' => 'The Endurance Test: Why Five-Day Test Cricket Remains the Pinnacle of the Sport',
                'slug' => 'endurance-test-why-five-day-test-cricket-remains-pinnacle',
                'category_slug' => 'international-cricket',
                'excerpt' => 'From swinging red Dukes in overcast conditions to gritty fifth-day run chases on deteriorating tracks, the timeless magic of Test match cricket endures.',
                'featured_image' => 'assets/images/cricket/test_cricket.svg',
                'status' => 'published',
                'published_at' => now()->subDay(),
                'meta_title' => 'Why Five-Day Test Cricket Remains the Pinnacle | CricBail',
                'meta_description' => 'An editorial exploring the technical and psychological depths of five-day Test match cricket.',
                'content' => "<p class='lead'>In an era dominated by short formats, the technical depth, mental resilience, and unfolding narrative of a five-day Test encounter remain unmatched across world sports.</p>

<h3>The Psychological Battle of Attrition</h3>
<p>Unlike limited-overs cricket where mistakes can often be masked by quick momentum shifts, Test cricket exposes technical vulnerabilities across sessions. A batsman facing a probing spell in the morning session must conquer both movement off the seam and personal doubts.</p>

<blockquote class='p-3 bg-light border-start border-4 border-success my-4'>
    <em>'Test cricket tests character before technique; it asks how much discomfort a player can endure across ninety overs.'</em>
</blockquote>

<h3>Pitch Deterioration and Fifth-Day Drama</h3>
<p>Cracks widening on day five, variable bounce, and the duel between courageous tail-enders and relentless bowlers produce some of sport's most gripping theatre. The World Test Championship has revitalized interest, ensuring every single match carries critical championship points.</p>",
            ],
            [
                'title' => 'Ranji Trophy Showcase: Young Fast Bowlers Rising from Domestic Grassroots',
                'slug' => 'ranji-trophy-showcase-young-fast-bowlers-rising-grassroots',
                'category_slug' => 'domestic-cricket',
                'excerpt' => 'A spotlight on the breakout seamers who bowled marathon spells on grinding pitches during the domestic four-day competition.',
                'featured_image' => 'assets/images/cricket/domestic_trophy.svg',
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'meta_title' => 'Ranji Trophy Showcase: Rising Fast Bowlers | CricBail',
                'meta_description' => 'Profiling the young fast bowling prospects excelling in first-class domestic cricket.',
                'content' => "<p class='lead'>The domestic first-class season has brought forth a wave of disciplined fast bowlers capable of sustaining long spells in tough subcontinental conditions.</p>

<h3>The Workhorses of First-Class Cricket</h3>
<p>While franchise radar screens often look for immediate speeds, selectors value the grueling domestic overs bowled in sweltering heat. Several uncapped pacers finished the group stages with 35+ wickets at an average beneath 18.5.</p>

<p>Their ability to reverse-swing the older SG ball and maintain relentless fourth-stump discipline highlights the robust health of domestic talent pipelines.</p>",
            ],
            [
                'title' => 'Captaincy in Focus: How Strategic Field Placements Turn Tight Finishes',
                'slug' => 'captaincy-in-focus-strategic-field-placements-tight-finishes',
                'category_slug' => 'players',
                'excerpt' => 'Breaking down the high-risk, high-reward tactical decisions made by premier captains during nail-biting final overs.',
                'featured_image' => 'assets/images/cricket/player_spotlight.svg',
                'status' => 'published',
                'published_at' => now()->subDays(3),
                'meta_title' => 'Captaincy in Focus: Strategic Field Placements | CricBail',
                'meta_description' => 'Analyzing field placement tactics and tactical leadership under pressure in cricket.',
                'content' => "<p class='lead'>Under the glare of modern broadcast lenses, cricket captaincy has transformed into a high-stakes chess match where every boundary sweeper placement can swing the contest.</p>

<h3>The Risk of the Deep Point Trap</h3>
<p>Modern captains often leave third man open while packing the cover boundary, tempting batsmen to play against the bounce. Examining key matches from recent series shows that subtle field adjustments created false strokes in 68% of targeted overs.</p>

<p>Mental composure under pressure separates good captains from legendary leaders who can read the pitch and bowler morale concurrently.</p>",
            ],
            [
                'title' => 'Under Floodlights: The Allure and Science of Pink-Ball Day-Night Tests',
                'slug' => 'under-floodlights-allure-and-science-pink-ball-day-night-tests',
                'category_slug' => 'international-cricket',
                'excerpt' => 'Examining twilight visibility, extra lacquer, and swing dynamics during the twilight session of day-night Test matches.',
                'featured_image' => 'assets/images/cricket/stadium_floodlights.svg',
                'status' => 'published',
                'published_at' => now()->subDays(4),
                'meta_title' => 'Pink-Ball Day-Night Tests: Allure and Science | CricBail',
                'meta_description' => 'The technical nuances and dramatic twilight sessions of pink-ball day-night Test cricket.',
                'content' => "<p class='lead'>Day-night Test cricket has captured spectator imaginations, drawing bumper crowds to enjoy top-tier red-ball battles under luminous stadium floodlights.</p>

<h3>The Twilight Trap</h3>
<p>Cricketers universally acknowledge that the 45-minute transition from natural daylight to full artificial lighting is the most treacherous period for top-order batsmen. The extra lacquer on the pink leather ball maintains swing long into the second session.</p>

<p>With administrators evaluating regular day-night fixtures across bilateral schedules, mastering pink-ball conditions has become an essential art for world-class batting units.</p>",
            ],
            [
                'title' => 'Draft Story: Tactical Analysis for Upcoming ICC Champions Trophy (Unpublished)',
                'slug' => 'draft-tactical-analysis-upcoming-icc-champions-trophy',
                'category_slug' => 'analysis',
                'excerpt' => 'Internal draft exploring early team combinations and conditions preview.',
                'featured_image' => 'assets/images/cricket/data_analysis.svg',
                'status' => 'draft', // DRAFT - MUST NOT BE PUBLICLY ACCESSIBLE
                'published_at' => null,
                'meta_title' => 'Draft Preview',
                'meta_description' => 'Draft description',
                'content' => "<p>This is an internal editorial draft and should strictly remain hidden from public visitors until approved and published by an administrator.</p>",
            ],
        ];

        foreach ($articlesData as $artData) {
            $cat = $categories[$artData['category_slug']];
            $article = Article::updateOrCreate(
                ['slug' => $artData['slug']],
                [
                    'title' => $artData['title'],
                    'excerpt' => $artData['excerpt'],
                    'content' => $artData['content'],
                    'featured_image' => $artData['featured_image'],
                    'category_id' => $cat->id,
                    'author_id' => $admin->id,
                    'status' => $artData['status'],
                    'published_at' => $artData['published_at'],
                    'meta_title' => $artData['meta_title'],
                    'meta_description' => $artData['meta_description'],
                ]
            );

            // Add sample approved comment to published articles
            if ($article->status === 'published' && $article->comments()->count() === 0) {
                $article->comments()->create([
                    'visitor_name' => 'Vikram Seth',
                    'email' => 'vikram@example.com',
                    'comment_body' => 'Superb and incisive article! The tactical depth here is truly refreshing.',
                    'status' => 'approved',
                ]);
            }
        }

        // 4. Sample Quizzes
        $quiz1 = Quiz::updateOrCreate(
            ['slug' => 'cricket-world-cup-legends'],
            [
                'title' => 'World Cup Legends & Historic Moments',
                'description' => 'Put your cricket history knowledge to the test with iconic World Cup finals, record bowling spells, and legendary batting partnerships.',
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ]
        );

        if ($quiz1->questions()->count() === 0) {
            $quiz1->questions()->createMany([
                [
                    'question_text' => 'Who hit the iconic 175* for India against Zimbabwe during the 1983 World Cup at Tunbridge Wells?',
                    'option_a' => 'Sunil Gavaskar',
                    'option_b' => 'Kapil Dev',
                    'option_c' => 'Mohinder Amarnath',
                    'option_d' => 'Kris Srikkanth',
                    'correct_option' => 'b',
                    'explanation' => 'Kapil Dev rescued India from 17/5 with an unforgettable counter-attacking 175 not out.',
                    'display_order' => 1,
                ],
                [
                    'question_text' => 'Which bowler holds the record for taking 4 wickets in 4 consecutive balls at a Men\'s Cricket World Cup?',
                    'option_a' => 'Lasith Malinga',
                    'option_b' => 'Wasim Akram',
                    'option_c' => 'Glenn McGrath',
                    'option_d' => 'Mitchell Starc',
                    'correct_option' => 'a',
                    'explanation' => 'Lasith Malinga achieved this historic feat against South Africa in the 2007 World Cup in Guyana.',
                    'display_order' => 2,
                ],
            ]);
        }

        $quiz2 = Quiz::updateOrCreate(
            ['slug' => 'ipl-masters-trivia'],
            [
                'title' => 'IPL Masters Trivia Challenge',
                'description' => 'How well do you know the history, orange cap winners, and nail-biting super-over finishes of the Indian Premier League?',
                'status' => 'published',
                'published_at' => now()->subDay(),
            ]
        );

        if ($quiz2->questions()->count() === 0) {
            $quiz2->questions()->createMany([
                [
                    'question_text' => 'Who scored the first ever century in the history of the Indian Premier League on April 18, 2008?',
                    'option_a' => 'Brendon McCullum',
                    'option_b' => 'Michael Hussey',
                    'option_c' => 'Chris Gayle',
                    'option_d' => 'Adam Gilchrist',
                    'correct_option' => 'a',
                    'explanation' => 'Brendon McCullum struck an explosive 158* off 73 balls for KKR against RCB in the IPL opener.',
                    'display_order' => 1,
                ],
            ]);
        }
    }
}
