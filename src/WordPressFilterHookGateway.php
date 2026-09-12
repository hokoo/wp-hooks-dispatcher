<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher;

use iTRON\wpHooksDispatcher\Contracts\FilterHookGateway;
use LogicException;

final class WordPressFilterHookGateway implements FilterHookGateway
{
    public function addFilter(
        string $hook,
        callable $callback,
        int $priority,
        int $acceptedArguments
    ): void {
        if (!function_exists('add_filter')) {
            throw new LogicException('WordPress add_filter() is not available.');
        }

        add_filter($hook, $callback, $priority, $acceptedArguments);
    }

    public function removeFilter(
        string $hook,
        callable $callback,
        int $priority
    ): void {
        if (!function_exists('remove_filter')) {
            throw new LogicException('WordPress remove_filter() is not available.');
        }

        remove_filter($hook, $callback, $priority);
    }
}
