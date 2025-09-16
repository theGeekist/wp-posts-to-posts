# Modernization Plan: Posts 2 Posts

Date: 2025-09-17

## Ground Rules (Read Before Starting Any Task)
1. Every task MUST include appropriate automated tests (unit, integration, or e2e) where relevant. A task is only considered complete when accompanied by a Mini Release Notes subsection appended beneath the task (in this file or CHANGELOG) summarizing: scope, impact, migrations (if any), and rollback notes.
2. Simplification is the highest priority. Prefer reducing cognitive load over adding abstraction. Avoid premature patterns. Functions should be as pure as practical; isolate side effects (DB, I/O, global state) to narrow seams for testability.
3. Backward compatibility should be preserved until an explicit deprecation phase; emit warnings rather than silently altering behavior.
4. All new code must use namespacing, strict types (where feasible), and explicit return values; legacy style may remain only in shim layers.
5. Security & escaping: treat all output as unsafe by default. Adopt `esc_html()`, `esc_attr()`, `wp_kses_post()` or stricter.
6. Performance considerations must be documented for any query that could exceed O(log n) lookups or returns unbounded record sets.
7. Each phase completion requires: tests passing in CI matrix, updated documentation, version bump (semantic), and Mini Release Notes.
8. Prefer composition over inheritance going forward; avoid adding to large legacy classes—create focused services.
9. Add TODO comments only with an associated issue reference (once issue tracker formalized) to prevent orphaned debt.
10. No new global functions unless for backward compatibility shims (and mark them clearly as such in docblocks).

## Guiding Principles
- Preserve backward compatibility for existing integrations initially.
- Introduce modern layers (namespaces, REST, block UI) behind additive APIs.
- Ship in small, reviewable phases with CI enforcement.
- Avoid large rewrites that block adoption.

## Phase 0 – Baseline (Already Started)
- [x] Prevent fatal on missing dependencies (graceful admin notice).
- [ ] Document development setup (Composer, npm if needed) in README.
- [ ] Add `.editorconfig` and `.gitignore` tweaks if needed for new build artifacts.

## Phase 1 – Tooling & Quality
- [ ] Add `phpcs.xml.dist` with WordPress Coding Standards; generate baseline report.
- [ ] Add GitHub Actions workflow: PHPCS + PHPStan (level 0 baseline -> raise gradually).
- [ ] Add `phpstan.neon.dist` + baseline file.
- [ ] Add PHPUnit bootstrap refresh using `wp-phpunit/wp-phpunit`; ensure tests run on PHP 7.4–8.2.
- [ ] Introduce `composer scripts` for lint/test (`composer test`, `composer lint`).

## Phase 1.5 – Legacy Framework Sunset (scb-framework & Transitional Layer)
Goal: Begin detaching from `scb-framework` while keeping plugin operational.
- [ ] Inventory current scb class usages (AdminPage, BoxesPage, Options, Cron, Hooks) and map replacements.
- [ ] Create `src/Legacy/` adapters wrapping required behavior (e.g., Admin Page registration) using core WP APIs directly.
- [ ] Introduce feature flag `P2P_DISABLE_SCB` (defaults false) to toggle new bootstrap path.
- [ ] Migrate one non-critical scb-dependent component (e.g., tools page) to new structure as pilot.
- [ ] Add deprecation notices when scb classes are instantiated directly (triggered via `do_action('deprecated_class_run')`).
- [ ] Document migration strategy in `UPGRADE.md` draft.
- [ ] Add tests ensuring behavior parity (existing hooks still fire).
- [ ] Mini Release Notes: summarize replaced surface area & rollback toggle.

