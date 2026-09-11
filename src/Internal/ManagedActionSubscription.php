<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Internal;

use Closure;
use iTRON\wpHooksDispatcher\ActionSubscription;
use iTRON\wpHooksDispatcher\Contracts\ActionHookGateway;
use iTRON\wpHooksDispatcher\Contracts\SiteContextProvider;
use iTRON\wpHooksDispatcher\SiteContext;

/** @internal Created by ActionDispatcher; not part of the public API. */
final class ManagedActionSubscription implements ActionSubscription
{
    private Closure $callback;

    private Closure $wrapper;

    private bool $subscribed = false;

    private function __construct(
        private ActionHookGateway $hooks,
        private SiteContextProvider $contexts,
        private SiteContext $context,
        private string $hook,
        callable $callback,
        private int $priority
    ) {
        $this->callback = Closure::fromCallable($callback);
        $this->wrapper = Closure::fromCallable([$this, 'dispatch']);
    }

    public static function register(
        ActionHookGateway $hooks,
        SiteContextProvider $contexts,
        SiteContext $context,
        string $hook,
        callable $callback,
        int $priority,
        int $acceptedArguments
    ): self {
        $subscription = new self(
            $hooks,
            $contexts,
            $context,
            $hook,
            $callback,
            $priority
        );

        $hooks->addAction(
            $hook,
            $subscription->wrapper,
            $priority,
            $acceptedArguments
        );
        $subscription->subscribed = true;

        return $subscription;
    }

    public function unsubscribe(): void
    {
        if (!$this->subscribed) {
            return;
        }

        $this->hooks->removeAction($this->hook, $this->wrapper, $this->priority);
        $this->subscribed = false;
    }

    private function dispatch(mixed ...$arguments): void
    {
        if (
            !$this->subscribed
            || !$this->context->equals($this->contexts->current())
        ) {
            return;
        }

        ($this->callback)(...$arguments);
    }
}
