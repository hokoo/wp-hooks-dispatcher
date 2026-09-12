<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher;

use InvalidArgumentException;
use iTRON\wpHooksDispatcher\Contracts\FilterHookGateway;
use iTRON\wpHooksDispatcher\Contracts\SiteContextProvider;
use iTRON\wpHooksDispatcher\Internal\ManagedFilterSubscription;

final class FilterDispatcher
{
    private FilterHookGateway $hooks;

    private SiteContextProvider $contexts;

    public function __construct(
        ?FilterHookGateway $hooks = null,
        ?SiteContextProvider $contexts = null
    ) {
        $this->hooks = $hooks ?? new WordPressFilterHookGateway();
        $this->contexts = $contexts ?? new WordPressSiteContextProvider();
    }

    public function subscribe(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArguments = 1
    ): FilterSubscription {
        if ($acceptedArguments < 1) {
            throw new InvalidArgumentException(
                'Filter subscriptions must accept at least the filtered value.'
            );
        }

        return ManagedFilterSubscription::register(
            $this->hooks,
            $this->contexts,
            $this->contexts->current(),
            $hook,
            $callback,
            $priority,
            $acceptedArguments
        );
    }
}
