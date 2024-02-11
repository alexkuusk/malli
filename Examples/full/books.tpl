{{ BLOCK:books }}
<h2>{{ $pageTitle }}</h2>
<table border=1> 
{{ FOREACH $books as $k => ['ean' => $ean, 'title' => $title, 'author' => $author] }}
    <tr>
        <td>{{ $k + 1 }}</td>
        <td>{{ htmlspecialchars($ean) }}</td>
        <td>{{ htmlspecialchars($title) }}</td>
        <td>{{ htmlspecialchars($author) }}</td>
    </tr>
{{ /FOREACH }}
</table>
{{ /BLOCK:books }}

