# Delivery plan: test and quality hardening

## Delivery status

- Epic E1 status: `in_progress`
- Approved priority: real multisite integration, adapter failure coverage,
  coverage enforcement, then static analysis.
- First execution batch: approved by the owner on 2026-09-13.
- Batch 1 (T1, T2, T4): completed in commit `c7a74bb`.
- Batch 2 (T3): completed and verified after Batch 1 raised production
  coverage to 100% of lines and methods, 80.39% of branches, and 76.92% of
  paths.
- Delivery branch: `codex/test-hardening`, created from the two local commits
  that were ahead of `origin/master`.
- Release, tagging, and direct pushes to `master` are out of scope. Delivery is
  through a pull request.

## Intent

Goal: make the package's central multisite guarantee and WordPress boundary
failures resistant to regression, then enforce the resulting quality level in
CI.

Audience: maintainers changing hook lifecycle, WordPress adapters, site
context detection, or supported runtime versions.

Success criteria:

- Integration tests call WordPress's real `switch_to_blog()` and
  `restore_current_blog()` implementations while exercising native `WP_Hook`.
- Failure behavior for unavailable WordPress functions and invalid `$wpdb`
  state is covered by isolated tests.
- CI fails when production line coverage falls below the accepted baseline.
- Static analysis runs at its strictest practical level without suppressing
  source defects through a baseline.
- PHP 8.1 and PHP 8.5 test runs, code style, workflow linting, dependency audit,
  and epic-level QA pass.

Scope:

- Tests and their focused WordPress integration harness.
- PHPUnit coverage configuration and a deterministic threshold checker.
- PHPStan configuration and Composer/CI commands.
- Maintainer documentation needed to run the checks.

Out of scope:

- Production API or runtime behavior changes.
- A database-backed full WordPress test installation when the native multisite
  lifecycle can be exercised deterministically without one.
- Subscription-scope or bulk teardown APIs.
- Performance benchmarking, package release, tags, and direct pushes to
  `master`.

Constraints and dependencies:

- PHP 8.1 remains the minimum supported runtime.
- Integration coverage must keep testing the configured WordPress version
  matrix without requiring network access at PHPUnit runtime.
- Coverage enforcement may use a single stable PHP/WordPress combination; the
  existing compatibility matrix remains responsible for version coverage.

## Technical decisions

### Focused native multisite harness

Load WordPress's real `ms-blogs.php` beside `plugin.php`, and provide only the
small cache and database-boundary doubles required by `switch_to_blog()`. This
exercises WordPress's actual switch/restore stack and hook dispatch without
adding a database service to a library whose production code performs no
queries.

### Coverage baseline

Enforce at least 90% production line coverage using a Clover report and a
repository-owned checker. Report branch/path coverage during QA, but do not
make it a merge gate until PHPUnit's CI driver/report format can enforce it
portably.

### Static analysis

Use PHPStan at the strictest passing level with explicit WordPress boundary
stubs/configuration. Do not add a generated baseline that could hide future
findings.

## E1. Harden regression detection and delivery gates

Outcome: the package's central context-isolation behavior and failure contract
are directly verified, and future regressions are rejected automatically.

Scope:

- Native multisite lifecycle integration coverage.
- WordPress adapter and context-provider failure tests.
- Coverage and static-analysis CI gates.
- Verification documentation and PR delivery.

Out of Scope:

- Runtime feature development or public API changes.
- Release and deployment operations.

Success Criteria:

- All intent-level success criteria pass.
- T1-T4 meet their acceptance criteria and Definitions of Done.
- Independent epic-level QA returns `pass` without unresolved findings.
- The scoped work and the two pre-existing local commits are published only on
  the feature branch and presented in a pull request.

Dependencies:

- Existing action/filter contract and integration harness.
- Owner approval recorded above.

Risks/Open Questions:

- WordPress internals may add new cache calls to multisite switching; the
  focused harness must fail visibly rather than silently bypassing them.
- The 90% line threshold is an initial regression floor, not a claim that line
  coverage alone proves behavior.

Tasking Guidance:

- Re-run `$decompose-work` if scope, contracts, dependencies, or constraints
  change.
- Execution tasks must retain Status, Goal, Scope, Out of Scope, DoR, DoD, AC,
  Dependencies, and Notes/Risks.
- Assign readiness from actual dependencies; do not implement non-ready work.

## Dependency order

T1 and T2 are independent and form Batch 1. T4 is also independently runnable.
T3 follows the resulting test surface so its threshold is based on the final
suite. T5 starts only after T1-T4 are committed and independently reviewable.

### T1. Exercise the native WordPress multisite lifecycle

Status: `completed`

Goal: verify action and filter subscriptions across real WordPress
switch/restore operations.

Scope:

- Load the native multisite switching implementation in integration bootstrap.
- Add the minimal deterministic cache/database harness it requires.
- Test inactive delivery after `switch_to_blog()` and resumed delivery after
  `restore_current_blog()` for actions and filters.

Out of Scope:

- Database queries, site creation, or the full WordPress PHPUnit suite.
- Production source changes.

DoR:

- The focused native harness decision above is approved as part of Batch 1.
- Existing integration tests define the expected context contract.

