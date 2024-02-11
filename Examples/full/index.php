<?php

require_once '../vendor/autoload.php';

use Alexkuusk\Malli\Malli;
use Alexkuusk\Malli\MalliException;

$params = [
    '_params' => [
        'path' => '../tpl/', //you can set a brand or site template path; if omitted parent is used
        'file' => 'index.tpl', //template file name; may include sub path; if omitted parent is used
        'cachePath' => '../tmp/tpl/', //set different paths to http and https and to any separate host if template sharing and collisions are undesirable
        'block' => 'main', //block in template file. If block is omitted, basename of file will be used
        'language' => $_GET['lang'] ?? 'en', //make proper validation
    ],
    '_data' => [
        'title' => 'Template test',
        'name' => 'My Name',
        'subs' => [
            0 => [
                '_params' => [
                    'block' => 'sub_block',
                ],
                '_data' => [
                    'subject' => 'sub template 1',
                ],
            ],
            1 => [
                '_params' => [
                    'block' => 'sub_block',
                ],
                '_data' => [
                    'subject' => 'sub template 2',
                    'loop' => range(1, 10),
                ],
            ],
        ],
        'langs' => [
            '_params' => [
                'block' => 'lang_bar',
            ],
            '_data' => [
                'langs' => ['et' => 'eesti', 'en' => 'English', 'se' => 'Svenska',],
            ],
        ]
    ],
];


try {
    $booksPage = (new Malli([
        '_params' => ['path' => '../tpl/', 'file' => 'books.tpl', 'language' => $_GET['lang'] ?? 'en',], //file name without extension will be used as block name
        '_data' => [
            'pageTitle' => 'All my books',
            'books' => [
                [
                    'ean' => 12345,
                    'title' => 'George of the Jungle',
                    'author' => 'folklore',
                ],
                [
                    'ean' => 54312,
                    'title' => 'Presidency for Dummies',
                    'author' => 'George W. Bush',
                ],
            ]
        ],
    ]));

    function translate($str, $lang) {
        $translations = [
            'Hello' => [
                'en' => 'Hello',
                'et' => 'Tere',
                'se' => 'Hej',
            ],
            'no data' => [
                'en' => 'no data',
                'et' => 'pole andmeid',
                'se' => 'inga data',
            ],
        ];
        return $translations[$str][$lang] ?? $str;
    }

    $params['_data']['content'] = $booksPage;
    echo (new Malli())
        ->setParams($params['_params'])
        ->setCustomTag('/\{\{! (.+) !\}\}/Ums', 'echo translate($match[1], $PARSER->getLanguage());', true)
        ->setCustomTag('/####/Ums', ' ', false)
        ->setData($params['_data'])
        ->render();

    //echo (new Malli())->setData($params['_data'])->setParams($params['_params'])->render(); //data before params wont work
    //echo (new Malli($params)); //alternative
    //$parser = new Malli($params); echo $parser->render();
}
catch (MalliException $t)
{
    print_r($t->getMessage());
}
finally {}
