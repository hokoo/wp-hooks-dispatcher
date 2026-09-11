<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Unit;

use iTRON\wpHooksDispatcher\ActionDispatcher;
use iTRON\wpHooksDispatcher\ActionSubscription;
use iTRON\wpHooksDispatcher\SiteContext;
use iTRON\wpHooksDispatcher\Tests\Support\InMemoryActionHookGateway;
use iTRON\wpHooksDispatcher\Tests\Support\MutableSiteContextProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ActionDispatcherTest extends TestCase
{
    private InMemoryActionHookGateway $hooks;

    private MutableSiteContextProvider $contexts;

    private ActionDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->hooks = new InMemoryActionHookGateway();
        $this->contexts = new MutableSiteContextProvider(
            new SiteContext(1, 'wp_')
        );
        $this->dispatcher = new ActionDispatcher($this->hooks, $this->contexts);
    }

    public function testItPreservesWordPressRegistrationMetadata(): void
    {
        $this->dispatcher->subscribe('example', static function (): void {
        }, 23, 3);

        $action = $this->hooks->action(0);

        self::assertSame('example', $action['hook']);
        self::assertSame(23, $action['priority']);
        self::assertSame(3, $action['accepted']);
    }

    public function testItForwardsOnlyArgumentsAcceptedByWordPress(): void
    {
        $received = [];

        $this->dispatcher->subscribe(
            'example',
            static function (mixed ...$arguments) use (&$received): void {
                $received = $arguments;
            },
            10,
            2
        );

        $this->hooks->doAction('example', 'first', 'second', 'third');

        self::assertSame(['first', 'second'], $received);
    }

    public function testItDoesNotInvokeCallbackForDifferentBlogId(): void
    {
        $calls = 0;
        $this->dispatcher->subscribe(
            'example',
            static function () use (&$calls): void {
                ++$calls;
            }
        );

        $this->contexts->switchTo(new SiteContext(2, 'wp_'));
        $this->hooks->doAction('example');

        self::assertSame(0, $calls);
    }

    public function testItDoesNotInvokeCallbackForDifferentDatabasePrefix(): void
    {
        $calls = 0;
        $this->dispatcher->subscribe(
            'example',
            static function () use (&$calls): void {
                ++$calls;
            }
        );

        $this->contexts->switchTo(new SiteContext(1, 'wp_2_'));
        $this->hooks->doAction('example');

        self::assertSame(0, $calls);
    }

    public function testItResumesDeliveryWhenCapturedContextIsRestored(): void
    {
        $calls = 0;
        $this->dispatcher->subscribe(
            'example',
            static function () use (&$calls): void {
                ++$calls;
            }
        );

        $this->contexts->switchTo(new SiteContext(2, 'wp_2_'));
        $this->hooks->doAction('example');
        $this->contexts->switchTo(new SiteContext(1, 'wp_'));
        $this->hooks->doAction('example');

        self::assertSame(1, $calls);
    }

    public function testUnsubscribeUsesStableWrapperAndIsIdempotent(): void
    {
        $calls = 0;
        $subscription = $this->dispatcher->subscribe(
            'example',
            static function () use (&$calls): void {
                ++$calls;
            }
        );
        $registeredCallback = $this->hooks->action(0)['callback'];

        $subscription->unsubscribe();
        $subscription->unsubscribe();

        self::assertSame(1, $this->hooks->removeCalls);
        self::assertSame(0, $this->hooks->count());
        self::assertIsCallable($registeredCallback);
        $registeredCallback();
        $this->hooks->doAction('example');
        self::assertSame(0, $calls);
    }

    public function testItDelegatesPriorityAndEqualPriorityOrderToGateway(): void
    {
        $order = [];

        $this->dispatcher->subscribe(
            'example',
            static function () use (&$order): void {
                $order[] = 'late';
            },
            20
        );
        $this->dispatcher->subscribe(
            'example',
            static function () use (&$order): void {
                $order[] = 'first-equal';
            },
            10
        );
        $this->dispatcher->subscribe(
            'example',
            static function () use (&$order): void {
                $order[] = 'second-equal';
            },
            10
        );

        $this->hooks->doAction('example');

        self::assertSame(['first-equal', 'second-equal', 'late'], $order);
    }

    public function testItPropagatesTheOriginalCallbackFailure(): void
    {
        $failure = new RuntimeException('callback failed');
        $this->dispatcher->subscribe(
            'example',
            static function () use ($failure): void {
                throw $failure;
            }
        );

        try {
            $this->hooks->doAction('example');
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
            static function () use (&$calls, &$subscription): void {
                ++$calls;
                self::assertInstanceOf(ActionSubscription::class, $subscription);
                $subscription->unsubscribe();
            }
        );

        $this->hooks->doAction('example');
        $this->hooks->doAction('example');

        self::assertSame(1, $calls);
    }
}
