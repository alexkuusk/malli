{{ BLOCK:main }}
<html>
<head>
    <title>{{ $title }}</title>
</head> 
<body>
    {{ $langs }}
    <h1>{{! Hello !}} {{ $name }}!</h1>
    {{ $content }}
    <hr>
    <table border=1>
    <?php foreach ($subs as $k => $sub) { ?>
        <tr>
            <td>{{ $k+1 }}</td><td>{{ $sub }}
                {{ BLOCK:sub_block }}####
                    Here is a parent block var: {{ $PARSER->getParent()->getData()['name'] }}</td>
                    {{ BLOCK:sub3_block }}Just a random third level block that is not rendered{{ /BLOCK:sub3_block }}
                    <td>{{ $subject }}: {{ (isset($loop)) ? implode(', ', $loop) : translate('no data', $PARSER->getLanguage()) }}
                {{ /BLOCK:sub_block }}
            </td>
            <td>
            </td>
           </tr>
    <?php } ?>
    </table>
    {{ $langs }}
</body>
</html>
{{ /BLOCK:main }}

{{ BLOCK:lang_bar }}
<div style="background-color: #eee; text-align: right">
    {{ FOREACH $langs as $short => $lang }}
        <a href="?lang={{ $short }}" style="font-weight:{{ IF $short == $PARSER->getLanguage() }}bold{{ ELSE }}normal{{ /IF }}">{{ htmlentities($lang) }}</a>
        {{ IF $short != array_key_last($langs) }} | {{ / }}
    {{ /FOREACH }}
</div>
{{ /BLOCK:lang_bar }}






