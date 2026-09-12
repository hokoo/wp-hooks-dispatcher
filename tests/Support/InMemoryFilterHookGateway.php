<?php

declare(strict_types=1);

namespace iTRON\wpHooksDispatcher\Tests\Support;

use iTRON\wpHooksDispatcher\Contracts\FilterHookGateway;

final class InMemoryFilterHookGateway implements FilterHookGateway
{
    /** @var list<array{hook: string, callback: callable, priority: int, accepted: int, order: int}> */
    private array $filters = [];

    private int $nextOrder = 0;

    public int $removeCalls = 0;

    public function addFilter(
        string $hook,
        callable $callback,
        int $priority,
        int $acceptedArguments
    ): void {
        $this->filters[] = [
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'accepted' => $acceptedArguments,
            'order' => $this->nextOrder++,
        ];
    }

    public function removeFilter(
        string $hook,
        callable $callback,
        int $priority
    ): void {
        ++$this->removeCalls;

        $this->filters = array_values(array_filter(
            $this->filters,
            static fn (array $filter): bool => !(
                $filter['hook'] === $hook
                && $filter['callback'] === $callback
                && $filter['priority'] === $priority
            )
        ));
    }

    public function applyFilters(
        string $hook,
        mixed $value,
        mixed ...$arguments
    ): mixed {
        $filters = array_values(array_filter(
            $this->filters,
            static fn (array $filter): bool => $filter['hook'] === $hook
        ));

        usort(
            $filters,
            static fn (array $left, array $right): int =>
                [$left['priority'], $left['order']]
                <=> [$right['priority'], $right['order']]
        );

        foreach ($filters as $filter) {
            $callbackArguments = array_slice(
                [$value, ...$arguments],
                0,
                $filter['accepted']
            );
            $value = ($filter['callback'])(...$callbackArguments);
        }

        return $value;
    }

    /** @return array{hook: string, callback: callable, priority: int, accepted: int, order: int} */
    public function filter(int $index): array
    {
        return $this->filters[$index];
    }

    public function count(): int
    {
        return count($this->filters);
    }
}
