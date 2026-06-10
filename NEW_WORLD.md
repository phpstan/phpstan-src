# The New World: single-pass expression processing

Working design document for the `resolve-type-rewrite` branch. This describes where the
refactoring is going, why, what we gain, and the full inventory of code to build. It is a
branch-lifetime document — delete before merging to the default branch.

## 1. Motivation

PHPStan currently traverses the AST of the same expression **multiple times**:

1. **`NodeScopeResolver::processExprNode`** walks the expression to update the `Scope`
   (assignments, narrowing side-effects, throw/impure points).
2. **`MutatingScope::resolveType`** (via `ExprHandler::resolveType`) walks the expression
   *again* to compute its `Type` — on whatever scope the caller happens to hold.
3. **`TypeSpecifier::specifyTypesInCondition`** (via `ExprHandler::specifyTypes`) walks it
   a *third* time to compute narrowing (`SpecifiedTypes`).

Because pass 2 and 3 don't have the intermediate scopes that pass 1 computed, they have to
**re-create them**, which means re-invoking the engine from inside type resolution. Concrete
pathologies in today's code:

- `BooleanAndHandler::resolveType` re-runs `processExprNode($expr->left)` on a **throwaway
  `ExpressionResultStorage`** with a `NoopNodeCallback`, just to rebuild the truthy scope of
  the left side so it can type the right side — even though `processExpr` four lines earlier
  already processed the right side on exactly that scope. The cost is exponential on deep
  boolean chains, which is the only reason `BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH` and the
  flattened-chain code paths exist.
- `AssignHandler::unwrapAssign` manually walks through nested `$a = $b = 5` because resolving
  the type via the scope can't follow the chain naturally.
- `TypeSpecifier` is a condition-*rewriting* engine: handlers build **synthetic** expressions
  (`new Identical(...)`, isset and-chains, cast comparisons, swapped `Smaller` nodes) and
  re-enter the dispatcher, because narrowing logic can only talk to other narrowing logic
  through "an expression + a scope".
- `MutatingScope` keeps `truthyScopes`/`falseyScopes` caches and `FiberScope` keeps a
  truthy/falsey expr replay list (`preprocessScope`) purely to paper over the fact that
  narrowing is recomputed after the fact instead of being produced once, in place.

**The fix: process each expression once.** After `processExprNode` finishes we have not just
the updated `Scope` but also the expression's `Type` and its `SpecifiedTypes` — computed by
the handler *at the moment it had all its children's results and the correct intermediate
scopes in hand*.

## 2. Old world vs. new world

The old world keeps working on PHP < 8.1 until PHPStan 3.0, where it is mass-deleted.
The new world requires PHP 8.1+ (Fibers).

| Old world (deleted in 3.0) | New world |
|---|---|
| `MutatingScope::getType/getNativeType/resolveType` | `ExpressionResult::getType()/getNativeType()/getTypeForScope()` |
| `ExprHandler::resolveType` | `typeCallback` wired in `processExpr` |
| `TypeSpecifier::specifyTypesInCondition` dispatcher + `ExprHandler::specifyTypes` | `specifyTypesCallback` wired in `processExpr` |
| `MutatingScope::filterBySpecifiedTypes` | `MutatingScope::applySpecifiedTypes` |
| `filterByTruthyValue` / `filterByFalseyValue` (+ `truthyScopes` caches) | `applySpecifiedTypes($result->getSpecifiedTypes(...))` |
| `ExpressionResult` legacy `truthyScopeCallback`/`falseyScopeCallback` | `getTruthyScope()`/`getFalseyScope()` reimplemented on the line above (accessors stay; ~31 engine call sites untouched) |
| `FiberScope::preprocessScope` truthy/falsey replay | not needed — narrowing applied to real scopes |

Enforcement: `MutatingScope::getType()/getNativeType()/getKeepVoidType()` and
`TypeSpecifier::specifyTypesInCondition()` **throw** when `NewWorld::disableOldWorld()`
returns `true` (and `PHPSTAN_FNSR` ≠ `0`, PHP 8.1+ — those conditions stay at the call
sites). The old world stays fully functional under `PHPSTAN_FNSR=0` (PHP < 8.1 path).
**The committed state is `return false;`** — mixed mode, everything green. Flipping the
single literal to `return true;` is how a handler-migration leg starts: the guard then
fails loudly wherever migration is incomplete, instead of commenting throws in four places.

**The goal of this continuous refactoring: the whole test suite is green when the guard
exceptions are set to not fire** (mixed mode — migrated handlers run their new-world
callbacks, everything else takes the guarded legacy bridges). That bar means the rewrite
pays off *before* it is finished: every migrated handler immediately delivers improved and
more precise analysis across the whole test suite, not just in the new-world corpus. The
guard-on mode is the forcing function and the progress meter; the mixed mode is the
deliverable at every point along the way.

## 3. Design decisions (settled)

1. **`typeCallback: callable(Expr, MutatingScope): Type`** — one callback, mirroring PR #5224
   (`b2ce1a0558`). `getType()` resolves on the result's own scope; `getNativeType()` on
   `doNotTreatPhpDocTypesAsCertain()`; `getTypeForScope($scope)` picks the variant by
   `$scope->nativeTypesPromoted`. No separate native/keepVoid callbacks; `getKeepVoidType`
   is a one-off solved later.
2. **Inside callbacks, `$scope->getType($child)` becomes `$childResult->getTypeForScope($s)`.**
   `MutatingScope::getType()` must never be called from inside an `ExprHandler`.
3. **Never reach into `ExpressionResultStorage` from handler logic.** Child results are
   threaded through closures. Storage exists only as the fiber rendezvous (deliver results to
   suspended rule callbacks) and the synthetic-node fallback. Every constructed
   `ExpressionResult` carries its `expr` so `getType()` always works.
