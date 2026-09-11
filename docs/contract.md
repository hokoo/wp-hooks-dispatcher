# Public contract

The 1.x line of `wp-hooks-dispatcher` manages WordPress action subscriptions.
Filter subscriptions are intentionally outside the first stable release.

## Context identity

`ActionDispatcher::subscribe()` captures both values returned by the configured
`SiteContextProvider`: the WordPress blog ID and current database prefix. The
subscription compares both values again on every dispatch.

The consumer callback runs only when both values match exactly. A mismatched
context is a no-op: no consumer code runs and no value is synthesized. Restoring
the captured context restores delivery while the subscription remains active.

The dispatcher never calls `switch_to_blog()`, rebinds an existing subscription,
or initializes application objects for another site. The consumer remains
responsible for creating and subscribing the appropriate object while each site
context is active.

## Registration and ordering

The dispatcher passes the hook name, priority, and accepted-argument count to
WordPress unchanged. WordPress remains the registry and owns callback ordering,
including registration order at equal priorities. The package does not replace
or intercept the global hook registry.

## Lifetime

`subscribe()` returns an `ActionSubscription`. Its `unsubscribe()` method
removes the exact stable wrapper registered with WordPress. Unsubscription is
idempotent and terminal for that subscription; create a new subscription to
register again. Automatic destructor-based removal is deliberately unsupported
because the WordPress registry itself retains active callbacks.

## Failures

An exception or other `Throwable` raised by an active consumer callback passes
through unchanged. The dispatcher does not catch, log, normalize, or retry it.
Failures while reading the current context or invoking the WordPress gateway
also remain visible to the caller.

## Extensibility

`ActionHookGateway` and `SiteContextProvider` are injectable public boundaries
for tests and non-standard WordPress bootstraps. They do not allow a consumer to
change the captured identity of an existing subscription.
