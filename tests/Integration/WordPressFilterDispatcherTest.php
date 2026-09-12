<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Integration;

use InvalidArgumentException;
use iTRON\wpHooksDispatcher\FilterDispatcher;
use RuntimeException;

final class WordPressFilterDispatcherTest extends WordPressIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/bootstrap.php';
    }

    public function testNativeRegistryPreservesOrderArgumentsAndValueChain(): void
    {
        $received = [];
        $dispatcher = new FilterDispatcher();

        $dispatcher->subscribe(
            'dispatcher_test',
            static function (
                string $value,
                string $suffix
            ) use (&$received): string {
                $received[] = ['late', $value, $suffix];

                return $value . '-late-' . $suffix;
            },
            20,
            2
        );
        $dispatcher->subscribe(
            'dispatcher_test',
            static function (string $value) use (&$received): string {
                $received[] = ['first-equal', $value];

                return $value . '-first-equal';
            },
            10,
            1
        );
        $dispatcher->subscribe(
            'dispatcher_test',
            static function (
                string $value,
                string $suffix,
                string $ignored
            ) use (&$received): string {
                $received[] = [
                    'second-equal',
                    $value,
                    $suffix,
                    $ignored,
                ];

                return $value . '-second-equal';
            },
            10,
            3
        );

        $result = apply_filters(
            'dispatcher_test',
            'original',
            'extra',
            'ignored',
            'not-forwarded'
        );

        self::assertSame(
            [
                ['first-equal', 'original'],
                [
                    'second-equal',
                    'original-first-equal',
                    'extra',
                    'ignored',
                ],
                [
                    'late',
                    'original-first-equal-second-equal',
                    'extra',
                ],
            ],
            $received
        );
        self::assertSame(
            'original-first-equal-second-equal-late-extra',
            $result
        );
    }

    public function testNativeRegistryPassesValueThroughAcrossContexts(): void
    {
        $calls = 0;
        $dispatcher = new FilterDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static function (string $value) use (&$calls): string {
                ++$calls;

                return $value . '-filtered';
            }
        );

        $GLOBALS['blog_id'] = 2;
        $blogMismatch = apply_filters('dispatcher_test', 'original');

        $GLOBALS['blog_id'] = 1;
        $GLOBALS['wpdb']->prefix = 'wp_2_';
        $prefixMismatch = apply_filters('dispatcher_test', 'original');

        $GLOBALS['wpdb']->prefix = 'wp_';
        $active = apply_filters('dispatcher_test', 'original');

        self::assertSame('original', $blogMismatch);
        self::assertSame('original', $prefixMismatch);
        self::assertSame('original-filtered', $active);
        self::assertSame(1, $calls);
    }

    public function testNativeRegistryTracksWordPressSwitchAndRestore(): void
    {
        $calls = 0;
        $dispatcher = new FilterDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static function (string $value) use (&$calls): string {
                ++$calls;

                return $value . '-filtered';
            }
        );

        switch_to_blog(2);
        $inactive = apply_filters('dispatcher_test', 'original');

        self::assertSame('original', $inactive);
        self::assertSame(0, $calls);
        self::assertSame(2, get_current_blog_id());
        self::assertSame('wp_2_', $GLOBALS['wpdb']->prefix);

        self::assertTrue(restore_current_blog());
        $restored = apply_filters('dispatcher_test', 'original');

        self::assertSame('original-filtered', $restored);
        self::assertSame(1, $calls);
        self::assertSame(1, get_current_blog_id());
        self::assertSame('wp_', $GLOBALS['wpdb']->prefix);
        self::assertSame([2, 1], $GLOBALS['wp_hooks_dispatcher_test_cache_switches']);
        self::assertSame([], $GLOBALS['_wp_switched_stack']);
        self::assertFalse($GLOBALS['switched']);
    }

    public function testNativeRegistrySelectsOnlyCurrentSiteSubscription(): void
    {
        $dispatcher = new FilterDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static fn (string $value): string => $value . '-site-one'
        );

        switch_to_blog(2);
        $dispatcher->subscribe(
            'dispatcher_test',
            static fn (string $value): string => $value . '-site-two'
        );

        $siteTwo = apply_filters('dispatcher_test', 'original');
        restore_current_blog();
        $siteOne = apply_filters('dispatcher_test', 'original');

        self::assertSame('original-site-two', $siteTwo);
        self::assertSame('original-site-one', $siteOne);
    }

    public function testNativeRemovalIsIdempotentAndStopsFiltering(): void
    {
        $dispatcher = new FilterDispatcher();
        $subscription = $dispatcher->subscribe(
            'dispatcher_test',
            static fn (string $value): string => $value . '-filtered'
        );

        $active = apply_filters('dispatcher_test', 'original');
        $subscription->unsubscribe();
        $subscription->unsubscribe();
        $removed = apply_filters('dispatcher_test', 'original');

        self::assertSame('original-filtered', $active);
        self::assertSame('original', $removed);
        self::assertFalse(has_filter('dispatcher_test'));
    }

    public function testNativeDispatchPropagatesOriginalFailure(): void
    {
        $failure = new RuntimeException('native dispatch failed');
        $dispatcher = new FilterDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static function (mixed $value) use ($failure): mixed {
                throw $failure;
            }
        );

        try {
            apply_filters('dispatcher_test', 'original');
            self::fail('The callback failure was not propagated.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught);
        }
    }

    public function testZeroAcceptedArgumentsAreRejectedBeforeRegistration(): void
    {
        $dispatcher = new FilterDispatcher();

        try {
            $dispatcher->subscribe(
                'dispatcher_test',
                static fn (): string => 'replacement',
                10,
                0
            );
            self::fail('Zero accepted arguments were not rejected.');
        } catch (InvalidArgumentException $caught) {
            self::assertSame(
                'Filter subscriptions must accept at least the filtered value.',
                $caught->getMessage()
            );
        }

        self::assertFalse(has_filter('dispatcher_test'));
    }
}
