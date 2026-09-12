<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', rtrim((string) getenv('WP_CORE_DIR'), '/') . '/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!function_exists('wp_cache_switch_to_blog')) {
    function wp_cache_switch_to_blog(int $blogId): void
    {
        $GLOBALS['wp_hooks_dispatcher_test_cache_blog_id'] = $blogId;
        $GLOBALS['wp_hooks_dispatcher_test_cache_switches'][] = $blogId;
    }
}