4. **Hard-fail + guarded legacy bridge.** A result without a callback falls back to the
   guarded `$scope->getType($expr)`: transparent under `PHPSTAN_FNSR=0` (validated parity vs
   baseline on stress files), loud failure under the guard. This is what makes
   handler-by-handler migration safe — the suite stays green on the legacy path while the
   guard tells us exactly what to migrate next.
5. **New code paths instead of nullable/optional params** on existing methods (no
   `?Type $exprType` threading through `TypeSpecifier::create`; `SpecifiedTypes` stays
   untouched — it is `@api` and extensions produce it forever).
6. **Copy-and-adjust is sanctioned**: `resolveType` bodies are copied into `typeCallback`
   (and `specifyTypes` into `specifyTypesCallback`) with the §3.2 substitution. Dual
   maintenance until 3.0 is the accepted cost; mitigate by extracting pure `Type`-taking
   helpers shared by both worlds.
7. **`specifyTypesCallback` returns a new envelope object** (working name `NarrowingResult`):
   `SpecifiedTypes` + `array<string, ExpressionResult>` (exprString → result). The map is the
   "type oracle": it answers original (pre-narrowing) types in `applySpecifiedTypes` and
   `normalize()`, and supplies dim/var types for the `ArrayDimFetch` parent-update — all via
   `ExpressionResult::getType()`, honoring §3.3. Extension-produced `SpecifiedTypes` flow
   through with an empty map.
8. **The new world is cut away from the old world.** Callbacks contain *copied
   and adjusted* code — they never delegate to `resolveType`/`specifyTypes`
   (those must be deletable in 3.0). Duplication between the worlds is accepted.
   `ResultAwareScope` is used **only at two sanctioned boundaries**: invoking
   extensions, and `ParametersAcceptorSelector` (+ the TypeSpecifier
   conditional-return/assert helpers until they are ported). It is *not* a
   general bridge for running old-world handler bodies.
9. **Two adapters, by execution context**:
   - **`FiberScope`** (exists): for *rule* node-callbacks, which run before the expression is
     processed. `getType()` suspends the fiber; the engine resumes it with the
     `ExpressionResult` at the end of `processExprNode`. Synthetic exprs are processed on
     demand at end of traversal.
   - **`ResultAwareScope`** (to build): for *extensions and old-world helper code invoked from
     inside handler callbacks* — dynamic return type extensions, type-specifying extensions,
     `ParametersAcceptorSelector::selectFromArgs`, `TypeSpecifier::create`, assert resolution.
     These run mid-analysis where suspension is impossible *and unnecessary*: all children are
     already processed. `getType()` resolves in tiers: extension registry → scope-tracked
     holder → known-results map → inline re-process (`processExprNode` on a duplicated
     storage with `NoopNodeCallback` — handles the synthetic exprs extensions love to build)
     → guarded bridge.
10. **Single-pass analysis kills nullsafe short-circuiting.** The old world walks every
    eligible expression recursively (`NullsafeShortCircuitingHelper`,
    `NullsafeOperatorHelper::getNullsafeShortcircuitedExpr`) to find a `?->` somewhere in
    the chain that influences the result. In the new world expressions process inside-out,
    so only `NullsafePropertyFetchHandler` and `NullsafeMethodCallHandler` ever see the
    `?->` — they emit the plain-chain variant alongside their own key **once**, and every
    parent composes their results. `DefaultNarrowingHelper::specifyDefaultTypes()` therefore
    needs no expression type at all, and `specifyTypesCallback`s never invoke the
    `typeCallback` — narrowing callbacks are cheap, type-free closures.
11. **Result callbacks must not capture the `ExpressionResultStorage`.** Stored results
    capturing the storage they live in are reference cycles only the cyclic GC can free;
    one call anywhere in an expression makes the whole ancestor result graph cyclic.
    Measured: the cycles were the *entire* 4.3× `NodeScopeResolverTest` slowdown (92s → 25s
    when broken; the engine work itself was at old-world parity all along, the time went to
    GC scans over live cyclic webs). Late asks build their adapters on a **fresh storage**
    instead — the synthetics-in-flight cycle guard threads through it, only known-result
    seeding is lost on those rare paths.
12. **`InitializerExprTypeResolver` keeps its `callable(Expr): Type` shape; the new world
    feeds it a results-first callback** — the form `ArrayHandler` established: the closure
    closes over the already-processed child `ExpressionResult`s (keyed by
    `spl_object_id($expr)`), answers from `$childResult->getTypeForScope($s)` when the asked
    expr is one of them, and only falls back to the guarded `$s->getType($inner)` bridge for
    exprs it has no result for. Handlers migrating constructs that resolve through
    `InitializerExprTypeResolver` (BinaryOp, ClassConstFetch, ConstFetch, UnaryMinus/Plus,
    …) reuse this pattern instead of inventing per-handler plumbing.
13. **Branch scopes prefer the handler's scope callbacks; adapters are seeded per base
    scope.** Two lessons from the BooleanAnd/Or leg, both found as mixed-mode nsrt diffs:
    - `getTruthyScope()`/`getFalseyScope()` consult `truthyScopeCallback`/`falseyScopeCallback`
      *first*, the specify-callback reconstruction second. A handler that can compose a
      branch scope incrementally must say so: for `A && B` the truthy scope *is* the right
      operand's truthy scope (the left narrowing is already part of it) — re-deriving the
      whole conjunction from per-arm `SpecifiedTypes` re-unions types that were never
      unioned in the old world and drifts representations (`array<mixed>` vs
      `array<mixed, mixed>`). Consequence: migrated handlers pass *new-world* scope
      callbacks or none at all — the old `filterByTruthyValue($expr)` bridges were stripped
      when the preference flipped.
    - A `ResultAwareScope` must be seeded only with results **evaluated on its base scope**.
      A result's memoized type is its evaluation-point type, so seeding the right operand's
      result (evaluated on the left-truthy scope) into the pre-condition adapter answered
      narrowing-original asks with already-narrowed types (`is_bool($x) && $x` falsey lost
      the non-bool half). Everything not seeded re-processes on the base scope (tier 4).

