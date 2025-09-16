# Posts 2 Posts Modernization Notes

Date: 2025-09-17

## 1. Fatal Activation Error (Resolved)
**Original Error:** `Call to undefined function scb_init()` in `posts-to-posts.php`.
**Cause:** `vendor/` dependencies (scb-framework) not loaded prior to calling `scb_init()`. On fresh checkout without `composer install`, the function is undefined and causes a fatal during activation.
**Fix Applied:** Added defensive checks in `posts-to-posts.php`:
- Guarded inclusion of Mustache + scb-framework only if files exist.
- Fallback admin notice if dependencies are missing instead of hard fatal.
**Remaining Risk:** If composer dependencies are partial or outdated, functionality will degrade. Consider bundling a minimal internal loader or switching to Composer’s autoloader directly.

## 2. Dependency Stack
- Relies on legacy **scb-framework** (dynamic loader pattern, pre-namespaces).
- Uses **Mustache 2.x** (OK but somewhat dated; could migrate small templates to native output buffering or modern JS-driven components in React for block editor).
- Library `scribu/lib-posts-to-posts` contains core API (connection registration, querying, metadata schema).

## 3. Database Layer & Schema
- Two custom tables inferred: `$wpdb->p2p` and `$wpdb->p2pmeta` (created via storage installer in library, not inside this repo’s root code). Need to confirm install logic in `P2P_Storage` within the lib (not yet inspected in depth).
- Deletion queries build `WHERE p2p_id IN (...)` using sanitized integers (`absint`). Acceptable but could switch to `$wpdb->prepare` for consistency/readability.
- No explicit schema upgrades for utf8mb4 or indexes review. Potential performance gains: composite indexes on `(p2p_type,p2p_from)` and `(p2p_type,p2p_to)` and `(p2p_from,p2p_to)` if not already present.

## 4. PHP Version Compatibility
Observed patterns:
- No modern scalar type hints, return types, or strict types (expected for legacy code).
- Dynamic properties set on objects returned by custom classes (e.g. `$item->title = ...` in `admin/box.php`). PHP 8.2 deprecates dynamic properties on non-`stdClass` objects unless `#[AllowDynamicProperties]` or magic methods used. Potential future warnings.
- Anonymous functions exist only minimally (closure for admin notice added now) – acceptable.
- No union types; OK but opportunities for clarity.

## 5. Security Review (Surface)
- Admin UI uses nonces (`P2P_BOX_NONCE`). Need to audit nonce verification on create / delete / reorder endpoints (not inspected yet: likely AJAX handlers in the library or admin classes).
- Mustache templates loaded from filesystem—safe if unmodified; Mustache is logic-less so low risk of code injection.
- SQL: Direct `DELETE` queries constructed from sanitized ints—OK. Still convert to `$wpdb->query( $wpdb->prepare( ... ) )` pattern for consistency.
- Escaping: Output handled through Mustache, but some raw HTML assembly occurs (e.g., data attributes concatenated). Should wrap attributes with `esc_attr()` and textual output with `esc_html()` for modern standards.

## 6. Performance Considerations
- Metabox loads all connected items with `p2p:per_page => -1` – problematic for large cardinality (scales poorly). Should add pagination or lazy loading via REST endpoints.
- Uses multiple small templates + Mustache parse each load; could precompile or unify.
- No object caching integration. Opportunities: store resolved connection IDs in transient or object cache layers keyed by `(p2p_type,object_id,direction)`.

## 7. Extensibility & API Layer
- API is procedural (functions `p2p_register_connection_type`, `p2p_get_connections`, etc.). Maintain for backward compatibility but introduce a namespaced facade `PostsToPosts\Connections` for new code.
- No dedicated REST API endpoints. Modern integrations (headless WP / block editor UI) would benefit from endpoints to:
  - List connection types
  - Query connections for an object
  - Create / delete connections
  - Update connection meta

## 8. Block Editor (Gutenberg) Gaps
- Current UX is classic metabox (Backbone + Mustache). In block editor contexts, classic metabox is supported but inferior UX:
  - No inline search with async while typing using REST.
  - No React components or data store integration (`@wordpress/data`).
- Suggested features:
  - Block Sidebar Panel (PluginDocumentSettingPanel) for each registered connection type.
  - Reusable `ConnectionSelector` React component with debounced search (WP REST or custom endpoint) and multi-select.
  - Inline creation (quick create) using standard `wp.data.dispatch('core').saveEntityRecord()`.

## 9. Internationalization
- Uses `load_plugin_textdomain` correctly.
- Some strings may lack context or escaping in admin JS localization (review needed). Provide `/* translators: */` comments where ambiguous.

## 10. Code Organization & Maintainability
Pain points:
- Mixed concerns: bootstrap code + dependency checks in main file.
- Admin UI logic interwoven with rendering logic (e.g. `P2P_Box` both fetches data and renders Mustache templates).
- Lack of interfaces or abstractions for storage vs presentation.
- No PSR-4 autoloading / namespaces; reliant on custom autoloaders.

Modern restructuring proposal (incremental):
1. Introduce `src/` with namespaced wrappers; keep legacy API as thin shim.
2. Migrate new classes to PSR-4 via composer while leaving legacy untouched.
3. Gradually refactor `P2P_Box` into service + view layer.
4. Add integration tests for REST endpoints once created.

## 11. Testing
- Contains outdated PHPUnit config (`phpunit.xml`) likely referencing older WP test includes.
- Upgrade path: adopt `wp-phpunit/wp-phpunit` package, update bootstrap, run tests under PHP 8.2+. Add new tests for REST + block UI serialization.

