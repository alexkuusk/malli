Light Template Engine
=====

A simple and lightweight PHP templating engine using few custom tags that all are translated into pure PHP.
PHP is also allowed inside templates, just be aware of the scopes. Template blocks are compiled into PHP functions and functons are called and their output captured.

Possibility to add custom tags, for example translation code. That code's output can be turned into static and saved along with PHP code, so that heavy translating functions are not called every page load.
Supports multilanguage.

Blocks can be nested inside blocks.

Requires PHP version 8.2+

Setup
-----
See Example folder for more complex examples

```
composer install alexkuusk/malli
```

minimal
```php
require_once '../vendor/autoload.php';

use Alexkuusk\Malli\Malli;

echo (new Malli([
    '_params' => [
        'path' => '../tpl/',
        'file' => 'index.tpl',
        'block' => 'index', //if omitted, file name without extension is used
    ],
    '_data' => [
        'title' => 'Template test',
        'books' => [
            'One', 
            'two',
        ]
    ]));
```

You can also pass in an extension that will be appended to all template paths in Environment.

```tpl
{{ BLOCK:index }}
<html>
<head>
    <title>{{ $title }}</title>
</head> 
<body>
<table border=1> 
{{ FOREACH $books as $k => $title }}
    <tr>
        <td>{{ $k + 1 }}</td>
        <td>{{ htmlspecialchars($title) }}</td>
    </tr>
{{ /FOREACH }}
</table>
</body>
</html>
{{ /BLOCK:index }}
```
