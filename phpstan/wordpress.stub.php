<?php

declare(strict_types=1);

/**
 * @param string $hookName
 * @param callable $callback
 * @param int $priority
 * @param int $acceptedArguments
 * @return bool
 */
function add_action(
    string $hookName,
    callable $callback,
    int $priority = 10,
    int $acceptedArguments = 1
): bool {
}

/**
 * @param string $hookName
 * @param callable $callback
 * @param int $priority
 * @return bool
 */
function remove_action(
    string $hookName,
    callable $callback,
    int $priority = 10
): bool {
}

/**
 * @param string $hookName
 * @param callable $callback
 * @param int $priority
 * @param int $acceptedArguments
 * @return bool
 */
function add_filter(
    string $hookName,
    callable $callback,
    int $priority = 10,
    int $acceptedArguments = 1
): bool {
}

/**
 * @param string $hookName
 * @param callable $callback
 * @param int $priority
 * @return bool
 */
function remove_filter(
    string $hookName,
    callable $callback,
    int $priority = 10
): bool {
}

/** @return int */
function get_current_blog_id(): int
{
}