## 4. What we gain

- **Performance**: one traversal instead of up to three. The `BooleanAnd::resolveType`
  re-processing (and its depth cap), the `filterByTruthyValue` recomputation cascades, and the
  `truthyScopes` cache layer all disappear. #5224 measured ~17% on a comparable consolidation.
  Types are computed from already-known child types instead of re-walking subtrees.
- **Correctness by construction**: a type is computed exactly where the right scope exists.
  No more "which scope do I resolve this on" bugs; the right side of `&&` is typed on the
  left-truthy scope because that is literally the scope it was processed on.
- **Simplicity — hacks that delete themselves**:
  - `unwrapAssign` (nested assigns flow through result delegation),
  - `BooleanAndHandler::resolveType` re-walk + `BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH` + flattened-chain workarounds,
  - synthetic re-dispatch nodes inside `specifyTypes` bodies (`new Identical(...)`, isset chains),
  - `AssignHandler`'s Ternary lookahead on `$storage->duplicate()`,
  - `truthyScopes`/`falseyScopes` caches, `FiberScope::preprocessScope` replay,
  - `storeBeforeScope`/`findBeforeScope` (already dead),
  - in 3.0: all `resolveType`/`specifyTypes` methods, `MutatingScope::resolveType`,
    the `TypeSpecifier` dispatcher, `filterBySpecifiedTypes`, `filterByTruthy/FalseyValue`.
- **Extension compatibility preserved**: third-party extensions keep their signatures.
  `Scope::getType` works inside extensions via `ResultAwareScope`/`FiberScope`;
  `TypeSpecifier::specifyTypesInCondition` recursion works via an `instanceof` head-check
  routing to the new world.

## 5. Implementation inventory

Status: ✅ done · 🔶 in progress · 🔧 mechanical · 🎯 design-sensitive

### A. Core contracts
1. ✅ `ExpressionResult::getType/getNativeType/getTypeForScope` + guarded bridge; results
   stored per expr; fiber delivery moved to end of `processExprNode` (`storeResult`).
2. 🎯 `NarrowingResult` envelope (§3.7) + result-based `normalize()`.
3. 🔶 `expr:` on every `ExpressionResult` construction; memoize truthy/falsey applied scopes;
   `getSpecifiedTypes` returns the envelope.

### B. Adapters
4. 🎯 `ResultAwareScope` + factory (§3.8) — unlocks call handlers and all extensions.
5. 🔧 `TypeSpecifier::specifyTypesInCondition` head-check: `ResultAwareScope` → map/inline-process;
   `FiberScope` → suspend. Un-guards `AssertFunctionTypeSpecifyingExtension`,
   `InArrayFunctionTypeSpecifyingExtension`, `ImpossibleCheckTypeHelper:305`.
6. 🔧 `FiberScope` gaps: `doNotTreatPhpDocTypesAsCertain()` override (today it escapes to a
   plain promoted `MutatingScope`), `filterByTruthy/FalseyValue` → suspend + apply,
   re-process request for `getScopeType` (maintainer).

### C. applySpecifiedTypes
7. 🎯 `MutatingScope::applySpecifiedTypes(NarrowingResult): self` — original types via tiers
   (extensions → tracked holder → envelope result → bridge); intersect/remove math +
   complex-union/`NeverType` early-outs stay centralized (extensions force sure/sureNot
   semantics to survive); post-narrowing holders computed locally (kills `getScopeType` at
   `MutatingScope:3412`); `IssetExpr` entries → existing certainty ops (already clean).
8. 🔧 New-world path for the `ArrayDimFetch` parent-update in `specifyExpressionType`
   (`MutatingScope:2860-2886`): dim/var types from the envelope map.
9. 🔧 `ExpressionResult::getTruthyScope/getFalseyScope` reimplemented on #7 (+ memoization).

### D. specifyTypesCallback producers
10. 🔧 Leaf default narrowing helper (new path; copy-adjusted
    `handleDefaultTruthyOrFalseyContext`/`createForExpr` taking the own type from the result).
11. 🎯 Result-based entry points on `EqualityTypeSpecifyingHelper` (replacing its 7
    `new Identical(...)` re-dispatches), `NonNullabilityHelper`, `NullsafeShortCircuitingHelper`,
    `ConditionalExpressionHolderHelper`.
12. 🔧 Compound handlers composing child envelopes at the scopes they were already processed
    on: `BooleanNot/And/Or` (incl. flattened variants), `ErrorSuppress`, `Ternary`, `Coalesce`,
    `Isset`/`Empty` (compose parts instead of building synthetic chains), `Instanceof`,
    `BinaryOp` equality/comparisons, casts.
