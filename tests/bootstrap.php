<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Base SQLite de test fraîche à chaque exécution
if ('test' === ($_SERVER['APP_ENV'] ?? null)) {
    @unlink(dirname(__DIR__).'/var/test.db');
}
