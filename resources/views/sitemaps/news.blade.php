{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
    @foreach($newsEntries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
        <news:news>
            <news:publication>
                <news:name>{{ htmlspecialchars($entry['publication_name'], ENT_XML1, 'UTF-8') }}</news:name>
                <news:language>{{ $entry['language'] }}</news:language>
            </news:publication>
            <news:publication_date>{{ $entry['published_at'] }}</news:publication_date>
            <news:title>{{ htmlspecialchars($entry['title'], ENT_XML1, 'UTF-8') }}</news:title>
        </news:news>
    </url>
    @endforeach
</urlset>
