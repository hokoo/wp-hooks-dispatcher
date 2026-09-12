<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Contracts;

interface FilterHookGateway
{
    public function addFilter(
        string $hook,
        callable $callback,
        int $priority,
        int $acceptedArguments
    ): void;

    public function removeFilter(
        string $hook,
        callable $callback,
        int $priority
    ): void;
}
