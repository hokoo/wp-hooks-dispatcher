<?php

declare(strict_types=1);

$wordpressRoot = getenv('WP_CORE_DIR');

if ($wordpressRoot === false || $wordpressRoot === '') {
    throw new RuntimeException('WP_CORE_DIR must point to a WordPress checkout.');
}

require_once rtrim($wordpressRoot, '/') . '/wp-includes/plugin.php';

require_once __DIR__ . '/functions.php';
