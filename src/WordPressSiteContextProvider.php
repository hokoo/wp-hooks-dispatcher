<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher;

use iTRON\wpHooksDispatcher\Contracts\SiteContextProvider;
use LogicException;

final class WordPressSiteContextProvider implements SiteContextProvider
{
    public function current(): SiteContext
    {
        if (!function_exists('get_current_blog_id')) {
            throw new LogicException(
                'WordPress get_current_blog_id() is not available.'
            );
        }

        global $wpdb;

        if (!is_object($wpdb) || !is_string($wpdb->prefix ?? null)) {
            throw new LogicException('WordPress database prefix is not available.');
        }

        return new SiteContext(get_current_blog_id(), $wpdb->prefix);
    }
}
