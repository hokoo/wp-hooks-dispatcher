# wpHooksDispatcher

[![Latest Stable Version](https://img.shields.io/packagist/v/hokoo/wp-hooks-dispatcher.svg?style=flat-square)](https://packagist.org/packages/hokoo/wp-hooks-dispatcher)
[![Tests](https://img.shields.io/github/actions/workflow/status/hokoo/wp-hooks-dispatcher/ci.yml?branch=master&style=flat-square&label=tests)](https://github.com/hokoo/wp-hooks-dispatcher/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/badge/coverage-%E2%89%A590%25-brightgreen.svg?style=flat-square)](https://github.com/hokoo/wp-hooks-dispatcher/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/packagist/dependency-v/hokoo/wp-hooks-dispatcher/php.svg?style=flat-square)](https://packagist.org/packages/hokoo/wp-hooks-dispatcher)
[![License](https://img.shields.io/packagist/l/hokoo/wp-hooks-dispatcher.svg?style=flat-square)](https://github.com/hokoo/wp-hooks-dispatcher/blob/master/LICENSE)

Prevents a WordPress action or filter callback created for one site from
running while a different multisite context is active.

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

`add_action()` and `add_filter()` have no site-context boundary and return no
lifecycle handle for the registration.

## What this package does

`ActionDispatcher::subscribe()` and `FilterDispatcher::subscribe()` capture
both the current WordPress blog ID and `$wpdb->prefix`, then register a stable
wrapper with the native WordPress hook registry. Every time the hook fires, the
wrapper checks the current values before invoking application code:

- both values match: the consumer callback runs normally;
- either value differs: the callback is skipped (and filters pass the current
  value through unchanged);
- the captured context is restored: delivery resumes while still subscribed;
- the subscription is removed: delivery stops permanently for that handle.

WordPress still owns hook dispatch, priority order, filter value chaining, and
accepted-argument handling. Exceptions from an active callback pass through
unchanged.

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
- a loaded WordPress runtime providing the relevant `add_action()` and
  `remove_action()` or `add_filter()` and `remove_filter()` functions,
  `get_current_blog_id()`, and a string `$wpdb->prefix`

The package has no Composer runtime dependencies beyond PHP. Non-standard or
isolated bootstraps can inject their own `ActionHookGateway` or
`FilterHookGateway` and `SiteContextProvider` instead of using the native
WordPress adapters.

## Install

```shell
composer require hokoo/wp-hooks-dispatcher
```

## Use actions

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

## Use filters

Filter subscriptions use the same context and lifecycle rules. When the
captured context is inactive, the wrapper returns the current filtered value
unchanged so later callbacks continue to receive the correct value:

```php
use iTRON\wpHooksDispatcher\FilterDispatcher;

$dispatcher = new FilterDispatcher();

$subscription = $dispatcher->subscribe(
    'the_title',
    static fn (string $title, int $postId): string =>
        $title . ' #' . $postId,
    priority: 10,
    acceptedArguments: 2
);

$subscription->unsubscribe();
```

Filter subscriptions require `acceptedArguments` to be at least `1`. The
wrapper must receive the current filtered value in order to pass it through
safely when its captured context is inactive.

As with actions, remove a managed filter through its subscription handle rather
than by passing the original consumer callback to `remove_filter()`. See the
complete [public contract](docs/contract.md).

## Development

```shell
composer install
composer check
composer analyse
WP_CORE_DIR=/path/to/wordpress composer test:integration
XDEBUG_MODE=coverage WP_CORE_DIR=/path/to/wordpress composer test:coverage
```

Integration tests load WordPress's real `WP_Hook` implementation from the
checkout identified by `WP_CORE_DIR`, including its native multisite
switch/restore lifecycle. The coverage command requires Xdebug or PCOV and
enforces at least 90% production line coverage.

## License

MIT.
