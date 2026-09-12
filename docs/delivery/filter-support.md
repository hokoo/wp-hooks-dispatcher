# Delivery plan: context-aware filter subscriptions

## Delivery status

- Epic E1 status: `review`
- Batch 1 (T1-T4): `review`; implementation and verification are complete,
  but the batch is not yet committed.
- Batch 2 (T5): `waiting_dependency`; it starts after Batch 1 is committed.
- Human approval: the 2026-09-12 request authorizes filter support alongside
  actions; the follow-up authorizes retrospective planning and documentation
  using the existing implementation as the baseline.
- Stop condition: publishing a version, creating a tag, or pushing changes is
  outside this plan and requires a separate owner decision.

## Intent

Goal: provide filter subscriptions with the same site-context isolation and
explicit lifecycle as action subscriptions while preserving native WordPress
filter behavior.

Audience: maintainers of long-running workers, multisite test processes, CLI
commands, and applications that switch WordPress sites after callbacks have
been registered.

Success criteria:

- Active filter callbacks receive the value and additional accepted arguments,
  and their result continues through the native filter chain.
- Inactive filter callbacks do not execute and return the current value
  unchanged.
- Blog ID and database prefix are both part of the captured context identity.
- Priority, equal-priority order, teardown, and failure propagation remain
  native or match the established action-dispatcher contract.
- The public contract, usage documentation, changelog, and package metadata all
  describe action and filter support consistently.
- Unit tests, code style checks, and integration tests against a real `WP_Hook`
  implementation pass on a supported PHP version.

Scope:

- A public `FilterDispatcher` and `FilterSubscription` API parallel to actions.
- Injectable filter registry and site-context boundaries.
- A stable internal wrapper with context-aware value pass-through.
- Unit and WordPress integration coverage.
- Consumer-facing and maintainer-facing documentation.

Out of scope:

- Refactoring or renaming the existing action API.
- Automatically switching sites or rebinding subscriptions.
- Supporting filter subscriptions with zero accepted arguments.
- Publishing a package version, tagging, pushing, or changing the release
  channel.
- Supporting WordPress runtimes older than those already compatible with the
  package contract.

Constraints and dependencies:

- PHP 8.1 or newer is required by the package.
- The default adapters require a loaded WordPress hook registry,
  `get_current_blog_id()`, and a string `$wpdb->prefix`.
- Integration verification requires a local WordPress checkout containing the
  real `wp-includes/plugin.php` and `WP_Hook` implementation.

## Technical decisions

### Parallel public API

Decision: expose `FilterDispatcher`, `FilterSubscription`, and
`FilterHookGateway` beside their action equivalents.

Reason: the additive API preserves the stable action contract, keeps action and
filter return semantics explicit, and retains simple dependency injection.

Alternatives considered:

- A single generic hook dispatcher would reduce class count but would mix
  `void` action delivery with value-returning filter delivery and risk a
  breaking public API change.
- Calling WordPress filter functions directly from the dispatcher would use
  fewer types but weaken isolated unit testing and diverge from the existing
  action boundary.

### Inactive filter semantics

Decision: an inactive wrapper returns the value it received without invoking
consumer code.

Reason: this is the only no-op behavior that preserves the WordPress filter
chain and allows callbacks at later priorities to receive the current value.

### Accepted-argument invariant

Decision: reject `acceptedArguments < 1` before registration.

Reason: an inactive wrapper must receive the current filtered value in order to
return it. With zero accepted arguments, WordPress withholds that value, so
context-safe pass-through cannot be implemented correctly.

### Delivery and release strategy

Decision: deliver the change as an additive, unreleased repository update.
Package publication and semantic version selection remain an owner-controlled
follow-up.

Rollback: revert the scoped delivery commits. The feature introduces no stored
data, migration, or runtime state outside native WordPress hook registrations.

## E1. Add filter parity to context-aware subscriptions

Outcome: consumers can register and explicitly remove site-bound WordPress
filters with the same isolation guarantees already available for actions.

Scope:

- Public and injectable filter subscription API.
- Context-matched callback execution and inactive value pass-through.
- Native ordering, argument forwarding, value chaining, and error behavior.
- Automated verification and complete public documentation.

Out of Scope:

- Changes to action behavior or public action types.
- Automatic multisite context management.
- Package release operations.

Success Criteria:

- Every intent-level success criterion above is verified.
- All T1-T4 task acceptance criteria and Definitions of Done pass.
- Independent epic-level QA reports `pass` with no unresolved findings.
- Scoped changes and delivery closure are committed.

Dependencies:

- Existing action-dispatcher contract and injectable site-context provider.
- Supported PHP runtime and local WordPress core for verification.

