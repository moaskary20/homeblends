<?php

return [
    'supportedLocales' => [
        'ar' => [
            'name' => 'Arabic',
            'script' => 'Arab',
            'native' => 'العربية',
            'regional' => 'ar_EG',
        ],
    ],

    'useAcceptLanguageHeader' => false,

    'hideDefaultLocaleInURL' => true,

    'localesOrder' => ['ar'],

    'utf8suffix' => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),

    'urlsIgnored' => ['/admin', '/admin/*', '/affiliate', '/affiliate/*', '/api', '/api/*', '/livewire/*', '/up', '/sitemap.xml', '/robots.txt'],

    'httpMethodsIgnored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
];
