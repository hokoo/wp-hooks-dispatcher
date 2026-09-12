<?php

declare(strict_types=1);

$wordpressRoot = getenv('WP_CORE_DIR');

if ($wordpressRoot === false || $wordpressRoot === '') {
    throw new RuntimeException('WP_CORE_DIR must point to a WordPress checkout.');
}

$wordpressRoot = rtrim($wordpressRoot, '/') . '/';

require_once __DIR__ . '/functions.php';

require_once $wordpressRoot . 'wp-includes/plugin.php';
require_once $wordpressRoot . 'wp-includes/load.php';
require_once $wordpressRoot . 'wp-includes/ms-blogs.php';