Risks/Open Questions:

- Consumers that intentionally use zero accepted filter arguments must keep
  native `add_filter()` registration; the dispatcher rejects that unsupported
  shape explicitly.
- The eventual release version and publication timing are deliberately
  unresolved because they are outside the approved scope.

Tasking Guidance:

- Re-run `$decompose-work` on this epic if scope, contracts, dependencies, or
  constraints change.
- Produce execution tasks with Status, Goal, Scope, Out of Scope, DoR, DoD, AC,
  Dependencies, and Notes/Risks.
- Assign `needs_design`, `waiting_dependency`, or `todo` from actual readiness;
  do not mark work `todo` until its DoR is satisfied.

## Dependency order

T1 establishes the documented contract. T2 implements it. T3 and T4 verify T2
independently at the unit and WordPress registry levels. T5 closes the epic only
after T1-T4 are committed and available for independent review.

## Execution tasks

### T1. Define and document the filter contract

Status: `review`

Goal: make filter behavior and its supported public surface unambiguous to
consumers and maintainers.

Scope:

- Document filter usage, context pass-through, lifecycle, extensibility, and
  the accepted-argument invariant.
- Update package description and unreleased changelog entries.
- Record the delivery intent, decisions, scope, and task backlog.

Out of Scope:

- Publishing API reference pages outside the repository.
- Selecting or publishing a release version.

DoR:

- The existing action contract is the accepted compatibility baseline.
- Filter callbacks must remain inert outside their captured site context.
- The minimum accepted-argument decision is recorded above.

DoD:

- README, public contract, changelog, package metadata, and this delivery plan
  agree on supported behavior.
- Documentation has no stale statement that filters are unsupported.
- Documentation changes are committed with the scoped batch.

AC:

- Given a consumer reads the README, when they configure a filter subscription,
  then they can identify the API, teardown method, and minimum accepted count.
- Given a maintainer reads the public contract, when the captured context is
  inactive, then the required value pass-through behavior is explicit.
- Given release notes are inspected, then filter support is listed as
  unreleased and no publication claim is made.

Dependencies:

- Source request and the existing action public contract.

Notes/Risks:

- Release numbering remains an owner decision.

Artifacts:

- `README.md`
- `docs/contract.md`
- `docs/delivery/filter-support.md`
- `CHANGELOG.md`
- `composer.json`

Verification:

- `composer validate --no-check-publish --strict`
- `git diff --check`

### T2. Implement context-aware filter subscriptions

Status: `review`

Goal: add production filter support without changing the existing action API.

Scope:

- Add the public dispatcher and subscription handle.
- Add the injectable filter gateway and native WordPress adapter.
- Add a managed wrapper that captures context, returns callback results while
  active, passes values through while inactive, and removes itself exactly.
- Reject accepted-argument counts below one before native registration.

Out of Scope:

- Refactoring action classes into shared generic base classes.
- Site switching, callback rebinding, retries, logging, or exception handling.

DoR:

- T1 defines the public contract and supported filter semantics.
- Existing action types remain backward compatible.

DoD:

- Production classes are PSR-4 autoloadable and pass project code style.
- Filter registration and removal delegate native metadata unchanged.
- Context checks, callback return values, and idempotent teardown implement the
  documented contract.
- Scoped implementation is committed.

AC:

- Given both context values match, when a filter runs, then the consumer return
  value becomes the next value in the chain.
- Given either context value differs, when a filter runs, then consumer code is
  skipped and the current value is returned unchanged.
- Given a subscription is removed twice, when the hook registry is inspected,
  then native removal occurred exactly once and delivery remains stopped.
- Given `acceptedArguments` is zero or negative, when subscription is attempted,
  then registration fails before the gateway is called.

Dependencies:

- T1 contract.
- Existing `SiteContextProvider` and `SiteContext` behavior.

Notes/Risks:

- The stable wrapper, rather than the original callback, owns native
  registration identity; consumers must retain the subscription handle.

Artifacts:

- `src/FilterDispatcher.php`
- `src/FilterSubscription.php`
- `src/Contracts/FilterHookGateway.php`
- `src/Internal/ManagedFilterSubscription.php`
- `src/WordPressFilterHookGateway.php`

Verification:

- `composer lint`
- Unit and integration tasks below.

### T3. Cover filter behavior with isolated unit tests

Status: `review`

Goal: verify the filter contract without depending on a WordPress runtime.

Scope:

- Add an in-memory filter gateway with native-like ordering and value chaining.
- Test metadata, accepted arguments, both context dimensions, restoration,
  teardown, ordering, failures, self-unsubscription, and invalid argument count.

Out of Scope:

