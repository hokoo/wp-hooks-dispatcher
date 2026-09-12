# Changelog

All notable changes to this project will be documented in this file. The
project follows [Semantic Versioning](https://semver.org/).

## Unreleased

## 1.1.0 - 2026-09-13

- Add context-aware WordPress filter subscriptions.
- Preserve native filter value chaining, priority, accepted-argument, and
  ordering semantics.
- Pass the current filtered value through unchanged outside the captured site
  context.
- Add explicit, idempotent filter subscription teardown.
- Exercise WordPress's native multisite switch and restore lifecycle in the
  integration suite.
- Add strict static analysis and a production coverage gate to CI.

## 1.0.1 - 2026-09-12

- Explain the multisite callback-leakage problem and consumer ownership model
  directly in the README.

## 1.0.0 - 2026-09-12

- Add context-aware WordPress action subscriptions.
- Match both captured blog ID and database prefix on every dispatch.
- Preserve native priority, accepted-argument, and ordering semantics.
- Add explicit, idempotent subscription teardown.
- Cover the public contract with unit and real `WP_Hook` integration tests.
