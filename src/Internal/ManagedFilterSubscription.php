<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Internal;

use Closure;
use iTRON\wpHooksDispatcher\Contracts\FilterHookGateway;
use iTRON\wpHooksDispatcher\Contracts\SiteContextProvider;
use iTRON\wpHooksDispatcher\FilterSubscription;
use iTRON\wpHooksDispatcher\SiteContext;

/** @internal Created by FilterDispatcher; not part of the public API. */
final class ManagedFilterSubscription implements FilterSubscription
{
    private Closure $callback;

    private Closure $wrapper;

    private bool $subscribed = false;

    private function __construct(
        private FilterHookGateway $hooks,
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
        FilterHookGateway $hooks,
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

        $hooks->addFilter(
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

        $this->hooks->removeFilter($this->hook, $this->wrapper, $this->priority);
        $this->subscribed = false;
    }

    private function dispatch(mixed $value, mixed ...$arguments): mixed
    {
        if (
            !$this->subscribed
            || !$this->context->equals($this->contexts->current())
        ) {
            return $value;
        }

        return ($this->callback)($value, ...$arguments);
    }
}
