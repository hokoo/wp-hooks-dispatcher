<?php

declare(strict_types=1);

if (!function_exists('get_current_blog_id')) {
    function get_current_blog_id(): int
    {
        return $GLOBALS['wp_hooks_dispatcher_unit_blog_id'] ?? 1;
    }
}
