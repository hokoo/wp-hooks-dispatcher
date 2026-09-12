<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Unit;

use InvalidArgumentException;
use iTRON\wpHooksDispatcher\FilterDispatcher;
use iTRON\wpHooksDispatcher\FilterSubscription;
use iTRON\wpHooksDispatcher\SiteContext;
use iTRON\wpHooksDispatcher\Tests\Support\InMemoryFilterHookGateway;
use iTRON\wpHooksDispatcher\Tests\Support\MutableSiteContextProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FilterDispatcherTest extends TestCase
{
    private InMemoryFilterHookGateway $hooks;

    private MutableSiteContextProvider $contexts;

    private FilterDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->hooks = new InMemoryFilterHookGateway();
        $this->contexts = new MutableSiteContextProvider(
            new SiteContext(1, 'wp_')
        );
        $this->dispatcher = new FilterDispatcher($this->hooks, $this->contexts);
    }

    public function testItPreservesWordPressRegistrationMetadata(): void
    {
        $this->dispatcher->subscribe(
            'example',
            static fn (mixed $value): mixed => $value,
            23,
            3
        );

        $filter = $this->hooks->filter(0);

        self::assertSame('example', $filter['hook']);
        self::assertSame(23, $filter['priority']);
        self::assertSame(3, $filter['accepted']);
    }

    public function testItReturnsCallbackResultAndForwardsAcceptedArguments(): void
    {
        $received = [];

        $this->dispatcher->subscribe(
            'example',
            static function (mixed ...$arguments) use (&$received): string {
                $received = $arguments;

                return 'filtered';
            },
            10,
            2
        );

        $result = $this->hooks->applyFilters(
            'example',
            'original',
            'second',
            'third'
        );

        self::assertSame(['original', 'second'], $received);
        self::assertSame('filtered', $result);
    }

    public function testItPassesValueThroughForDifferentBlogId(): void
    {
        $calls = 0;
        $this->dispatcher->subscribe(
            'example',
            static function (string $value) use (&$calls): string {
                ++$calls;

                return $value . '-filtered';
            }
        );

        $this->contexts->switchTo(new SiteContext(2, 'wp_'));
        $result = $this->hooks->applyFilters('example', 'original');

        self::assertSame(0, $calls);
        self::assertSame('original', $result);
    }

    public function testItPassesValueThroughForDifferentDatabasePrefix(): void
    {
        $calls = 0;
        $this->dispatcher->subscribe(
            'example',
            static function (string $value) use (&$calls): string {
                ++$calls;

                return $value . '-filtered';
            }
        );

        $this->contexts->switchTo(new SiteContext(1, 'wp_2_'));
        $result = $this->hooks->applyFilters('example', 'original');

        self::assertSame(0, $calls);
        self::assertSame('original', $result);
    }

    public function testItResumesFilteringWhenCapturedContextIsRestored(): void
    {
        $this->dispatcher->subscribe(
            'example',
            static fn (string $value): string => $value . '-filtered'
        );

        $this->contexts->switchTo(new SiteContext(2, 'wp_2_'));
        $inactiveResult = $this->hooks->applyFilters('example', 'original');
        $this->contexts->switchTo(new SiteContext(1, 'wp_'));
        $activeResult = $this->hooks->applyFilters('example', 'original');

        self::assertSame('original', $inactiveResult);
        self::assertSame('original-filtered', $activeResult);
    }

    public function testUnsubscribeUsesStableWrapperAndIsIdempotent(): void
    {
        $calls = 0;
        $subscription = $this->dispatcher->subscribe(
            'example',
            static function (string $value) use (&$calls): string {
                ++$calls;

                return $value . '-filtered';
            }
        );
        $registeredCallback = $this->hooks->filter(0)['callback'];

        $subscription->unsubscribe();
        $subscription->unsubscribe();

        self::assertSame(1, $this->hooks->removeCalls);
        self::assertSame(0, $this->hooks->count());
        self::assertSame('original', $registeredCallback('original'));
        self::assertSame(
            'original',
            $this->hooks->applyFilters('example', 'original')
        );
        self::assertSame(0, $calls);
    }

    public function testItDelegatesOrderingAndValueChainingToGateway(): void
    {
        $this->dispatcher->subscribe(
            'example',
            static fn (string $value): string => $value . '-late',
            20
        );
        $this->dispatcher->subscribe(
            'example',
            static fn (string $value): string => $value . '-first-equal',
            10
        );
        $this->dispatcher->subscribe(
            'example',
            static fn (string $value): string => $value . '-second-equal',
            10
        );

        $result = $this->hooks->applyFilters('example', 'original');

        self::assertSame(
            'original-first-equal-second-equal-late',
            $result
        );
    }

    public function testItPropagatesTheOriginalCallbackFailure(): void
    {
        $failure = new RuntimeException('callback failed');
        $this->dispatcher->subscribe(
            'example',
            static function (mixed $value) use ($failure): mixed {
                throw $failure;
            }
        );

        try {
            $this->hooks->applyFilters('example', 'original');
            self::fail('The callback failure was not propagated.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught);
        }
    }

    public function testCallbackCanUnsubscribeItself(): void
    {
        $calls = 0;
        $subscription = null;
        $subscription = $this->dispatcher->subscribe(
            'example',
            static function (string $value) use (
                &$calls,
                &$subscription
            ): string {
                ++$calls;
                self::assertInstanceOf(
                    FilterSubscription::class,
                    $subscription
                );
                $subscription->unsubscribe();

                return $value . '-filtered';
            }
        );

        $firstResult = $this->hooks->applyFilters('example', 'original');
        $secondResult = $this->hooks->applyFilters('example', 'original');

        self::assertSame('original-filtered', $firstResult);
        self::assertSame('original', $secondResult);
        self::assertSame(1, $calls);
    }

    public function testItRejectsZeroAcceptedArguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Filter subscriptions must accept at least the filtered value.'
        );

        $this->dispatcher->subscribe(
            'example',
            static fn (): string => 'replacement',
            10,
            0
        );
    }
}