13. 🔧 Call handlers: type-specifying extensions + conditional-return + asserts via the
    adapter; `Assign`/`AssignOp` (createNull from RHS envelope, truthy/falsey via #10).

### E. typeCallback producers
14. ✅ `Scalar`, `Variable`, `Assign` (Assign re-threaded to avoid storage).
15. 🔧 Trivial: `ConstFetch`, `Print`/`Exit`/`Throw` (fixed types), `Clone`, `ErrorSuppress`,
    `Empty`/`Isset`/`Instanceof`/`BooleanNot` (booleans), 15 `Virtual/*` passthroughs.
16. 🔧 `InitializerExprTypeResolver`-backed (it is **already `callable(Expr): Type`-
    parameterized** — 82 occurrences): `BinaryOp`, casts, `UnaryMinus/Plus`, `BitwiseNot`,
    `InterpolatedString`, `Array_`, `ClassConstFetch`.
17. 🔧 Compound control flow: `BooleanAnd/Or`, `Ternary`, `Coalesce`, `Match` — children are
    already processed per-branch; combine child results, delete the re-entry blocks.
18. 🎯 Calls: `FuncCall`/`MethodCall`/`StaticCall`/`New_`/nullsafe — return type extensions +
    generics inference (`selectFromArgs`) via the adapter; `PropertyFetch`/`StaticPropertyFetch`;
    `Closure`/`ArrowFunction` (existing `ClosureTypeResolver`); `Pre/PostInc/Dec`, `AssignOp`,
    `ArrayDimFetch`, `Yield`/`YieldFrom`, `Eval`, `Include`, `Pipe`.

### F. Engine rewiring
19. 🔶 `NodeScopeResolver` statements: 31 `scope->getType/getNativeType` sites → the result in
    hand (`treatPhpDocTypesAsCertain ? getType : getNativeType` maps 1:1 onto
    `getType()/getNativeType()`); `:1151` createNull → envelope; 9 `filterBy*` sites — the
    synthetic-condition ones (switch `:2023/2049`, foreach `:1462`, while `:1626`) become
    direct helper calls with results. (`findEarlyTerminatingExpr` already migrated.)

## 5a. Working style

- **TDD with the guard exceptions active**: when migrating a handler to the new
  world, always start by flipping `NewWorld::disableOldWorld()` to `return true;`
  (the committed state is `return false;` — mixed mode, everything green). Then
  drive the work with `NewWorldTypeInferenceTest`:
  1. add probes for the handler's constructs to `data/new-world.php` (or rely on
     bridge probes already there) and run the test — **red**, the guard message
     names the unmigrated handler;
  2. implement `typeCallback`/`specifyTypesCallback` until the test progresses
     past those constructs — the meter walks the data file in order, naming the
     next unmigrated handler ("fix, rerun, next");
  3. flip `disableOldWorld()` back to `return false;` and run the mixed-mode
     scoreboard (nsrt `NodeScopeResolverTest` + `make phpstan`) to verify
     whole-suite impact — `false` is also what gets committed.
  Each condition and branch of new-world code gets a probing assertType test.
- **No TODO markers in new-world code** — deferred functionality is implemented
  immediately. Where something genuinely depends on a not-yet-migrated handler,
  the code states that dependency as a fact (and bridges or skips), it doesn't
  promise future work.

## 5b. Handler migration checklist

`[x]` = `processExpr` wires both `typeCallback` and `specifyTypesCallback` into its
`ExpressionResult`. Residual guarded bridges inside migrated handlers are documented
as factual comments at their call sites, not here.

### Expression handlers

