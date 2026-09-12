<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Integration;

use InvalidArgumentException;
use iTRON\wpHooksDispatcher\FilterDispatcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class WordPressFilterDispatcherTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/bootstrap.php';
    }

    protected function setUp(): void
    {
        $GLOBALS['wp_filter'] = [];
        $GLOBALS['wp_actions'] = [];
        $GLOBALS['wp_filters'] = [];
        $GLOBALS['wp_current_filter'] = [];
        $GLOBALS['wp_hooks_dispatcher_test_blog_id'] = 1;

        $database = new stdClass();
        $database->prefix = 'wp_';
        $GLOBALS['wpdb'] = $database;
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

        $GLOBALS['wp_hooks_dispatcher_test_blog_id'] = 2;
        $blogMismatch = apply_filters('dispatcher_test', 'original');

        $GLOBALS['wp_hooks_dispatcher_test_blog_id'] = 1;
        $GLOBALS['wpdb']->prefix = 'wp_2_';
        $prefixMismatch = apply_filters('dispatcher_test', 'original');

        $GLOBALS['wpdb']->prefix = 'wp_';
        $active = apply_filters('dispatcher_test', 'original');

        self::assertSame('original', $blogMismatch);
        self::assertSame('original', $prefixMismatch);
        self::assertSame('original-filtered', $active);
        self::assertSame(1, $calls);
    }

    public function testNativeRegistrySelectsOnlyCurrentSiteSubscription(): void
    {
        $dispatcher = new FilterDispatcher();
        $dispatcher->subscribe(
            'dispatcher_test',
            static fn (string $value): string => $value . '-site-one'
        );

        $GLOBALS['wp_hooks_dispatcher_test_blog_id'] = 2;
        $GLOBALS['wpdb']->prefix = 'wp_2_';
        $dispatcher->subscribe(
            'dispatcher_test',
            static fn (string $value): string => $value . '-site-two'
        );

        $siteTwo = apply_filters('dispatcher_test', 'original');
        $GLOBALS['wp_hooks_dispatcher_test_blog_id'] = 1;
        $GLOBALS['wpdb']->prefix = 'wp_';
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
