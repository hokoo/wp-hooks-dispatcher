<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Contracts;

interface ActionHookGateway
{
    public function addAction(
        string $hook,
        callable $callback,
        int $priority,
        int $acceptedArguments
    ): void;

    public function removeAction(
        string $hook,
        callable $callback,
        int $priority
    ): void;
}
