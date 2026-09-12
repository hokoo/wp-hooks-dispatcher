<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Integration;

use iTRON\wpHooksDispatcher\ActionDispatcher;
use RuntimeException;

final class WordPressActionDispatcherTest extends WordPressIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/bootstrap.php';
    }

    public function testNativeRegistryPreservesPriorityOrderAndAcceptedArguments(): void
    {
        $received = [];
        $dispatcher = new ActionDispatcher();

        $dispatcher->subscribe(
            'dispatcher_test',
            static function (mixed ...$arguments) use (&$received): void {
                $received[] = ['late', $arguments];
            },
            20,
            2
        );
        $dispatcher->subscribe(
            'dispatcher_test',
            static function (mixed ...$arguments) use (&$received): void {
                $received[] = ['first-equal', $arguments];
            },
            10,
            1
        );
        $dispatcher->subscribe(
            'dispatcher_test',
            static function (mixed ...$arguments) use (&$received): void {
                $received[] = ['second-equal', $arguments];
            },
            10,
            3
        );

        do_action('dispatcher_test', 'one', 'two', 'three', 'four');

        self::assertSame(
            [
                ['first-equal', ['one']],
                ['second-equal', ['one', 'two', 'three']],
                ['late', ['one', 'two']],
            ],
            $received
        );
    }

    public function testNativeRegistryNoopsAcrossBothContextDimensions(): void
    {
        $calls = 0;
        $dispatcher = new ActionDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static function () use (&$calls): void {
                ++$calls;
            }
        );

        $GLOBALS['blog_id'] = 2;
        do_action('dispatcher_test');

        $GLOBALS['blog_id'] = 1;
        $GLOBALS['wpdb']->prefix = 'wp_2_';
        do_action('dispatcher_test');

        $GLOBALS['wpdb']->prefix = 'wp_';
        do_action('dispatcher_test');

        self::assertSame(1, $calls);
    }

    public function testNativeRegistryTracksWordPressSwitchAndRestore(): void
    {
        $calls = 0;
        $dispatcher = new ActionDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static function () use (&$calls): void {
                ++$calls;
            }
        );

        switch_to_blog(2);
        do_action('dispatcher_test');

        self::assertSame(0, $calls);
        self::assertSame(2, get_current_blog_id());
        self::assertSame('wp_2_', $GLOBALS['wpdb']->prefix);

        self::assertTrue(restore_current_blog());
        do_action('dispatcher_test');

        self::assertSame(1, $calls);
        self::assertSame(1, get_current_blog_id());
        self::assertSame('wp_', $GLOBALS['wpdb']->prefix);
        self::assertSame([2, 1], $GLOBALS['wp_hooks_dispatcher_test_cache_switches']);
        self::assertSame([], $GLOBALS['_wp_switched_stack']);
        self::assertFalse($GLOBALS['switched']);
    }

    public function testNativeRegistryHonorsZeroAcceptedArguments(): void
    {
        $receivedCount = null;
        $dispatcher = new ActionDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static function (mixed ...$arguments) use (&$receivedCount): void {
                $receivedCount = count($arguments);
            },
            10,
            0
        );

        do_action('dispatcher_test', 'not-forwarded');

        self::assertSame(0, $receivedCount);
    }

    public function testNativeRegistrySelectsOnlyTheCurrentSiteSubscription(): void
    {
        $calls = [];
        $dispatcher = new ActionDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static function () use (&$calls): void {
                $calls[] = 'site-one';
            }
        );

        switch_to_blog(2);
        $dispatcher->subscribe(
            'dispatcher_test',
            static function () use (&$calls): void {
                $calls[] = 'site-two';
            }
        );

        do_action('dispatcher_test');
        restore_current_blog();
        do_action('dispatcher_test');

        self::assertSame(['site-two', 'site-one'], $calls);
    }

    public function testNativeRemovalIsIdempotentAndStopsDelivery(): void
    {
        $calls = 0;
        $dispatcher = new ActionDispatcher();
        $subscription = $dispatcher->subscribe(
            'dispatcher_test',
            static function () use (&$calls): void {
                ++$calls;
            }
        );

        do_action('dispatcher_test');
        $subscription->unsubscribe();
        $subscription->unsubscribe();
        do_action('dispatcher_test');

        self::assertSame(1, $calls);
        self::assertFalse(has_action('dispatcher_test'));
    }

    public function testNativeDispatchPropagatesOriginalFailure(): void
    {
        $failure = new RuntimeException('native dispatch failed');
        $dispatcher = new ActionDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static function () use ($failure): void {
                throw $failure;
            }
        );

        try {
            do_action('dispatcher_test');
            self::fail('The callback failure was not propagated.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught);
        }
    }
}
