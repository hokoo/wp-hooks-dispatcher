<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher;

use iTRON\wpHooksDispatcher\Contracts\ActionHookGateway;
use iTRON\wpHooksDispatcher\Contracts\SiteContextProvider;
use iTRON\wpHooksDispatcher\Internal\ManagedActionSubscription;

final class ActionDispatcher
{
    private ActionHookGateway $hooks;

    private SiteContextProvider $contexts;

    public function __construct(
        ?ActionHookGateway $hooks = null,
        ?SiteContextProvider $contexts = null
    ) {
        $this->hooks = $hooks ?? new WordPressActionHookGateway();
        $this->contexts = $contexts ?? new WordPressSiteContextProvider();
    }

    public function subscribe(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArguments = 1
    ): ActionSubscription {
        return ManagedActionSubscription::register(
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
