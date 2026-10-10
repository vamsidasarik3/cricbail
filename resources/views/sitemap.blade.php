{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Main Pages -->
    <url>
        <loc>{{ route('home') }}</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>hourly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('news.index') }}</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>hourly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ route('about') }}</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('contact') }}</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('privacy') }}</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>

    <!-- Categories -->
    @foreach($categories as $category)
    <url>
        <loc>{{ route('news.category', $category->slug) }}</loc>
        <lastmod>{{ $category->updated_at ? $category->updated_at->toIso8601String() : now()->toIso8601String() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    <!-- Published Articles -->
    @foreach($articles as $article)
    <url>
        <loc>{{ route('news.show', $article->slug) }}</loc>
        <lastmod>{{ $article->updated_at ? $article->updated_at->toIso8601String() : $article->published_at->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

    <!-- Cricket Quizzes Hub -->
    <url>
        <loc>{{ route('quizzes.index') }}</loc>
        <lastmod>{{ now()->toIso8601String() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Published Quizzes -->
    @foreach($quizzes as $quiz)
    <url>
        <loc>{{ route('quizzes.show', $quiz->slug) }}</loc>
        <lastmod>{{ $quiz->updated_at ? $quiz->updated_at->toIso8601String() : now()->toIso8601String() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
</urlset>
