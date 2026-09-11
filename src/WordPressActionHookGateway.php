<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher;

use iTRON\wpHooksDispatcher\Contracts\ActionHookGateway;
use LogicException;

final class WordPressActionHookGateway implements ActionHookGateway
{
    public function addAction(
        string $hook,
        callable $callback,
        int $priority,
        int $acceptedArguments
    ): void {
        if (!function_exists('add_action')) {
            throw new LogicException('WordPress add_action() is not available.');
        }

        add_action($hook, $callback, $priority, $acceptedArguments);
    }

    public function removeAction(
        string $hook,
        callable $callback,
        int $priority
    ): void {
        if (!function_exists('remove_action')) {
            throw new LogicException('WordPress remove_action() is not available.');
        }

        remove_action($hook, $callback, $priority);
    }
}
