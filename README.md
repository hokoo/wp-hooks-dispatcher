# wp-hooks-dispatcher

Prevents a WordPress action callback created for one site from running while a
different multisite context is active.

## The problem

The WordPress hook registry is process-global. `switch_to_blog()` changes the
active site and database prefix, but it does not remove or scope callbacks that
were registered earlier:

```php
switch_to_blog(1);

$siteOneCleanup = create_cleanup_service_for_the_current_site();
add_action('deleted_post', [$siteOneCleanup, 'handle']);

switch_to_blog(2);

// The callback belonging to site 1 is still registered and will be called.
do_action('deleted_post', 42);
```

This matters when the callback retains a site-bound database adapter, client,
cache, or other service. In a long-running worker, multisite test process, CLI
command, or any application that switches sites dynamically, stale callbacks
can execute against the wrong context, fail unexpectedly, or keep retired
object graphs alive.

`add_action()` has no site-context boundary and returns no lifecycle handle for
the registration.

## What this package does

`ActionDispatcher::subscribe()` captures both the current WordPress blog ID and
`$wpdb->prefix`, then registers a stable wrapper with the native WordPress hook
registry. Every time the action fires, the wrapper checks the current values
before invoking application code:

- both values match: the consumer callback runs normally;
- either value differs: the callback is skipped;
- the captured context is restored: delivery resumes while still subscribed;
- the subscription is removed: delivery stops permanently for that handle.

WordPress still owns action dispatch, priority order, and accepted-argument
handling. Exceptions from an active callback pass through unchanged.

The package deliberately does not switch sites, create site-specific clients,
or rebind existing subscriptions. The consumer remains responsible for
initializing and subscribing a separate application object in every site
context it uses.

## When to use it

Use it for callbacks that retain site-specific state in a process that can call
`switch_to_blog()` after registration. Typical cases are multisite workers,
test suites, importers, queue consumers, and CLI processes.

It is usually unnecessary for a conventional isolated WordPress request that
never changes site, or for a callback that is intentionally context-neutral.

## Requirements

- PHP 8.1 or newer
- a loaded WordPress runtime providing `add_action()`, `remove_action()`,
  `get_current_blog_id()`, and a string `$wpdb->prefix`

The package has no Composer runtime dependencies beyond PHP. Non-standard or
isolated bootstraps can inject their own `ActionHookGateway` and
`SiteContextProvider` instead of using the native WordPress adapters.

## Install

```shell
composer require hokoo/wp-hooks-dispatcher
```

## Use

Create the dispatcher after WordPress is loaded. Subscribe each site-bound
callback while its intended site is active, and retain the returned handle for
explicit teardown:

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

The dispatcher registers its own wrapper, not the original consumer callback.
Consequently, `remove_action($hook, $originalCallback)` cannot remove this
registration; call `unsubscribe()` on the returned handle instead.

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