DoD:

- Tests use native `switch_to_blog()` and `restore_current_blog()` functions.
- Existing native hook-registry tests remain passing on the supported matrix.
- Test state is reset between cases.

AC:

- Given a subscription captured on site 1, when WordPress switches to site 2,
  then its action callback is skipped and its filter returns the input value.
- Given WordPress restores site 1, when the hooks fire again, then action and
  filter delivery resumes.
- Given the integration suite is run repeatedly, then switch-stack or global
  state does not leak between tests.

Dependencies:

- WordPress source checkout identified by `WP_CORE_DIR`.

Notes/Risks:

- The harness deliberately doubles persistence and cache boundaries while
  retaining WordPress's actual lifecycle implementation.

Verification:

- `composer test:integration`
- PHP 8.1 / WordPress 6.7.7 and PHP 8.5 / latest matrix runs.

### T2. Cover WordPress boundary failure paths

Status: `completed`

Goal: make documented runtime failures deterministic and regression-tested.

Scope:

- Test missing action/filter registration and removal functions.
- Test missing `get_current_blog_id()` and missing/invalid `$wpdb->prefix`.
- Cover `SiteContext` accessors and strict equality where useful.

Out of Scope:

- Changing exception classes/messages or production behavior.
- Testing PHP engine callable validation already enforced by type declarations.

DoR:

- Existing public failure contract remains authoritative.
- Tests can use process isolation where global PHP functions cannot be undone.

DoD:

- Every explicit `LogicException` branch in the WordPress adapters/provider is
  asserted.
- Tests remain order-independent and do not pollute integration globals.
- Unit suite passes on PHP 8.1 and PHP 8.5.

AC:

- Given a required WordPress function is absent, when its adapter is called,
  then the documented `LogicException` is raised.
- Given `$wpdb` or its prefix is invalid, when context is read, then the
  documented `LogicException` is raised.
- Given valid context values, when accessors/equality are used, then exact type
  and value semantics are preserved.

Dependencies:

- None beyond the approved contract.

Notes/Risks:

- Global functions require isolated-process tests to avoid suite pollution.

Verification:

- `composer test:unit`

### T3. Enforce production coverage in CI

Status: `completed`

Goal: prevent silent erosion of the verified production test surface.

Scope:

- Configure the production source coverage filter.
- Generate Clover coverage in one deterministic CI job.
- Add a repository-owned 90% line-coverage threshold check.
- Document the local coverage command.

Out of Scope:

- Uploading coverage to a third-party service.
- Making branch/path coverage a merge blocker in this batch.

DoR:

- T1 and T2 are complete so the accepted baseline reflects the final suite.

DoD:

- CI fails below 90% line coverage and passes at or above it.
- Coverage output is ignored and does not dirty the repository.
- Threshold-checker failure and success behavior are tested manually in QA.

AC:

- Given current tests, when the coverage job runs, then the report meets the
  threshold.
- Given a report below 90%, when the checker runs, then it exits non-zero with
  an actionable message.

Dependencies:

- T1 and T2.

Notes/Risks:

- The initial threshold preserves the current level; branch/path improvements
  remain visible QA metrics.

Verification:

- `composer test:coverage`

### T4. Add strict static analysis

Status: `completed`

Goal: detect type and boundary regressions that execution coverage cannot.

Scope:

- Add PHPStan as a development dependency.
- Add strict configuration for `src` and stable WordPress boundary knowledge.
- Run analysis from Composer and CI.

Out of Scope:

- Generated suppression baselines.
- Broad production refactors unrelated to genuine findings.

DoR:

- PHPStan is compatible with PHP 8.1 and the current Composer dependency set.

DoD:

- Strict analysis passes without ignored source errors or a baseline.
- `composer check` includes static analysis.
- CI quality job exercises the same command.

AC:

- Given the current production source, when `composer analyse` runs, then it
  exits successfully with zero findings.
- Given a detectable type error is introduced, then PHPStan reports it.

Dependencies:

- Composer package access during dependency update.

Notes/Risks:

- WordPress globals/functions require explicit stubs or a maintained extension.

Verification:

- `composer analyse`
- `composer check`

### T5. Run independent QA and deliver through PR

Status: `todo`

Goal: independently validate the epic and present all local work for review
without pushing directly to `master`.

Scope:

- Review T1-T4 against every AC and DoD.
- Re-run the supported runtime/WordPress matrix and quality checks.
- Commit closure/status documentation and open a pull request.

Out of Scope:

- Merging the pull request, releasing, or tagging.

DoR:

- T1-T4 are committed on the delivery branch.

DoD:

- Independent QA result is `pass`.
- Delivery plan records final task and epic status.
- Feature branch is pushed and a PR targets `master`.

AC:

- Given the committed branch, when QA evaluates it independently, then all
  success criteria are evidenced without relying on implementation claims.
- Given delivery is published, then `origin/master` remains unchanged and the
  PR contains both pre-existing local commits plus this hardening work.

Dependencies:

- T1-T4 and GitHub credentials.

Notes/Risks:

- Any non-trivial QA finding returns to a scoped follow-up task before closure.

Verification:

- Git diff/status/log inspection, full local matrix, and PR metadata review.
