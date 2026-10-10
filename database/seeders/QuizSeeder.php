<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Seed quizzes with exactly 10 high-quality cricket questions each.
     */
    public function run(): void
    {
        // ---------------------------------------------------------
        // QUIZ 1: World Cup Legends & Historic Moments (10 questions)
        // ---------------------------------------------------------
        $quiz1 = Quiz::updateOrCreate(
            ['slug' => 'cricket-world-cup-legends'],
            [
                'title' => 'World Cup Legends & Historic Moments',
                'description' => 'Test your knowledge on iconic ICC Men\'s Cricket World Cup milestones, unforgettable finals, and legendary match-winners.',
                'status' => 'published',
                'published_at' => now()->subDays(2),
            ]
        );

        // Clear existing questions for clean seed
        $quiz1->questions()->delete();

        $wcQuestions = [
            [
                'question_text' => 'Who was the captain of the Indian cricket team that famously lifted the 1983 Prudential World Cup at Lord\'s?',
                'option_a' => 'Sunil Gavaskar',
                'option_b' => 'Kapil Dev',
                'option_c' => 'Mohinder Amarnath',
                'option_d' => 'Dilip Vengsarkar',
                'correct_option' => 'b',
                'explanation' => 'Kapil Dev led India to their historic maiden World Cup victory in 1983, defeating the two-time defending champions West Indies.',
                'display_order' => 1,
            ],
            [
                'question_text' => 'Which bowler claimed the legendary match-winning spell of 3 for 41 including dismissing Inzamam and Wasim in the 1992 World Cup Final?',
                'option_a' => 'Wasim Akram',
                'option_b' => 'Imran Khan',
                'option_c' => 'Mushtaq Ahmed',
                'option_d' => 'Aqib Javed',
                'correct_option' => 'a',
                'explanation' => 'Wasim Akram produced an iconic spell in Melbourne against England, clean-bowling Allan Lamb and Chris Lewis off consecutive deliveries.',
                'display_order' => 2,
            ],
            [
                'question_text' => 'Who holds the record for the highest individual score in a Men\'s Cricket World Cup match with 237 not out?',
                'option_a' => 'Chris Gayle',
                'option_b' => 'Martin Guptill',
                'option_c' => 'Glenn Maxwell',
                'option_d' => 'Rohit Sharma',
                'correct_option' => 'b',
                'explanation' => 'Martin Guptill scored a blistering 237* off 163 balls for New Zealand against the West Indies in Wellington during the 2015 World Cup quarter-final.',
                'display_order' => 3,
            ],
            [
                'question_text' => 'Which country won three consecutive ICC Men\'s Cricket World Cup titles between 1999 and 2007?',
                'option_a' => 'West Indies',
                'option_b' => 'India',
                'option_c' => 'Australia',
                'option_d' => 'England',
                'correct_option' => 'c',
                'explanation' => 'Australia achieved an unprecedented hat-trick of World Cup triumphs, winning in 1999 (under Steve Waugh), 2003, and 2007 (both under Ricky Ponting).',
                'display_order' => 4,
            ],
            [
                'question_text' => 'Who was adjudged the Player of the Tournament in the 2011 ICC Cricket World Cup for his all-round heroics (362 runs & 15 wickets)?',
                'option_a' => 'Sachin Tendulkar',
                'option_b' => 'MS Dhoni',
                'option_c' => 'Yuvraj Singh',
                'option_d' => 'Zaheer Khan',
                'correct_option' => 'c',
                'explanation' => 'Yuvraj Singh put up a stellar all-round display scoring 362 runs with one century and four fifties while picking up 15 crucial wickets with left-arm spin.',
                'display_order' => 5,
            ],
            [
                'question_text' => 'Against which team did Glenn Maxwell smash an unbelievable 201 not out from 91/7 to script an iconic 2023 World Cup chase?',
                'option_a' => 'Afghanistan',
                'option_b' => 'Netherlands',
                'option_c' => 'South Africa',
                'option_d' => 'Sri Lanka',
                'correct_option' => 'a',
                'explanation' => 'At Wankhede Stadium in 2023, Maxwell battled severe cramps to hit 201* off 128 deliveries, rescuing Australia from 91 for 7 to victory against Afghanistan.',
                'display_order' => 6,
            ],
            [
                'question_text' => 'Who hit six sixes in an over off Stuart Broad during the inaugural 2007 ICC World Twenty20 in Durban?',
                'option_a' => 'Chris Gayle',
                'option_b' => 'Yuvraj Singh',
                'option_c' => 'Shahid Afridi',
                'option_d' => 'Herschelle Gibbs',
                'correct_option' => 'b',
                'explanation' => 'Yuvraj Singh smashed 36 runs off Stuart Broad\'s 19th over, reaching the fastest fifty in international cricket off just 12 balls.',
                'display_order' => 7,
            ],
            [
                'question_text' => 'Which venue hosted the nail-biting 2019 World Cup Final decided via a boundary countback after a tied Super Over?',
                'option_a' => 'The Oval, London',
                'option_b' => 'Edgbaston, Birmingham',
                'option_c' => 'Lord\'s, London',
                'option_d' => 'Old Trafford, Manchester',
                'correct_option' => 'c',
                'explanation' => 'The historic 2019 World Cup final between England and New Zealand was contested at Lord\'s Cricket Ground in London.',
                'display_order' => 8,
            ],
            [
                'question_text' => 'Who is the highest run-scorer in ICC Men\'s Cricket World Cup history with 2,278 runs across six editions?',
                'option_a' => 'Ricky Ponting',
                'option_b' => 'Virat Kohli',
                'option_c' => 'Kumar Sangakkara',
                'option_d' => 'Sachin Tendulkar',
                'correct_option' => 'd',
                'explanation' => 'Sachin Tendulkar amassed 2,278 runs in 45 World Cup matches between 1992 and 2011, boasting 6 centuries and 15 half-centuries.',
                'display_order' => 9,
            ],
            [
                'question_text' => 'Which bowler holds the record for the most wickets taken in a single Cricket World Cup edition (24 wickets in 2023)?',
                'option_a' => 'Mitchell Starc',
                'option_b' => 'Mohammed Shami',
                'option_c' => 'Glenn McGrath',
                'option_d' => 'Muttiah Muralitharan',
                'correct_option' => 'b',
                'explanation' => 'Mohammed Shami took 24 wickets in just 7 matches during the 2023 World Cup at an astonishing average of 10.70, including three 5-wicket hauls.',
                'display_order' => 10,
            ],
        ];

        foreach ($wcQuestions as $q) {
            $quiz1->questions()->create($q);
        }

        // ---------------------------------------------------------
        // QUIZ 2: IPL Masters Trivia Challenge (10 questions)
        // ---------------------------------------------------------
        $quiz2 = Quiz::updateOrCreate(
            ['slug' => 'ipl-masters-trivia'],
            [
                'title' => 'IPL Masters Trivia Challenge',
                'description' => 'Think you know the Indian Premier League? Test your recall on boundary bonanzas, mega auctions, purple caps, and thrilling final overs.',
                'status' => 'published',
                'published_at' => now()->subDay(),
            ]
        );

        $quiz2->questions()->delete();

        $iplQuestions = [
            [
                'question_text' => 'Who scored the memorable century (158 not out) in the very first match of the inaugural Indian Premier League in 2008?',
                'option_a' => 'Chris Gayle',
                'option_b' => 'Brendon McCullum',
                'option_c' => 'Adam Gilchrist',
                'option_d' => 'Shaun Marsh',
                'correct_option' => 'b',
                'explanation' => 'Brendon McCullum lit up the Chinnaswamy Stadium on April 18, 2008, scoring 158* off 73 balls for KKR against RCB.',
                'display_order' => 1,
            ],
            [
                'question_text' => 'Which franchise has won the joint-most IPL titles (5 trophies) along with Mumbai Indians as of 2024?',
                'option_a' => 'Kolkata Knight Riders',
                'option_b' => 'Chennai Super Kings',
                'option_c' => 'Rajasthan Royals',
                'option_d' => 'Sunrisers Hyderabad',
                'correct_option' => 'b',
                'explanation' => 'Chennai Super Kings under MS Dhoni clinched their 5th title in 2023, matching Mumbai Indians\' record haul of five IPL titles.',
                'display_order' => 2,
            ],
            [
                'question_text' => 'Who holds the record for the highest individual score in an IPL innings with 175 not out off 66 balls?',
                'option_a' => 'AB de Villiers',
                'option_b' => 'Chris Gayle',
                'option_c' => 'Virat Kohli',
                'option_d' => 'KL Rahul',
                'correct_option' => 'b',
                'explanation' => 'Chris Gayle blasted 175* off 66 balls for Royal Challengers Bangalore against Pune Warriors India at Bangalore in 2013.',
                'display_order' => 3,
            ],
            [
                'question_text' => 'Who is the all-time leading run-scorer in IPL history with over 8,000 runs?',
                'option_a' => 'Shikhar Dhawan',
                'option_b' => 'Rohit Sharma',
                'option_c' => 'Virat Kohli',
                'option_d' => 'David Warner',
                'correct_option' => 'c',
                'explanation' => 'Virat Kohli has represented Royal Challengers Bengaluru since 2008 and stands atop the all-time scoring charts with over 8,000 IPL runs.',
                'display_order' => 4,
            ],
            [
                'question_text' => 'Which bowler holds the record for the most wickets taken in a single IPL season (32 wickets in 2021)?',
                'option_a' => 'Dwayne Bravo',
                'option_b' => 'Harshal Patel',
                'option_c' => 'Jasprit Bumrah',
                'option_d' => 'Kagiso Rabada',
                'correct_option' => 'b',
                'explanation' => 'Harshal Patel equaled Dwayne Bravo\'s record by taking 32 wickets for Royal Challengers Bangalore in the 2021 edition.',
                'display_order' => 5,
            ],
            [
                'question_text' => 'Which player holds the record for scoring the most centuries in a single IPL season (4 centuries in 2016)?',
                'option_a' => 'Jos Buttler',
                'option_b' => 'Virat Kohli',
                'option_c' => 'Shubman Gill',
                'option_d' => 'David Warner',
                'correct_option' => 'b',
                'explanation' => 'Virat Kohli amassed 973 runs with 4 centuries in IPL 2016. Jos Buttler matched the 4-century feat later in IPL 2022.',
                'display_order' => 6,
            ],
            [
                'question_text' => 'Which uncapped sensation defended 13 runs against MS Dhoni in the final over of the 2023 clash at Chepauk for Rajasthan Royals?',
                'option_a' => 'Sandeep Sharma',
                'option_b' => 'Kuldeep Sen',
                'option_c' => 'Navdeep Saini',
                'option_d' => 'Avesh Khan',
                'correct_option' => 'a',
                'explanation' => 'Sandeep Sharma held his nerve with precise yorkers to deny MS Dhoni and Ravindra Jadeja at Chepauk in IPL 2023.',
                'display_order' => 7,
            ],
            [
                'question_text' => 'Who scored the fastest 50 in IPL history, achieving the milestone off just 13 deliveries against KKR at Eden Gardens in 2023?',
                'option_a' => 'KL Rahul',
                'option_b' => 'Pat Cummins',
                'option_c' => 'Yashasvi Jaiswal',
                'option_d' => 'Nicholas Pooran',
                'correct_option' => 'c',
                'explanation' => 'Yashasvi Jaiswal of Rajasthan Royals smashed a blistering 13-ball half-century against Kolkata Knight Riders in May 2023.',
                'display_order' => 8,
            ],
            [
                'question_text' => 'Who is the only overseas player to have won the IPL Orange Cap award three times (2015, 2017, 2019)?',
                'option_a' => 'Chris Gayle',
                'option_b' => 'David Warner',
                'option_c' => 'Shane Watson',
                'option_d' => 'Faf du Plessis',
                'correct_option' => 'b',
                'explanation' => 'David Warner won the Orange Cap in 2015, 2017, and 2019 while captaining and leading Sunrisers Hyderabad.',
                'display_order' => 9,
            ],
            [
                'question_text' => 'Which team won the inaugural edition of the Indian Premier League in 2008 under captain Shane Warne?',
                'option_a' => 'Chennai Super Kings',
                'option_b' => 'Rajasthan Royals',
                'option_c' => 'Delhi Daredevils',
                'option_d' => 'Kings XI Punjab',
                'correct_option' => 'b',
                'explanation' => 'Shane Warne inspired an underdog Rajasthan Royals side to edge past CSK in a thrilling final on June 1, 2008.',
                'display_order' => 10,
            ],
        ];

        foreach ($iplQuestions as $q) {
            $quiz2->questions()->create($q);
        }

        // Clean up or keep quiz 1 'world-cup-legends-quiz' as draft so we can test admin question count validation
        $draftQuiz = Quiz::where('slug', 'world-cup-legends-quiz')->first();
        if ($draftQuiz) {
            $draftQuiz->update([
                'status' => 'draft',
                'published_at' => null,
            ]);
        }
    }
}
