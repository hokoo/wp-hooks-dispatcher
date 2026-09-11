# wp-hooks-dispatcher

Context-aware WordPress action subscriptions for long-running processes,
multisite tests, and applications that reuse one process across site contexts.

An action runs only while the current WordPress blog ID and database prefix both
match the context captured when it was subscribed. WordPress still owns the
hook registry and callback order.

## Requirements

- PHP 8.1 or newer
- a loaded WordPress Plugin API (`add_action()` / `remove_action()`)

The package has no runtime dependencies beyond PHP and WordPress's hook API.

## Install

```shell
composer require hokoo/wp-hooks-dispatcher
```

## Use

Create the dispatcher after WordPress is loaded, then retain the returned
subscription for explicit lifecycle control:

```php
use iTRON\wpHooksDispatcher\ActionDispatcher;

$dispatcher = new ActionDispatcher();

$subscription = $dispatcher->subscribe(
    'deleted_post',
    static function (int $postId): void {
        // Work that belongs only to the site active at subscription time.
    },
    priority: 10,
    acceptedArguments: 1
);

$subscription->unsubscribe();
$subscription->unsubscribe(); // Safe: unsubscription is idempotent.
```

A callback is skipped when either context dimension differs, and becomes
eligible again if the captured context is restored before unsubscription.
Failures from an active callback propagate unchanged. The dispatcher never
switches WordPress sites or rebinds an existing subscription; application code
must initialize its own site-specific objects in the intended context.

The first stable release manages actions only. Filter semantics are deliberately
reserved for a future additive release. See the complete
[public contract](docs/contract.md).

## Development

```shell
composer install
composer check
WP_CORE_DIR=/path/to/wordpress composer test:integration
```

Integration tests load WordPress's real `WP_Hook` implementation from the
checkout identified by `WP_CORE_DIR`.

## License

MIT.