- [x] ArrayDimFetchHandler — typeCallback composes var/dim results (offsetGet synthetic via unseeded adapter for ArrayAccess; one-level nullsafe short-circuit propagation per §3.10); write-context `$x[]` is NeverType; default narrowing; holder-first helpers gained a scalar tier for the parent-update dims
- [x] ArrayHandler
- [ ] ArrowFunctionHandler
- [x] AssignHandler — Ternary/Match conditional-expression holders stay old-world until those handlers migrate
- [ ] AssignOpHandler
- [x] BinaryOpHandler — typeCallback complete (Identical/NotIdentical via the Type-taking RicherScopeGetTypeHelper variants); specifyTypesCallback invokes the old ~1300-line equality/comparison body directly with an unseeded adapter (single source — the dispatcher round-trip would bounce; the 3.0 cleanup absorbs the body); operand companions for apply originals; the apply path derives synthetic dim-fetch originals from resolvable var+dim; FiberScope::getKeepVoidType via the attributed-clone synthetic
- [x] BitwiseNotHandler — §3.12 results-first InitializerExprTypeResolver callback; default narrowing
- [x] BooleanAndHandler — typeCallback composes child results evaluated on the left-truthy scope (no re-walk, no `BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH`, no flattened path in the new world); truthy scope incremental via `$rightResult->getTruthyScope()` (§3.13); falsey via specifyTypesCallback with per-base-seeded adapters
- [x] BooleanNotHandler — typeCallback folds via the inner result; incremental branch scopes (truthy(!X) = X's falsey scope, §3.13); specifyTypesCallback negates the context onto the inner result (unseeded adapter for unmigrated inner)
- [x] BooleanOrHandler — mirror of BooleanAndHandler (falsey scope incremental, truthy via specifyTypesCallback); `augmentBooleanOrTruthyWithConditionalHolders` priced through the adapters
- [x] CastHandler — §3.12 results-first cast type (Unset_ cast → null); bool/int/double narrowing via the old comparison synthetics through an unseeded adapter
- [x] CastStringHandler — §3.12 results-first cast type; narrowing via the `!= ''` synthetic through an unseeded adapter
- [x] ClassConstFetchHandler — §3.12 results-first class-const type (dynamic class expr via the class result); dynamic const names mixed; default narrowing
- [x] CloneHandler — typeCallback intersects the inner result with object and maps through CloneTypeTraverser; default narrowing
- [ ] ClosureHandler
- [ ] CoalesceHandler
- [x] ConstFetchHandler — typeCallback: literal true/false/null, holder-tracked runtime constants, ConstantResolver (all unguarded already); default narrowing
- [ ] EmptyHandler
- [x] ErrorSuppressHandler — full delegation to the inner result (type, narrowing, branch scopes); unseeded adapter for unmigrated inner
- [x] EvalHandler — constant mixed typeCallback; default narrowing
- [x] ExitHandler — constant NonAcceptingNeverType typeCallback; default narrowing
- [ ] FirstClassCallableFuncCallHandler
- [ ] FirstClassCallableMethodCallHandler
- [ ] FirstClassCallableNewHandler
- [ ] FirstClassCallableStaticCallHandler
- [x] FuncCallHandler — dynamic-name calls bridge
- [x] IncludeHandler — constant mixed typeCallback; default narrowing
- [x] InstanceofHandler — typeCallback folds via the target/class results; specifyTypesCallback is the old create() math with an adapter seeded with the target and class results
- [x] InterpolatedStringHandler — per-part results keyed by spl_object_id (each captured at its own evaluation point); concat folding via resolveConcatType; default narrowing
- [ ] IssetHandler
- [ ] MatchHandler
- [ ] MethodCallHandler
- [ ] NewHandler
- [x] NullsafeMethodCallHandler — shares the §3.10 callback; call part reused via MethodCallHandler::processCallWithVarResult; call type bridges until MethodCallHandler migrates; impure calls gate result narrowing
- [x] NullsafePropertyFetchHandler — emits the plain-chain dual key and the subject-not-null entry once, per §3.10; dynamic names bridge
- [ ] PipeHandler
- [x] PostDecHandler
- [x] PostIncHandler
- [x] PreDecHandler
- [x] PreIncHandler
- [x] PrintHandler — constant `1` typeCallback; default narrowing
- [x] PropertyFetchHandler — one-level short-circuit propagation from a nullsafe var; dynamic names bridge
- [x] ScalarHandler
- [ ] StaticCallHandler
- [ ] StaticPropertyFetchHandler
- [x] TernaryHandler — typeCallback composes the branch results (each evaluated on the matching cond-narrowed scope; short ternary asks the cond on its truthy scope via getTypeOnScope); specifyTypesCallback rewrites into the old `(cond && if) || (!cond && else)` synthetic, processed through the migrated boolean handlers (adapter tier 4); branch scopes via the specify path; unlocked AssignHandler's Ternary conditional-holder block (cond result narrowing + getTruthyScope/getFalseyScope + adapter-priced branch types + entry resolver)
- [x] ThrowHandler — typeCallback is the NonAcceptingNeverType constant; throw point takes the inner result's type; default narrowing callback
- [x] UnaryMinusHandler — §3.12 results-first InitializerExprTypeResolver callback; default narrowing
- [x] UnaryPlusHandler — §3.12 results-first InitializerExprTypeResolver callback; default narrowing
- [x] VariableHandler — dynamic variable names bridge
- [ ] YieldFromHandler
- [ ] YieldHandler

### Virtual node handlers

- [ ] AlwaysRememberedExprHandler
- [ ] ExistingArrayDimFetchHandler
- [ ] FunctionCallableNodeHandler
- [ ] GetIterableKeyTypeExprHandler
- [ ] GetIterableValueTypeExprHandler
- [ ] GetOffsetValueTypeExprHandler
- [ ] InstantiationCallableNodeHandler
- [ ] MethodCallableNodeHandler
- [x] NativeTypeExprHandler
- [ ] OriginalPropertyTypeExprHandler
- [ ] SetExistingOffsetValueTypeExprHandler
- [ ] SetOffsetValueTypeExprHandler
- [ ] StaticMethodCallableNodeHandler
- [x] TypeExprHandler
- [ ] UnsetOffsetExprHandler

## 6. Migration mechanics

- **Exercisers**: tiny files analysed with `bin/phpstan analyse -l 8 test.php --debug` under
  the guard. `echo '1';` (type slice, green), `$v = 1; if ($v) {} else {}` (narrowing slice).
- **New-world test case** (`NewWorldTypeInferenceTest` + `data/new-world.php`): a temporary
  `TypeInferenceTestCase` subclass asserting types for both migrated handlers and the bridges.
  Its diagnostic value is **when the old world is cut off by the guard exceptions**: run it
  with the guards active and the failures show exactly which handlers still need to implement
  the new callbacks (the guard messages name the construct). In the mixed working state it
  must stay fully green. **When the whole suite is green in mixed mode, the temporary test
  case is deleted** — everything is covered by pre-existing tests.
- **Parity discipline**: after each migration leg, `PHPSTAN_FNSR=0` runs must match baseline
  (`git stash` + compare); the new-world result for migrated constructs must match the
  old-world result.
- **3.0 mass-deletion list**: everything in the left column of §2, the guard itself, and this
  document.

## 7. Status log

- 2026-06-09: `ExprHandler` consolidation (resolveType + specifyTypes live in handlers);
  guard commit; fiber delivery of `ExpressionResult` (`9cb1d353f0`); `Scalar`/`Variable`/
  `Assign` typeCallbacks; `echo '1';` green under guard; FNSR=0 parity restored (`891bad60ff`).
- 2026-06-10: feasibility research (this document); decision: `NarrowingResult` envelope,
  `ResultAwareScope` adapter, tiered original-type resolution in `applySpecifiedTypes`.
- 2026-06-10 (later): first three handlers fully migrated — `ScalarHandler`,
  `AssignHandler` (value result threaded through the `processAssignVar` callback;
  `hasTypeCallback()` contract; conditional-expression holders gated old-world-only
  with a TODO), `FuncCallHandler` (`resolveTypeViaResults`/`specifyTypesViaResults`
  copies; return-type + type-specifying extensions and `selectFromArgs` through
  `ResultAwareScope`; throw-point never-detection via lazy return-type callback).
  Supporting infra: `ResultAwareScope` (tiers: extensions → tracked → known results →
  inline re-process → guarded bridge; derivation-safe via `pushInFunctionCall`
  overrides), `NewWorld::isEnabled()`, `DefaultNarrowingHelper` (new-world copy of
  default truthy/falsey narrowing), `TypeSpecifier::specifyTypesInCondition`
  head-check for `ResultAwareScope` (recursion stays new-world) and `FiberScope`
  (rules suspend for the result — un-guards `ImpossibleCheckTypeHelper`),
  `FiberScope::doNotTreatPhpDocTypesAsCertain` fiber-safety, `processArgs`
  callable-arg type from the result. **`NewWorldTypeInferenceTest` added**
  (temporary; delete when the whole suite is green under the guard): 13 assertions
  over scalars, assigns (incl. nested), params, and function calls (signature,
  constant-folding extensions, nested calls) — green in both worlds.
- 2026-06-10 (TDD leg): **`MutatingScope::applySpecifiedTypes`** lands — the new-world
  apply side. Original (pre-narrowing) types resolved in tiers (extension registry →
  scope-tracked holders → caller-supplied ExpressionResults → guarded bridge); the
  conditional-holder matching tail is shared with `filterBySpecifiedTypes` via an
  extracted private method. `getTruthyScope`/`getFalseyScope` and the per-statement
  createNull narrowing run on it. `VariableHandler` gets its own copied typeCallback
  + default-narrowing specify callback; `TypeExpr`/`NativeTypeExpr` virtual handlers
  migrate (their type is the wrapped type); synthetic fiber requests are processed on
  the plain scope (a FiberScope would suspend from within — found via an infinite
  loop in the asserts flow). FuncCall conditional-return + asserts narrowing are
  **copied** into the handler (`*ViaResults`), no longer delegating to the
  TypeSpecifier internals; the `@api` `create()`/`specifyTypesInCondition()` (with
  adapter) remain the sanctioned entry points. Assign conditional-expression holders
  (truthy/falsey projection + falsey-scalar equality holders) are ported with a
  per-entry type resolver (assigned result → tracked holders → skip unpriceable
  entries, e.g. conditional-return narrowing of inner call arguments); Ternary/Match
  holders stay old-world until those handlers migrate. If/elseif condition types and
  `processArgs` callable/impure-invalidation types come from ExpressionResults.
  `NewWorldTypeInferenceTest`: **33 assertions green in both worlds**, including
  `if`/`else` narrowing (`$v = 1; if ($v)` — the original exerciser), assign-in-if,
  function asserts (`@phpstan-assert`), conditional return types, holder-driven
  narrowing (`$len = strlen($s); if ($len)` → `$s` is `non-empty-string`), and
  by-reference assignment.
- 2026-06-10 (array leg): **`ArrayHandler`, `BinaryOpHandler`, `Pre/PostInc`,
  `Pre/PostDec` migrate.** The headline test: `$a = [$b = 1, $b + 1, $c = $b,
  $c + 2, $c++, $c]` infers `array{1, 2, 1, 3, 1, 2}` — each item's type is
  captured at its own evaluation point (the old world resolves all items on one
  scope and cannot do this). `processVirtualAssign` takes an optional
  `$assignedTypeCallback` (auto-priced for `TypeExpr`/`NativeTypeExpr`);
  `PreInc/PreDec` extract a pure `resolveTypeFromVarType(Expr, Type)` shared by
  both worlds; `PostInc/PostDec` type as the variable's pre-step value and price
  the virtual `PreInc/PreDec` assign via the injected pre-handler. BinaryOp's
  `resolveTypeFromResults` is a full copy of `resolveType` with identity-matched
  child results (Identical/NotIdentical bridge to `RicherScopeGetTypeHelper`
  until the equality migration).
- 2026-06-10 (engine fixes found by the leg, via `make phpstan` divergence triage):
  1. **Pending fibers parked too eagerly flushed**: the flush ran at the end of
     *every* statement list, so a fiber asking about an expr that the enclosing
     statement still had to process (a do-while/while/for condition after its body
     list) was answered by synthetic re-processing on the scope captured at
     suspension — and the poisoned result was stored under the *real* AST node's
     key, early-resuming later legitimate askers (`do { $count++ } while ($count
     < 3)` reported `0 < 3 always true`). Fix: statement lists never flush;
     parked requests wait for the real `storeResult` resume, and only
     analysis-unit boundaries (file statements, function, method, trait) flush
     genuine synthetics. Resolved 7 `smaller.alwaysTrue` + ~10 loop-flavored
     src divergences.
  2. **First-class callables typed `mixed` when assigned** (both worlds —
     a consolidation regression): the FCC early path stored the virtual
     `*CallableNode`'s result, whose `expr` was the virtual node; the virtual
     handler's `resolveType` is intentionally `mixed`, so the result bridge
     asked the wrong node. Fix: rewrap the result with the original expr so the
     bridge dispatches to the `FirstClassCallable*Handler`s.
  `make phpstan` (4G) divergences: 30 → 13; nsrt mixed failures: 45 → 31 (0 errors).
- 2026-06-10 (corpus + coverage): user-driven xdebug coverage audit of all branch
  changes vs 2.2.x under `NewWorldTypeInferenceTest` (raw whole-process coverage —
  PHPUnit per-test coverage misses data providers where the analysis runs).
  Corpus grown 34 → 132 assertions: all BinaryOp operators, pre/post inc/dec
  variants, keyed arrays, `is_callable` pair check, nullable truthy narrowing,
  post-inc-in-condition (exprResults tier of `applySpecifiedTypes`),
  `assertNativeType` probes, method-call result bridge, tracked-property and
  is_* narrowing through `ResultAwareScope` + `TypeSpecifier` head-checks,
  dynamic/undefined variables, unmigrated-condition bridges (BooleanNot/And/Or,
  instanceof, empty, isset, count), bare-call statements, negated/equality
  asserts, first-class callables, list assignment, closures/arrow fns, foreach
  virtual assigns, elseif, echo, min/max signature selection, dynamic
  return/type-specifying/throw extension probes (`is_int`, `assert`, `intdiv`
  try/finally certainty). **Coverage of executable changed lines: 47.5%.**
  Remaining uncovered, classified: old-world bodies moved by consolidation
  (covered by the pre-existing suite; deleted in 3.0), defensive throws,
  rule-driven paths (fiber early-resume/synthetic flush, `FiberScope`
  doNotTreat.../keepVoid — TypeInferenceTestCase runs no rules),
  `ExpressionTypeResolverExtension` tiers (no such extension in test config),
  and future-leg provisions (isset-certainty apply branch, TruthyFalsey
  context, nullsafe roots in migrated specify callbacks).
- 2026-06-11 (whole-suite burn-down): **the full test suite finishes again** (the
  hang was the premature pending-fiber flush poisoning stored results) and the
  scoreboard is now measured suite-wide: 12843 tests, 25 -> ~10 failures.
  Fixed, each with its own commit: FiberScope types resolve at the expression's
  evaluation point narrowed by rule-applied filters (restores the old
  preprocessScope contract; fixes dynamic-call name/param correlation and
  chained-call asks; also fixes filterByFalseyValue delegating to
  filterByTruthyValue); keepVoid bridges to the old world (regular results
  store void as null — "(void) is used" errors were lost); per-scalar
  conditional holders bridge through old-world equality (nullsafe subjects pin
  non-null); function-call extension reads price at the call point (before the
  call's own virtual mutations — array_shift saw the already-shifted arg);
  native-type promotion mirrored into the specify-path adapter (PHPDoc tips
  were lost); collectors collect before forwarding to rules (suspended rule
  fibers deferred execution-end/return collection past the aggregate-node
  snapshots) plus a class-boundary fiber flush before the Class*Nodes;
  Ternary/Match conditional holders un-gated into mixed mode (isset-ternary
  variable certainty); applySpecifiedTypes uses Yes-certainty holders only as
  narrowing originals (Maybe holders carry "when defined" types — broke
  FNSR=0 parity, found by bisect). Remaining failures are one designed-fix
  family (passed-closure typing context through the adapter — see the task
  notes; three heuristic attempts each traded fixes for breaks) + the
  multi-assign precision improvement awaiting a mode-dependent-expectations
  policy.
- 2026-06-11 (property leg): **PropertyFetchHandler + NullsafePropertyFetchHandler
  migrate** — the first leg driven end-to-end by the §5a loop with the
  disableOldWorld meter. The nullsafe handler is now the only place that knows
  about `?->` (§3.10): it evaluates the subject once, narrows it non-null for
  the property part via the new type-taking
  `ensureShallowNonNullabilityFromTypes()`, fires the rule callback for the
  virtual plain fetch itself and stores a result for it, and its
  specifyTypesCallback emits the plain-chain dual key (one structural
  `getNullsafeShortcircuitedExpr` call) plus a subject-not-null entry —
  replacing the old dispatcher-built `BooleanAnd(var !== null, plain)`.
  The plain handler propagates a nullsafe var's short-circuit null one level
  (no recursion). `ExpressionResult` gains **companionResults** so
  applySpecifiedTypes can price the plain variant's original type from the
  stored plain result. `FiberScope::getScopeType/getScopeNativeType` rerouted
  through the result path (the reserved scope-walk design pending — flagged).
  Leg coverage: 89.5%+ of executable changed lines via 18 new corpus probes
  (non-nullable/null/array-dim subjects, chains, dynamic names, native asks,
  bare-statement context); the rest are defensive throws and rule-only paths.
- 2026-06-10 (boolean leg): **`BooleanAndHandler` + `BooleanOrHandler` migrated** — the
  single-pass showcase. The right operand is evaluated on the left-truthy/left-falsey
  scope during processing, so the typeCallback composes the two child results directly:
  no `processExprNode` re-walk on a throwaway storage, no
  `BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH`, no flattened-chain fast path in the new world
  (the old ones stay for FNSR=0). Meter demo: a 6-arm `&&`/`||` chain (depth 5 > old cap
  4) narrows every arm and constant-folds whole-chain asks under `disableOldWorld=true`.
  specifyTypesCallback is the old math with child narrowing from the child results
  (`specifyChildTypes`: result callback, or the old dispatcher *with the adapter scope*
  for unmigrated children); `normalize()`/conditional-holder helpers price their
  narrowing-original asks through `ResultAwareScope`s seeded per base scope (§3.13).
  Found and fixed in the process:
  - branch-scope preference flip + legacy-callback strip (§3.13 first bullet; the
    representation drift showed up as 6 nsrt diffs: mixed-subtract, pr-5379, bug-9400,
    bug-7156, while-loop-variables, bug-14047);
  - per-base adapter seeding (§3.13 second bullet; `ReflectionProviderGoldenTest`
    remembered-`is_bool()` leak);
  - `FuncCallHandler::specifyTypesViaResults` dynamic-name fall-through invokes the
    old-world body (`specifyTypesFromCallableCall` + default context) directly — the
    dispatcher round-trip bounced an adapter scope back into the seeded self-result
    forever (4.5M-frame stack in nsrt);
  - virtual nodes built by handlers embed `toFiberScope()` scopes in the new world
    (`BooleanAndNode`/`BooleanOrNode` right scope) so the ConstantCondition rules'
    `getRightScope()->getType()` asks resolve through the stored right result;
  - `ResultAwareScope` answers Yes-tracked plain variables from the holder (tier 2½) —
    filter-derived adapters lose their context (`plainScope === null`) and variables
    fell through to the guarded bridge; superglobals (Yes-defined, no holder) keep
    falling through.
  Scoreboard: corpus 194/194 (17 new probes: deep chains, const folds via parent asks,
  inside-out narrowing, representation pins for both regressions, statement null-ctx,
  Or-in-And falsey, unmigrated-arm fall-through, isset-holder re-derivation, dynamic-name
  call in condition); nsrt at the known 6; CallMethods/BooleanAnd/BooleanOr/ImpossibleCheck
  rule tests green; `make phpstan` 204 = HEAD parity; FNSR=0 spot checks byte-identical.
  Changed-line coverage (corpus + guard-on meter merged): And 84%, Or 90%,
  ResultAwareScope 100% — remaining gaps are defensive throws, closure-closing braces,
  one fold return measured-missed under fibers (output proven by the meter), the
  truthy-and-false holder re-derivation pair, and `setAlwaysOverwriteTypes` propagation.
- 2026-06-10 (ternary leg): **`TernaryHandler` + `BooleanNotHandler` migrated**, and the
  AssignHandler Ternary conditional-holder block unlocked. The ternary typeCallback
  composes the branch results — each was evaluated on the matching cond-narrowed scope,
  so the old resolveType's cond re-processing on a throwaway storage dies; the short
  ternary asks the cond on its truthy scope via `getTypeOnScope` (native asks promote the
  truthy scope first). Narrowing rewrites into the old `(cond && if) || (!cond && else)`
  synthetic and processes it through the *migrated* boolean handlers (unseeded adapter,
  tier 4 — §3.13). BooleanNot: fold via the inner result, incremental swapped branch
  scopes (truthy(!X) = X's falsey scope), context negation onto the inner result.
  AssignHandler's Ternary holder block now takes the cond result's specify callback +
  getTruthyScope/getFalseyScope, adapter-priced branch types, and an entry resolver
  mirroring the assign one (FNSR=0 keeps the old block verbatim).
  Found and fixed: the nullsafe specify callback never ran the plain call's
  type-specifying extensions (`@phpstan-assert-if-true`, bug-12866) — the old synthetic
  `BooleanAnd(var !== null, plainCall)` dispatch provided that; exposed by BooleanNot's
  incremental falsey = nullsafe truthy. `createNullsafeSpecifyCallback` now composes the
  plain call's narrowing through the dispatcher (adapter) when the chain executed —
  until MethodCallHandler narrowing migrates.
  Scoreboard: corpus 219/219 (25 new probes: ternary basics/folds/short-as-condition/
  native, ternary-as-condition synthetic, assign holders incl. untracked entries,
  statement null-ctx ×2, BooleanNot folds and branch narrowing, bug-12866 pin); meter
  demo (8 dumps) green under disableOldWorld=true and byte-identical under FNSR=0;
  nsrt at the known 6; make phpstan 204 = parity; CallMethods/Ternary/BooleanNot/
  BooleanAnd/BooleanOr rule tests green. Changed-line coverage: Ternary 88%,
  BooleanNot 85%, Assign 86%, DefaultNarrowingHelper 100% — gaps are defensive throws,
  braces, the FNSR=0-only branch and one tier-3 delegation line.
- 2026-06-10 (NodeScopeResolver getType sweep): **statement-level `Scope::getType()`
  asks replaced with ExpressionResult asks**, TDD'd under `disableOldWorld=true` with a
  13-construct statement exerciser (if/elseif/else, while incl. always-true, do-while,
  for, foreach, switch, const, unset, @var annotations). Per construct:
  - If/elseif: next-arm scope = the elseif cond result's falsey scope.
  - While: before-cond boolean and last-pass cond boolean from the cond results; the
    loop-exit scope via the new `filterByFalseyValueUsingResult()` (apply path when the
    cond result carries a specify callback, guarded `filterByFalseyValue` for unmigrated
    conds / FNSR=0).
  - Do-while: the cond is processed once (hoisted above the always-iterates check and
    the DoWhileLoopConditionNode callback — rules now see cond sub-exprs first) and the
    single result feeds the boolean, falsey scope, and throw/impure points.
  - For: the last-cond result is captured (was discarded) and feeds always-iterates +
    post-loop falsey filtering; `inferForLoopExpressions` count-pattern asks priced
    through adapters.
  - Foreach: `getForeachIterateeTypes()` computes the iteratee PHPDoc+native pair per
    `$originalScope` (memoized result asks on the eval scope, `getTypeOnScope` on the
    pollute-filtered one) and threads it through the NSR `enterForeach` helper, the
    constant-array unroll, and new `MutatingScope::enterForeach`/`enterForeachKey`
    signatures (no internal asks; no external callers existed); the post-loop dim-fetch/
    key/value re-asks go through per-scope adapters; the traversable throw point takes
    the iteratee type.
  - Switch: exhaustiveness asks the cond result on the case-narrowed scope
    (`getTypeOnScope`); per-case `Equal` synthetics stay with the equality leg.
  - Const_/ClassConst: the value result's types; Unset_ dim-var via adapter;
    `findEarlyTerminatingExpr` called-on types via adapter; `processStmtVarAnnotation`
    and execution-end never-checks via adapters; AssignHandler by-ref array keys via
    adapter; `MethodThrowPointHelper` takes a lazy return-type callback (FuncCall's
    shape; MethodCall/StaticCall pass null = guarded bridge until their legs).
  - `MutatingScope::specifyExpressionType`'s ArrayDimFetch parent-update reads the dim/
    var/native types holder-first (`getTypeFromTrackedHolder`) — the plan's §C tier-1
    resolution, hit via `enterForeachKey`'s dim-fetch holder assignment.
  - `LiteralArrayItem` embeds fiber scopes in the new world (DuplicateKeys rule asks) —
    the BooleanAndNode/BooleanOrNode lesson generalized: any handler-built virtual node
    carrying a scope embeds `toFiberScope()`.
  - ThrowHandler migrated en passant (constant never type).
  Documented residue (raw asks that stay): rule-callback closures already receive
  FiberScope (NSR:958); FNSR=0 branches (If cond ternaries); recursive by-ref
  closure-use self-ask (ClosureHandler leg); by-ref args fallback (documented);
  `createCallableParameters` type callbacks (task #18 — callers price them);
  `filterBy*` over engine synthetics (`$arrayComparisonExpr`, switch case `Equal`,
  maybe-empty foreach merge — the equality/BinaryOp leg).
  Scoreboard: corpus 232/232 (+13 statement probes); meter exerciser green under the
  guard and byte-identical under FNSR=0; nsrt at the known 6; `make phpstan` 204 =
  parity (3 fresh findings fixed: redundant null check, unused truthy helper variant,
  Scope-vs-MutatingScope on the end-node adapter); DefinedVariableRuleTest
  testDynamicAccess failure confirmed pre-existing at HEAD.
- **Known engine debt — `ExpressionResultStorage` memory retention**: every
  `ExpressionResult` (holding its after-scope, callbacks, memoized types) is
  retained for the whole file; `make phpstan` needs ~12.5 GB at 4G-per-worker
  limits and OOMs at the default 599M in nested-foreach files
  (`SplObjectStorage::addAll` in `duplicate()`). Pre-existing at HEAD before the
  array leg (5 OOM errors baseline vs 7 with it). Needs an eviction strategy
  (results evictable once no fiber/conditional holder can still ask — e.g.
  per-statement or per-function clearing, or weak references); per project
  discipline the fix is algorithmic, not a memory-limit bump.
