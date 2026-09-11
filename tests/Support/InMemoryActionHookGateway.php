<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Support;

use iTRON\wpHooksDispatcher\Contracts\ActionHookGateway;

final class InMemoryActionHookGateway implements ActionHookGateway
{
    /** @var list<array{hook: string, callback: callable, priority: int, accepted: int, order: int}> */
    private array $actions = [];

    private int $nextOrder = 0;

    public int $removeCalls = 0;

    public function addAction(
        string $hook,
        callable $callback,
        int $priority,
        int $acceptedArguments
    ): void {
        $this->actions[] = [
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'accepted' => $acceptedArguments,
            'order' => $this->nextOrder++,
        ];
    }

    public function removeAction(
        string $hook,
        callable $callback,
        int $priority
    ): void {
        ++$this->removeCalls;

        $this->actions = array_values(array_filter(
            $this->actions,
            static fn (array $action): bool => !(
                $action['hook'] === $hook
                && $action['callback'] === $callback
                && $action['priority'] === $priority
            )
        ));
    }

    public function doAction(string $hook, mixed ...$arguments): void
    {
        $actions = array_values(array_filter(
            $this->actions,
            static fn (array $action): bool => $action['hook'] === $hook
        ));

        usort(
            $actions,
            static fn (array $left, array $right): int =>
                [$left['priority'], $left['order']]
                <=> [$right['priority'], $right['order']]
        );

        foreach ($actions as $action) {
            ($action['callback'])(
                ...array_slice($arguments, 0, $action['accepted'])
            );
        }
    }

    /** @return array{hook: string, callback: callable, priority: int, accepted: int, order: int} */
    public function action(int $index): array
    {
        return $this->actions[$index];
    }

    public function count(): int
    {
        return count($this->actions);
    }
}