- Reimplementing the complete WordPress hook registry.
- Performance or concurrency benchmarking.

DoR:

- T2 exposes injectable boundaries and the managed filter behavior.

DoD:

- New unit tests cover every listed scope item.
- Existing action tests remain green.
- The unit suite passes on a supported PHP runtime.
- Scoped tests are committed.

AC:

- Given the in-memory gateway, when filters have different or equal priorities,
  then their results are chained in priority and registration order.
- Given the context changes and is restored, when filters are applied each time,
  then only the restored context invokes the consumer callback.
- Given callback or lifecycle edge cases occur, then failure propagation,
  self-unsubscription, and idempotency match the public contract.

Dependencies:

- T2 implementation.

Notes/Risks:

- Native registry parity that cannot be proven by the test double belongs to T4.

Artifacts:

- `tests/Support/InMemoryFilterHookGateway.php`
- `tests/Unit/FilterDispatcherTest.php`

Verification:

- `composer test:unit`

### T4. Verify filters against the native WordPress hook registry

Status: `review`

Goal: prove that the wrapper composes correctly with the real `WP_Hook`
implementation.

Scope:

- Test ordering, accepted arguments, and value chaining through
  `apply_filters()`.
- Test both context mismatches, multiple site-bound subscriptions, native
  removal, error propagation, and rejection before registration.

Out of Scope:

- Full WordPress multisite bootstrapping or database integration.
- Browser, HTTP, or plugin activation testing.

DoR:

- T2 and T3 behavior is implemented and passes isolated verification.
- A local WordPress checkout is available to provide the real hook registry.

DoD:

- Integration tests exercise native `add_filter()`, `apply_filters()`,
  `remove_filter()`, and `has_filter()` behavior.
- Existing action integration tests remain green.
- The integration suite passes on a supported PHP runtime.
- Scoped integration tests are committed.

AC:

- Given multiple managed filters at mixed priorities, when native WordPress
  applies the hook, then results and additional arguments match native chaining.
- Given subscriptions captured for two sites, when each context is active, then
  only its callback changes the value.
- Given a removed or invalid subscription, when the registry is inspected, then
  no managed filter remains registered for that case.

Dependencies:

- T2 implementation.
- T3 passing unit suite.
- Local WordPress core checkout.

Notes/Risks:

- These tests isolate `WP_Hook`; they do not exercise database-backed
  `switch_to_blog()` behavior, which is outside the current test architecture.

Artifacts:

- `tests/Integration/WordPressFilterDispatcherTest.php`

Verification:

- `WP_CORE_DIR=/path/to/wordpress composer test:integration`

### T5. Run independent epic QA and close delivery

Status: `waiting_dependency`

Goal: independently verify E1 against its success criteria and leave a durable
delivery record.

Scope:

- Review committed T1-T4 code, tests, and documentation against their AC and
  DoD.
- Re-run relevant automated checks.
- Record QA outcome, residual risks, commit evidence, and final task statuses.

Out of Scope:

- Fixing product-visible findings without returning them to a scoped follow-up
  task.
- Publishing or pushing the release.

DoR:

- T1-T4 are committed and verified.
- An independent QA reviewer is available.

DoD:

- Independent QA reports `pass`, or the owner explicitly accepts any meaningful
  residual risk from `pass_with_notes`.
- Findings are either resolved or represented as execution-ready follow-up
  tasks.
- Epic and task statuses reflect committed delivery state.
- Delivery closure documentation is committed.

AC:

- Given the committed batch, when an independent reviewer checks every E1
  success criterion and task AC/DoD, then evidence supports a `pass` outcome.
- Given delivery closes, when repository history and this plan are inspected,
  then implementation, verification, QA, and residual risk are traceable.

Dependencies:

- T1-T4 completed and committed.
- Independent QA agent availability.

Notes/Risks:

- A `pass_with_notes` finding that is product-visible or operationally
  meaningful requires explicit human risk acceptance before epic closure.

Artifacts:

- QA report in the delivery closure section below.
- Scoped delivery commits.

Verification:

- `composer validate --no-check-publish --strict`
- `composer check`
- `WP_CORE_DIR=/path/to/wordpress composer test:integration`
- `git diff --check`

## Verification evidence

Pre-commit checks run on 2026-09-12:

- PHP 8.2.26: `composer check` passed.
- PHPCS: 22 files passed.
- Unit suite: 19 tests and 39 assertions passed.
- Integration suite using a local WordPress `WP_Hook`: 12 tests and 21
  assertions passed.
- `composer validate --no-check-publish --strict` passed.
- `git diff --check` passed.

## Delivery closure

Pending Batch 1 commit and independent epic-level QA.