## 12. Tooling & CI
- Legacy `.travis.yml` present—Travis deprecated for many workflows. Migrate to GitHub Actions:
  - Matrix: PHP 7.4, 8.0, 8.1, 8.2, WP latest + LTS.
  - Coding standards: `wp-coding-standards/wpcs` via PHPCS.
  - Static analysis: PHPStan level 5 baseline then tighten.

## 13. Coding Standards & Linting
- Missing PHPCS config file. Add `phpcs.xml.dist` with WPCS rules; gradually fix.
- Add `.editorconfig` for consistency.

## 14. Backward Compatibility Strategy
- Maintain existing global functions & hooks.
- Ensure new REST routes & JS layer degrade gracefully if disabled.
- Provide feature flag constants for new behavior.
- Write upgrade guide in `README` or `UPGRADE.md`.

## 15. Data Migration / Upgrades
- Confirm current schema version tracking with `get_option('p2p_storage')` (seen in `admin/tools-page.php`). Document versioning and add dbDelta style migrations for future structure (indexes / column changes).

## 16. Potential Feature Enhancements
- Orderable connections via drag-and-drop with persisted `p2p_order` meta (exists?)—audit and surface in block panel.
- Connection filtering by taxonomy or status.
- GraphQL support (register connections as bidirectional relationships in WPGraphQL if plugin installed).
- CLI improvements: bulk rebuild indexes, orphan cleanup.

## 17. Risks / Technical Debt Summary
| Area | Risk | Mitigation |
|------|------|------------|
| Dynamic properties | PHP 8.2 deprecation notices | Add `__get/__set` or migrate to typed properties | 
| Legacy autoloaders | Fragile load order | Adopt Composer PSR-4 for new code | 
| Monolithic classes | Hard to test | Extract services (Repository, Renderer) |
| Lack of REST | Hard integration with blocks/headless | Introduce `wp-json/p2p/v1` routes | 
| Unbounded queries | Performance issues | Pagination + lazy load | 
| Missing CI | Regressions undetected | GitHub Actions pipeline | 
| Direct SQL strings | Consistency/readability | Use `$wpdb->prepare` | 

## 18. Immediate Low-Risk Wins
1. Add dependency notice (DONE) & instruct dev to run composer.
2. Introduce PHPCS + baseline.
3. Add GitHub Actions for static analysis.
4. Build minimal REST endpoint: list connections for a post.
5. Create experimental block editor panel consuming endpoint.

## 19. Longer-Term Refactors
- Replace Mustache + Backbone with React components using WP packages.
- Namespace new code under `P2P\`.
- Provide deprecation layer emitting `_doing_it_wrong()` warnings for outdated hooks after version bump.

## 20. Documentation Needs
- Architecture overview diagram (existing `diagrams/` folder could be updated).
- Clear lifecycle: Registration -> Querying -> Rendering -> Meta updates.
- Security & performance best practices page.

## 21. Summary
The plugin is functionally valuable but rests on a legacy stack (scb-framework + classic metabox + procedural API). With staged modernization emphasizing REST + block editor UX, namespace adoption, and improved tooling, it can align with contemporary WordPress development while preserving backward compatibility.

## 22. Current Logical Test Failures (Intentionally Deferred)
The PHPUnit harness now executes fully without fatals. Several tests still fail logically; they are preserved verbatim to act as executable specification of legacy expectations. These are deferred until after architectural refactors so we can consciously decide whether to keep, adapt, or deprecate behaviors.

| Test | Symptom | Root Cause (Preliminary) | Suggested Future Action |
|------|---------|--------------------------|-------------------------|
| `test_connected_query` | `$connected[0]->p2p_id` empty; count OK | Connection object returned by `get_users()` loses injected `p2p_id` when meta query (`connected_query`) path runs. Likely missing join alias or late hydration step in `connected_posts->each()` for user queries with meta filters. | Audit query builder for user-side connections with meta constraints; ensure `p2p_id` column selected and mapped into `P2P_User` wrapper (or populate via second lightweight query). |
| `test_p2p_list_posts_separator` | Fails expecting `', '` in output with only single connected item | Test added assertion to avoid being marked risky; legacy helper only adds separators between multiple items; single-item case naturally lacks separator. | Adjust test to create two connections OR relax assertion to check output non-empty. Decision: likely revise test when enhancing output helper. |
| `test_any` (final assertion) | After `disconnect(...,'any')`, `$connected_users->items` not empty | `disconnect()` with `'any'` sentinel does not coerce to expected direction; logic only matches explicit object IDs. Residual connections remain. | Extend disconnect path to treat `'any'` as wildcard for currently set direction; re-run to confirm count reduction; add regression test for cardinality scenarios. |
| `test_each_connected_users` (warning only) | Warning: `Corrupted data for item X` from `_p2p_get_other_id()` | Warning triggered when row side resolution mismatches expected direction (user/post ordering) for user connections—likely due to inconsistent assignment of `p2p_from`/`p2p_to` for user types or missing schema abstraction. | Inspect storage insertion for user connections; enforce canonical direction ordering; add validation in hydration eliminating false-positive corruption warnings. |

### Rationale for Deferral
Resolving these now risks churn ahead of planned namespacing and storage abstraction (Phases 1.5–3). Keeping failing tests documents expected legacy semantics; when implementing REST + new services, replicate or consciously document deviations (add release notes & deprecations where changed).

### Follow-Up Checklist
1. Introduce focused integration tests around connection hydration for posts vs users (ensure `p2p_id`, direction flags).
2. Add instrumentation/logging toggle to capture raw SQL for failing scenarios to ease future debugging.
3. Decide policy for helper outputs (e.g., separators for single item) and update tests accordingly.
4. Implement wildcard-aware disconnect semantics; update docs.
5. Replace ad-hoc corruption warning with structured exception or silent self-heal (data repair routine) once storage layer refactored.

_Added: 2025-09-17 (post harness stabilization)._
