<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Quiz;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Build an absolute canonical URL using the configured production HTTPS domain.
     * Prevents local development URLs and staging URLs from ever appearing in canonical tags.
     */
    public static function canonicalUrl(?string $path = null, array $whitelistParams = ['page']): string
    {
        $base = rtrim(config('seo.canonical_domain', 'https://www.cricbail.com'), '/');

        if ($path === null) {
            $rawPath = request()->path();
            $path = ($rawPath === '/' || $rawPath === '') ? '' : '/' . ltrim($rawPath, '/');
        } elseif (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parsedPath = parse_url($path, PHP_URL_PATH) ?? '';
            $path = ($parsedPath === '/' || $parsedPath === '') ? '' : '/' . ltrim($parsedPath, '/');
        } else {
            $path = ($path === '/' || $path === '') ? '' : '/' . ltrim($path, '/');
        }

        // Handle whitelisted query parameters (e.g. pagination ?page=2)
        $queryString = '';
        $queryParts = [];

        foreach ($whitelistParams as $param) {
            if (request()->has($param)) {
                $val = request()->query($param);
                // Only keep page if it's greater than 1
                if ($param === 'page' && (int)$val > 1) {
                    $queryParts[$param] = (int)$val;
                } elseif ($param !== 'page' && !empty($val)) {
                    $queryParts[$param] = $val;
                }
            }
        }

        if (!empty($queryParts)) {
            $queryString = '?' . http_build_query($queryParts);
        }

        return $base . $path . $queryString;
    }

    /**
     * Clean and format title tags to prevent duplicate brand suffixes (e.g. '| CricBail | CricBail').
     */
    public static function title(?string $rawTitle = null, bool $appendBrand = true): string
    {
        if (empty($rawTitle)) {
            return config('seo.default_title', 'CricBail - Modern Cricket Journalism & Tactical Match Analysis');
        }

        $cleaned = trim($rawTitle);

        // Strip existing single or double brand suffixes
        $cleaned = preg_replace('/\s*\|\s*CricBail(\s*\|\s*CricBail)?$/i', '', $cleaned);
        $cleaned = preg_replace('/\s*-\s*CricBail(\s*-\s*CricBail)?$/i', '', $cleaned);
        $cleaned = trim($cleaned);

        if ($appendBrand) {
            return $cleaned . ' | ' . config('seo.site_name', 'CricBail');
        }

        return $cleaned;
    }

    /**
     * Clean and truncate meta description to optimal SERP length (150-160 chars).
     */
    public static function description(?string $rawDesc = null, ?string $fallback = null): string
    {
        $desc = !empty($rawDesc) ? $rawDesc : $fallback;
        if (empty($desc)) {
            $desc = config('seo.default_description', 'In-depth cricket journalism, breaking news, tournament analysis, and interactive cricket trivia on CricBail.');
        }

        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($desc)));
        return Str::limit($clean, 160, '...');
    }

    /**
     * Resolve absolute URL for social preview image (Open Graph & Twitter).
     * Enforces JPEG/PNG/WebP format and rejects SVG files unsupported by crawlers.
     */
    public static function socialImage(?string $imagePath = null): string
    {
        $base = rtrim(config('seo.canonical_domain', 'https://www.cricbail.com'), '/');
        $fallback = $base . '/' . ltrim(config('seo.default_image', 'assets/images/cricket/cricbail_share_card.jpg'), '/');

        if (empty($imagePath)) {
            return $fallback;
        }

        // SVG files cannot be parsed by Facebook / Twitter / WhatsApp crawlers
        if (str_ends_with(strtolower($imagePath), '.svg')) {
            return $fallback;
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        return $base . '/' . ltrim($imagePath, '/');
    }

    /**
     * Generate Schema.org NewsArticle JSON-LD for cricket journalism.
     */
    public static function articleJsonLd(Article $article): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://www.cricbail.com'), '/');
        $articleUrl = static::canonicalUrl('/news/' . $article->slug);
        $imageUrl = static::socialImage($article->featured_image);

        $publishedDate = $article->published_at 
            ? $article->published_at->toIso8601String() 
            : now()->toIso8601String();

        $modifiedDate = $article->updated_at 
            ? $article->updated_at->toIso8601String() 
            : $publishedDate;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $articleUrl,
            ],
            'headline' => static::cleanHeadline($article->title),
            'description' => static::description($article->meta_description, $article->excerpt),
            'image' => [
                $imageUrl,
            ],
            'datePublished' => $publishedDate,
            'dateModified' => $modifiedDate,
            'author' => [
                '@type' => 'Person',
                'name' => $article->author ? $article->author->name : 'CricBail Editorial Bureau',
                'jobTitle' => 'Cricket Correspondent',
                'worksFor' => [
                    '@type' => 'Organization',
                    'name' => config('seo.site_name', 'CricBail'),
                ],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('seo.site_name', 'CricBail'),
                'url' => $base,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $base . '/assets/images/cricket/cricbail_share_card.jpg',
                    'width' => 1200,
                    'height' => 630,
                ],
            ],
            'articleSection' => $article->category ? $article->category->name : 'Cricket News',
            'inLanguage' => 'en-US',
            'url' => $articleUrl,
        ];
    }

    public static function newsArticleSchema(Article $article): array
    {
        return static::articleJsonLd($article);
    }

    /**
     * Generate Schema.org BreadcrumbList JSON-LD.
     * Supports both associative key-value pair and list of ['name' => ..., 'url' => ...] arrays.
     */
    public static function breadcrumbJsonLd(array $items): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://www.cricbail.com'), '/');
        $elements = [];
        $position = 1;

        foreach ($items as $key => $value) {
            if (is_array($value) && isset($value['name']) && isset($value['url'])) {
                $name = $value['name'];
                $url = $value['url'];
            } else {
                $name = $key;
                $url = $value;
            }

            $absUrl = str_starts_with($url, 'http') ? $url : $base . '/' . ltrim($url, '/');
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => $absUrl,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    public static function breadcrumbSchema(array $items): array
    {
        return static::breadcrumbJsonLd($items);
    }

    /**
     * Generate Schema.org WebSite JSON-LD with internal search action.
     */
    public static function websiteJsonLd(): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://www.cricbail.com'), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('seo.site_name', 'CricBail'),
            'url' => $base,
            'description' => config('seo.default_description'),
            'inLanguage' => 'en-US',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $base . '/news?search={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public static function websiteSchema(): array
    {
        return static::websiteJsonLd();
    }

    /**
     * Generate Schema.org Organization JSON-LD.
     */
    public static function organizationJsonLd(): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://www.cricbail.com'), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'NewsMediaOrganization',
            'name' => config('seo.site_name', 'CricBail'),
            'url' => $base,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $base . '/assets/images/cricket/cricbail_share_card.jpg',
                'width' => 1200,
                'height' => 630,
            ],
            'knowsAbout' => [
                'Cricket',
                'Indian Premier League',
                'International Cricket Council',
                'Test Cricket',
                'T20 Cricket',
            ],
        ];
    }

    public static function organizationSchema(): array
    {
        return static::organizationJsonLd();
    }

    /**
     * Generate Schema.org Quiz JSON-LD.
     */
    public static function quizJsonLd(Quiz $quiz): array
    {
        $quizUrl = static::canonicalUrl('/quizzes/' . $quiz->slug);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Quiz',
            'name' => $quiz->title,
            'description' => static::description($quiz->description),
            'url' => $quizUrl,
            'educationalLevel' => 'All Cricket Fans',
            'learningResourceType' => 'Quiz',
            'typicalAgeRange' => '10-',
            'about' => [
                '@type' => 'Thing',
                'name' => 'Cricket Trivia and History',
            ],
            'provider' => [
                '@type' => 'Organization',
                'name' => config('seo.site_name', 'CricBail'),
            ],
        ];
    }

    public static function quizSchema(Quiz $quiz): array
    {
        return static::quizJsonLd($quiz);
    }

    /**
     * Clean headline string for JSON-LD.
     */
    protected static function cleanHeadline(string $title): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($title)));
    }

    /**
     * Dynamic magic call to support instance method invocation via @inject('seo', 'App\Services\SeoService')
     */
    public function __call(string $method, array $parameters)
    {
        return static::$method(...$parameters);
    }
}