## Phase 2 – Namespacing & Autoloading
- [ ] Create `src/` directory with PSR-4 namespace `P2P\` via `composer.json` autoload section (keep legacy untouched).
- [ ] Add `P2P\Plugin` bootstrap class to encapsulate initialization.
- [ ] Wrap new REST + services in namespaced classes.
- [ ] Provide shim functions (legacy still calls old globals).

## Phase 3 – REST API Layer
- [ ] Register namespace `p2p/v1`.
- [ ] Endpoint: `GET /connections?post={id}` returns grouped by connection type.
- [ ] Endpoint: `POST /connections` create connection (validate nonce/capability).
- [ ] Endpoint: `DELETE /connections/{p2p_id}` delete connection.
- [ ] Endpoint: `POST /connections/{p2p_id}/meta` update meta (with whitelist & sanitization).
- [ ] Add permission callbacks and nonce/cap ability integration.
- [ ] Add unit tests (controller + schema) + documentation.

## Phase 4 – Block Editor Integration
- [ ] Add JS build tooling (`@wordpress/scripts`).
- [ ] Create `plugin-sidebar` panel (or `PluginDocumentSettingPanel`) for each connection type.
- [ ] Reusable `<ConnectionManager />` React component: search, select, order, remove.
- [ ] REST-backed debounced search (infinite scroll or pagination).
- [ ] Inline create (optional) via core data store and post type capabilities.
- [ ] Fallback: retain classic metabox (feature flag to disable when block UI stable).

## Phase 5 – Performance & Scalability
- [ ] Add indexes review migration (introduce `dbDelta` style upgrade if needed).
- [ ] Implement caching layer (object cache) for connection lookups.
- [ ] Add pagination to admin lists (avoid `-1` queries).
- [ ] Lazy load connection rows (AJAX/REST) after initial panel mount.
- [ ] Add CLI commands: warm cache, clean orphans, stats report.

## Phase 6 – Data & Type Safety
- [ ] Introduce value objects for Connection Type definitions.
- [ ] Add DTO / serializer for REST responses.
- [ ] Replace dynamic property mutations with structured arrays or typed properties.
- [ ] Add `#[AllowDynamicProperties]` temporarily where refactor is deferred (document deprecation timeline).

## Phase 7 – UI/UX Enhancements
- [ ] Add drag-and-drop ordering in React panel (persist to `p2p_order` meta field).
- [ ] Add filtering: by status, taxonomy, author.
- [ ] Surface connection meta editing (e.g., role/weight/label) through panel.
- [ ] Accessibility audit (focus management, ARIA roles, keyboard nav, color contrast).

## Phase 8 – Documentation & Adoption
- [ ] Update `README.md` with modern examples (register, query, REST usage, block usage).
- [ ] Add `UPGRADE.md` (migration notes + deprecations schedule).
- [ ] Architecture diagram refresh (include sequence for REST create operation).
- [ ] Changelog entries with semantic versioning (start at 2.0.0 for major modernization?).

## Phase 9 – Optional Integrations
- [ ] WPGraphQL adapter (schema extension mapping p2p connections as fields).
- [ ] Integration tests with popular plugins (ACF, CPT UI).
- [ ] Performance benchmark harness (store metrics before/after caching changes).

## Phase 10 – Deprecation & Cleanup
- [ ] Emit `_doing_it_wrong()` notices for legacy internal-only functions (document replacements).
- [ ] Migrate Mustache templates to React or PHP partials (retire Mustache dependency if unused elsewhere).
- [ ] Remove Backbone assets after confirmed no longer needed.

## Stretch Goals
- [ ] Connection taxonomy (group connections logically / tag them).
- [ ] Relationship graph visualizer (admin page using force-directed layout). 
- [ ] Bulk operations UI (connect many posts to one target in wizard flow).

## Risk & Mitigation Highlights
| Risk | Phase Mitigation |
|------|------------------|
| Breaking existing hooks | Layered bootstrap & shims (Phases 2–4) |
| Performance regression with REST | Cache & pagination (Phase 5) |
| Tech debt grows during transition | Enforce CI gates early (Phase 1) |
| Dynamic property deprecations | Incremental refactor (Phase 6) |

## Sequencing Notes
Phases may overlap; ensure Phase 1 (tooling) is merged before adding large code. Block editor work (Phase 4) depends on stable REST endpoints (Phase 3). Performance improvements (Phase 5) should follow measurement baselines captured during Phase 1/2.

## Quick Start After Modernization (Target Example)
```php
// Register in functions.php or a small plugin
add_action('p2p_init', function() {
    p2p_register_connection_type([
        'name' => 'book_to_author',
        'from' => 'book',
        'to'   => 'author',
        'cardinality' => 'many-to-many',
        'admin_box' => [ 'context' => 'side' ],
    ]);
});

// REST fetch (future)
// GET /wp-json/p2p/v1/connections?post=123
```

## Acceptance Criteria per Phase
Each phase should ship with:
- Passing CI (lint + tests)
- Changelog update
- Documentation snippet
- Version bump where appropriate

---
Prepared for modernization planning. See `NOTES.md` for detailed audit rationale.
