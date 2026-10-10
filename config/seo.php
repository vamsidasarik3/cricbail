<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Canonical Base Domain
    |--------------------------------------------------------------------------
    |
    | Production domain used for all canonical URLs and sitemap location tags.
    | Local development URLs (e.g. localhost, vkrshna.in:8084) must NEVER be
    | emitted as canonical URLs or sitemap loc tags.
    |
    */
    'canonical_domain' => env('CANONICAL_DOMAIN', 'https://www.cricbail.com'),

    /*
    |--------------------------------------------------------------------------
    | Site Identity & Branding
    |--------------------------------------------------------------------------
    */
    'site_name' => 'CricBail',
    'default_title' => 'CricBail - Premier Cricket News, In-Depth Analysis & Quizzes',
    'default_description' => 'Your premier destination for timely cricket news, tactical tournament analysis, IPL developments, and interactive cricket trivia.',
    'default_image' => 'assets/images/cricket/cricbail_share_card.jpg',
    'twitter_handle' => '@CricBail',

    /*
    |--------------------------------------------------------------------------
    | Search Console Verification Tokens
    |--------------------------------------------------------------------------
    */
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION', null),
    'bing_site_verification' => env('BING_SITE_VERIFICATION', null),
    'ga_measurement_id' => env('GA_MEASUREMENT_ID', null),
];
