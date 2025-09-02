<?php
// tests/bootstrap.php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Charge les variables d'environnement (.env, .env.test, etc.)
if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Configure le mode debug
if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}