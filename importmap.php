<?php

/**
 * Importmap AssetMapper.
 * Après composer install : php bin/console importmap:install
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'admin' => [
        'path' => './assets/admin.js',
        'entrypoint' => true,
    ],
    'sortablejs' => [
        'version' => '1.15.6',
    ],
];
