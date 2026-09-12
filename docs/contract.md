# Public contract

The `wp-hooks-dispatcher` package manages WordPress action and filter
subscriptions that are scoped to the site context in which they were created.

## Context identity

`ActionDispatcher::subscribe()` and `FilterDispatcher::subscribe()` capture
both values returned by the configured `SiteContextProvider`: the WordPress
blog ID and current database prefix. The subscription compares both values
again on every dispatch.

The consumer callback runs only when both values match exactly. For an action,
a mismatched context is a no-op. For a filter, it returns the current filtered
value unchanged so the native filter chain can continue. Restoring the captured
context restores delivery while the subscription remains active.

The dispatcher never calls `switch_to_blog()`, rebinds an existing subscription,
or initializes application objects for another site. The consumer remains
responsible for creating and subscribing the appropriate object while each site
context is active.

## Registration and ordering

The dispatcher passes the hook name, priority, and accepted-argument count to
WordPress unchanged. WordPress remains the registry and owns callback ordering,
including registration order at equal priorities and filter value chaining.
The package does not replace or intercept the global hook registry.

Filter subscriptions require an accepted-argument count of at least one. This
ensures the context wrapper receives the current filtered value and can return
it unchanged while inactive. Invalid counts fail before native registration.

## Lifetime

`ActionDispatcher::subscribe()` returns an `ActionSubscription`, and
`FilterDispatcher::subscribe()` returns a `FilterSubscription`. Their
`unsubscribe()` methods remove the exact stable wrapper registered with
WordPress. Unsubscription is idempotent and terminal for that subscription;
create a new subscription to register again. Automatic destructor-based removal
is deliberately unsupported because the WordPress registry itself retains
active callbacks.

## Failures

An exception or other `Throwable` raised by an active consumer callback passes
through unchanged. The dispatcher does not catch, log, normalize, or retry it.
Failures while reading the current context or invoking the WordPress gateway
also remain visible to the caller.

## Extensibility

`ActionHookGateway`, `FilterHookGateway`, and `SiteContextProvider` are
injectable public boundaries for tests and non-standard WordPress bootstraps.
They do not allow a consumer to change the captured identity of an existing
subscription.
