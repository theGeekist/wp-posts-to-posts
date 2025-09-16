# scb-framework Inventory & Migration Plan

Date: 2025-09-17
Scope: Audit of all observable scb-framework touchpoints inside Posts 2 Posts plugin to guide Phase 1.5 (framework sunset / adapter layer introduction).

## Legend
| Priority | Meaning |
|----------|---------|
| P1 | Needed early to unblock future phases (REST, namespacing) |
| P2 | Moderate; migrate opportunistically |
| P3 | Low / can defer until after core refactors |

## 1. Bootstrap & Initialization
| Location | Usage | Purpose | Migration Strategy | Priority |
|----------|-------|---------|--------------------|----------|
| `posts-to-posts.php` lines ~74-75 | `scb_init( '_p2p_load' );` | Deferred plugin load via scb loader | Replace with direct function call guarded by dependency checks (`_p2p_load(); _p2p_init();`) and feature flag `P2P_DISABLE_SCB`. Keep shim calling scb_init only if it exists for BC. | P1 |
| `vendor/scribu/scb-framework/load-composer.php` | Defines `scb_init()` | Central entry for scb-managed delayed activation | No direct modification; create adapter that mirrors required side-effects; eventually remove include when flag enabled. | P1 |

## 2. Table Registration & Schema Management
| Location | Usage | Purpose | Migration Strategy | Priority |
|----------|-------|---------|--------------------|----------|
| `vendor/scribu/lib-posts-to-posts/storage.php` | `scb_register_table`, `scb_install_table`, `scb_uninstall_table` | Creates `p2p` & `p2pmeta` tables | Abstract into `P2P\Infrastructure\SchemaManager` using `$wpdb` + dbDelta; replicate schema definitions; wrap activation/deactivation hooks. Keep legacy calls behind adapter facade. | P1 |
| `vendor/scribu/scb-framework/Table.php` | Helper class for table lifecycle | Generic table helper (unused externally) | Inline required logic into new schema manager; mark this legacy component deprecated. | P2 |

## 3. Admin Interface Components
| Location | Usage | Purpose | Migration Strategy | Priority |
|----------|-------|---------|--------------------|----------|
| `vendor/scribu/scb-framework/AdminPage.php` | `scb_admin_notice`, page scaffolding | Builds admin pages & notices | Replace with explicit `add_menu_page` / `add_submenu_page` and WordPress admin notice hooks. Provide adapter implementing legacy constructor signature calling new service. | P2 |
| `vendor/scribu/scb-framework/Widget.php` | Static registry + `_scb_register` | Widget registration convenience | If plugin still registers widgets via scb (verify actual usage; none seen yet in audit), replace with direct `register_widget()` calls in init; keep shim if detection needed. | P3 |

## 4. Utility Functions
| Location | Usage | Purpose | Migration Strategy | Priority |
|----------|-------|---------|--------------------|----------|
| `vendor/scribu/lib-posts-to-posts/util.php` & `api.php` | `scb_list_group_by()` | Array grouping helper | Re-implement minimal pure function in namespaced `P2P\Util\Arr::groupBy()`; alias global name for BC. | P2 |
| `vendor/scribu/scb-framework/Util.php` | Activation hook management (`scb_activation_...`) | Delayed activation patterns | Convert to direct activation hook and/or manual upgrade checks on `plugins_loaded`. Provide no-op compatibility layer referencing existing options. | P2 |

## 5. Activation / Uninstall Hooks
| Location | Usage | Purpose | Migration Strategy | Priority |
|----------|-------|---------|--------------------|----------|
| `scb-framework/Util.php` & `Table.php` | `scb_install_table`, `scb_uninstall_table` | Schema creation/removal | Implement explicit `register_activation_hook` and `register_uninstall_hook` pointing to schema manager; deprecate scb calls. | P1 |

## 6. Data Structures & Dynamic Properties
| Location | Usage | Purpose | Migration Strategy | Priority |
|----------|-------|---------|--------------------|----------|
| Various classes under `vendor/scribu/lib-posts-to-posts/` | Rely on dynamic property assignment | Flexible ad-hoc metadata on objects | Introduce immutable value objects or well-defined DTO arrays inside new namespace; add magic `__get/__set` or `#[AllowDynamicProperties]` only as temporary shim. | P2 |

## 7. Hooks & Events
| Location | Usage | Purpose | Migration Strategy | Priority |
|----------|-------|---------|--------------------|----------|
| `scb_activation_{plugin}` actions | Custom activation pipeline | Sequence plugin-specific activation tasks | Replace with standard WP activation flow; provide deprecated action dispatchers to keep third-party handlers functional. | P2 |

## 8. Gap / Verification Items
| Area | Needed Action | Notes |
|------|--------------|-------|
| Widget usage | Search for classes extending scb widget base | None found yet; confirm to lower priority. |
| Admin pages | Enumerate actual P2P admin page classes using scb | Follow-up grep for `class P2P_.*Admin` or similar. |
| Options storage | Identify if scb Options API wrapper used | Grep for `new scbOptions` (not yet done). |

## 9. Migration Sequence Proposal
1. Introduce feature flag & direct bootstrap (leave scb path intact) – P1.
2. Add SchemaManager with mirrored create/uninstall; run side-by-side verification – P1.
3. Replace grouping utility & array helpers with namespaced replacements – P2.
4. Migrate admin pages incrementally; first passive registration via new service then retire scb AdminPage usage – P2.
5. Introduce DTO/value objects for connection items; begin removing dynamic property writes – P2.
6. Remove scb activation indirection once all tables & admin pages confirmed stable – P2/P3.

## 10. Backward Compatibility Strategy
- Keep global functions (`scb_init`, `scb_list_group_by`) as thin pass-throughs until a documented deprecation window (emit `_doing_it_wrong()` notices when feature flag enabled).
- Provide polyfill file `legacy-scb-shims.php` loaded only if `P2P_DISABLE_SCB` is true and scb-framework is absent.
- Add automated integration test capturing before/after `do_action` and `apply_filters` sequences for bootstrap and activation when toggling flag.

## 11. Risk Assessment
| Risk | Impact | Mitigation |
|------|--------|------------|
| Schema drift between scb and new manager | Data inconsistency | Snapshot existing `SHOW CREATE TABLE` and assert identical after migration. |
| Third-party code expecting scb activation hooks | Broken integrations | Fire legacy hooks within new bootstrap. |
| Performance regressions during dual-path bootstrap | Slower load | Add transient to short-circuit schema checks; measure via query monitor before/after. |

## 12. Success Criteria for Phase 1.5
- `P2P_DISABLE_SCB=true` path loads plugin without scb-framework present (happy path tests pass; logical failures unchanged).
- New SchemaManager reports tables exist & matches hash of legacy schema.
- Inventory doc PR merged; TODO updated with checked inventory item and Mini Release Notes.

---
Prepared as foundation for Phase 1.5 execution.
