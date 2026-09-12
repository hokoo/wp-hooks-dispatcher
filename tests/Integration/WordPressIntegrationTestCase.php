<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Integration;

use PHPUnit\Framework\TestCase;

abstract class WordPressIntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetWordPressGlobals();
    }

    protected function tearDown(): void
    {
        $this->resetWordPressGlobals();

        parent::tearDown();
    }

    private function resetWordPressGlobals(): void
    {
        $GLOBALS['wp_filter'] = [];
        $GLOBALS['wp_actions'] = [];
        $GLOBALS['wp_filters'] = [];
        $GLOBALS['wp_current_filter'] = [];
        $GLOBALS['_wp_switched_stack'] = [];
        $GLOBALS['switched'] = false;
        $GLOBALS['blog_id'] = 1;
        $GLOBALS['table_prefix'] = 'wp_';
        $GLOBALS['wp_hooks_dispatcher_test_cache_blog_id'] = 1;
        $GLOBALS['wp_hooks_dispatcher_test_cache_switches'] = [];
        $GLOBALS['wpdb'] = new NativeWordPressDatabase();
    }
}
