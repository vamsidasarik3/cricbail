{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <!-- Static Main Pages -->
    @foreach($staticPages as $page)
    <url>
        <loc>{{ $page['url'] }}</loc>
        <lastmod>{{ $page['lastmod'] }}</lastmod>
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
    @endforeach

    <!-- Public Coverage Categories -->
    @foreach($categoryEntries as $cat)
    <url>
        <loc>{{ $cat['url'] }}</loc>
        <lastmod>{{ $cat['lastmod'] }}</lastmod>
        <changefreq>{{ $cat['changefreq'] }}</changefreq>
        <priority>{{ $cat['priority'] }}</priority>
    </url>
    @endforeach

    <!-- Published Articles -->
    @foreach($articleEntries as $art)
    <url>
        <loc>{{ $art['url'] }}</loc>
        <lastmod>{{ $art['lastmod'] }}</lastmod>
        <changefreq>{{ $art['changefreq'] }}</changefreq>
        <priority>{{ $art['priority'] }}</priority>
        @if(!empty($art['image']))
        <image:image>
            <image:loc>{{ $art['image'] }}</image:loc>
            <image:title>{{ htmlspecialchars($art['title'], ENT_XML1, 'UTF-8') }}</image:title>
        </image:image>
        @endif
    </url>
    @endforeach

    <!-- Cricket Quizzes Hub -->
    <url>
        <loc>{{ $quizHub['url'] }}</loc>
        <lastmod>{{ $quizHub['lastmod'] }}</lastmod>
        <changefreq>{{ $quizHub['changefreq'] }}</changefreq>
        <priority>{{ $quizHub['priority'] }}</priority>
    </url>

    <!-- Published Quizzes -->
    @foreach($quizEntries as $quiz)
    <url>
        <loc>{{ $quiz['url'] }}</loc>
        <lastmod>{{ $quiz['lastmod'] }}</lastmod>
        <changefreq>{{ $quiz['changefreq'] }}</changefreq>
        <priority>{{ $quiz['priority'] }}</priority>
    </url>
    @endforeach
</urlset>
