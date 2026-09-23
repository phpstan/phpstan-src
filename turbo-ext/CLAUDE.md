# turbo-ext — instructions for working on the native extension

Read `README.md` first: how shadowing works, the sync machinery, and
the seven **Design rules for new ports** there are binding. This file is the
operational checklist on top of them.

## Before porting anything: estimate, then decide

Ports pay off by absorbing *call frames*, priced at roughly 40ns per absorbed
userland frame. Count calls first (SPX: `SPX_ENABLED=1` on a self-analysis
run) and multiply — a site called 400× per run can never pay; a site absorbing
millions of tiny calls can. Three finished, correct, all-tests-green tier-1
ports were reverted because they measured ≈0% — being correct is not the bar,
being ≥0.5% faster is. When the estimate is marginal, don't port.

## Shadowing new code — do the steps in this order

1. **Extract the PHP code** into a dedicated class (static methods are fine)
   under `src/`, called unconditionally from the original sites — no
   turbo-conditional branches in callers. Find **all** call sites (beware:
   `grep "\$this->foo"` in double quotes sends `\$` to grep and silently
   matches nothing — use single quotes). Run the full test suite now, before
   any native work.
2. **Note its parent and interfaces** — the native class is declared with
   the twin's real name, final flag, parent and interfaces, and linked
   like a PHP declaration: interface methods need declared return types, a
   non-final class must dispatch its own non-final methods through the
   object's class entry (a PHP subclass may override them). If it is a DI
   service, its native `__construct` arginfo must declare the real
   parameter class names (rule 6 in README): Nette autowires by reflecting
   the constructor, and erased types fail container compilation for every
   shadowed service at once.
3. **Implement natively**: one class per `.cpp` in `src/`, namespace
   `PHPStanTurbo`, class **non-final**, `instanceof`-style checks instead of
   exact class-entry comparisons. Hot classes are registered with the raw
   Zend API in `main.cpp`'s MINIT (raw handler pointers, nothing that
   allocates per call). Reuse the `pt_*` helpers in
   `support.h`/`support.cpp` before writing new ones.

   **Style**: the logic lives in a C++ handle class in `namespace
   phpstan_turbo` that mirrors the PHP twin method for method (see
   `TrinaryLogic.cpp` as the reference; `and`/`or` keyword clashes get a
   trailing underscore); registration goes through the `reg::Class` builder
   in `reg.h` — one declaration per method: a method that only parses its
   parameters and hands them, in order, to a handle member returning
   `zv::Val`, `void` or `bool` with a trailing `bool &` out parameter is
   `cls.method<&Handle::member, zp::Obj, zp::Bool>("name", flags, { args... },
   returns)` with a generated handler; any other glue is a
   `cls.method("name", flags, requiredArgs, { args... }, lambda)` whose
   lambda parses with `zp::parse<zp::Obj, zp::Opt<zp::Bool>>(execute_data,
   ...)` (the raw ZEND_PARSE_PARAMETERS macros only for kinds zp does not
   cover). Both expand to the engine's own ZPP macros.
   Never introduce per-call argument boxing in a registration path — raw
   handler pointers only. Use the zero-cost
   wrappers in `zv.h` — borrowed `zv::Ref` views vs owned move-only
   `zv::Val` RAII values (UNDEF `Val` = pending exception), `zv::ArrRef`
   range-for instead of hand-rolled `ZEND_HASH_FOREACH` (it handles the
   packed layout of PHP 8.2+ — never walk Buckets by hand). Zero-cost is
   the bar: no virtuals, no exceptions, no allocations the raw form would
   not make; where an abstraction is not provably free, keep the raw zend
   form and say so in a comment. New generic helpers go into `zv.h`
   following its conventions, never as one-offs.
4. **Class names the native code needs** go through
   `Runtime::configure()`, fed from the generated `vendor/turbo-class-map.php`:
   add the key to `pt_class_refs` in `support.cpp` and mark the referenced
   class with `#[ReferencedByTurboExtension(key: '...')]` (vendored PhpParser
   classes are hardcoded in `build/TurboAttributeCollector.php` instead —
   `tests/smoke.php` holds the map against the real compiled table via
   `Runtime::classRefs()`). Never reference a shadowed class this way — the
   native code holds its class entry (the `shadow(&pt_ce_x)` out-pointer)
   and instantiates it directly.
5. **Mark the class** with
   `#[ShadowedByTurboExtension(implementation: __DIR__ . '/../turbo-ext/src/Foo.cpp')]`
   and run `composer dump-autoload` — `build/generate-turbo-manifest.php`
   regenerates the manifest of shadowed pairs in
   `vendor/turbo-shadowed-classes.json` and the class map in
   `vendor/turbo-class-map.php` from the attributes (shadowed classes
   living in vendor/ cannot carry the attribute and are hardcoded in
   `build/TurboAttributeCollector.php`).
5a. **Generate its declarations**: `php turbo-ext/bin/generate-declarations.php`
   writes `turbo-ext/src/generated/<Stem>.h` from the twin — `declareClass(cls)`
   (final/abstract, parent, the directly implemented interfaces),
   `declareProperties(cls)` (the twin's own properties, exactly) and the
   `slot::` constants of its instance properties, and `sig::` — each
   method's name, flags, arginfo and return type. Call both functions first
   in the registration function and register the methods by signature
   (`cls.method(sigs::accepts, handler)`, `cls.method<&Handle::accepts,
   zp::Obj, zp::Bool>(sigs::accepts)`) instead of spelling them out; side-by-side.php
   fails while a header is stale. A class whose native properties deliberately
   differ from the twin keeps declaring them by hand.
6. **Check method parity**: `php bin/side-by-side.php` must pass (it also
   re-derives the generated `vendor/turbo-*` files from the attributes and
   byte-compares them, so a stale autoloader dump fails there).
7. **Extend `tests/smoke.php`** with differential coverage (native result
   must equal the PHP implementation's result on the same inputs) and
   register the class in `$covered` next to its checks — the completeness
   check at the end fails for any shadowed class with no registered
   coverage. A `Type` port goes into `tests/type-family.php` instead
   (observations under the real names, run by `smoke.php` once per
   implementation and compared — the prefixed declaration cannot mix a
   native result object into the PHP compound types) and is registered in
   `$covered` at the section of `smoke.php` that runs it.
8. **Verify**: strict build, smoke test,
   `php -d extension=$PWD/turbo-ext/phpstan_turbo.so turbo-ext/tests/signature-parity.php`
   (arginfo parameter names must match the PHP twin exactly — named
   arguments — and optional parameters need `reg::withDefault()` so named
   arguments can skip them),
   full `make tests` with the extension loaded, and byte-identical analysis
   output with the extension loaded vs. not loaded. Anything touching
   `src/parser/` additionally runs `turbo-ext/tests/parser-corpus.php` and
   `turbo-ext/tests/parser-upstream-corpus.php` (byte-identical ASTs over
   the whole corpus and over php-parser's own test cases); a php-parser
   version bump in composer.lock requires the same.
9. **Benchmark** (protocol below). ≤0.5% → revert the port, keep the PHP
   extraction only if it stands on its own.
10. **Version bump** (only when `turbo-ext/src/` changed): commit the
    change, then run `make bump-turbo` — it sets
    `TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION` to
    `git log -1 --format=%H -- turbo-ext/src | cut -c1-7` and commits the
    follow-up (rerun it after a rebase: it refreshes an unpushed bump
    commit in place).
    The binary's own version is baked from git at build time (builds outside
    the monorepo read the VERSION.txt that subsplit-turbo-ext.yml generates
    into phpstan/turbo-ext — never create that file here), so only the PHP
    constant is maintained. The bump cannot be part of the same commit —
    the SHA would change under it — and must be recomputed after the change
    lands on the target branch (a pull request commit gets a new SHA when
    rebased). A PHP-twin-only edit needs no bump, but still needs the parity
    checks and the port.

## Porting analysis-engine classes (handlers, NodeScopeResolver)

NodeScopeResolver, StatementsHandler, the ExprHandler / StmtHandler classes,
their processors and helpers and the analyser value classes they trade calls
with are ported as one engine. Only rules and extensions (dynamic return type
and type-specifying extensions, rule callbacks, third-party code) stay PHP
for good; a native class calling into an analyser-internal PHP class pays a
full userland call per crossing, so every such call is future work — write it
so the next port can switch it in one place. The foundation is `src/Engine.h`
(implemented in `Engine.cpp` and `ExpressionResult.cpp`); ScalarHandler.cpp and
VariableHandler.cpp are the reference handler ports.

- **Closures the twin creates** (typeCallback, specifyTypesCallback,
  createTypesCallback, callbacks handed to helpers) are native closures:
  `pt_native_closure(&body, captures...)` builds a
  `PHPStanTurbo\NativeClosure` — the C++ body plus the captured values in one
  allocation — with `body(zval *captures, uint32_t argc, zval *argv, zval
  *return_value)`. Capture exactly what the PHP closure captures, in a fixed
  order, `$this` (the handler object) included when the body reads its
  properties: values are copied like `use ($x)`; `pt_native_closure_new(fn,
  count, captures, byReferenceMask)` keeps masked IS_REFERENCE captures as
  references (`use (&$x)`). The holder is callable from PHP like the closure
  (`$cb()`, call_user_func, `callable` parameters, clone); native code calls
  it through `pt_type_call_callable()` / `pt_call_fci()` without a frame.
  Where a PHP signature demands `Closure`, pass
  `pt_native_closure_to_closure(holder)`. Never compile or bind userland code.
- **`$this->expressionResultFactory->create(...)`** is
  `pt_expression_result_create(factory, args)`: the required parameters in the
  `pt_expression_result_args` constructor (NULL = null, NULL throw/impure
  points = `[]`), each named optional argument of the twin's call through its
  `with*()` setter. For the container-generated factory the native
  ExpressionResult is constructed directly (the extensions collection is
  learned from the factory's first result and cached per factory object); any
  other factory gets `create()` with the same named arguments. Read results
  with `pt_expression_result_get_type()` / `_get_native_type()` /
  `pt_expression_result_variable_flow()` and the inline borrowed slot readers
  of `AnalyserValues.h` (`pt_expression_result_scope(result, hold)`,
  `_before_scope()`, `_expr()`, `_throw_points()`, `_impure_points()`,
  `_has_yield(result, out)`, `_is_always_terminating()`) — the same readers
  the other analyser value classes (throw points, statement results, args
  results, ...) have there.
- **Handler dispatch**: a native handler registers its processExpr() /
  processStmt() body right after `cls.shadow(&pt_ce_x)` with
  `pt_expr_handler_entry_register(&pt_ce_x, &X::processExprEntry)` /
  `pt_stmt_handler_entry_register(...)`. Callers use
  `pt_expr_handler_process(handler, nodeScopeResolver, stmt, expr, scope,
  storage, nodeCallback, context)` / `pt_stmt_handler_process(...)`: the
  registered body directly, a PHP handler's method through a zend_function
  resolved once per class. Resolve handlers with
  `pt_expr_handler_registry_resolve()` / `pt_stmt_handler_registry_resolve()`.
  Other public handler methods called across handlers are exported as
  `pt_<handler>_<method>(zval *handler, ...)` — the native body when
  `Z_OBJCE_P(handler)` is the native class, the method by name otherwise
  (`pt_variable_handler_compose_result()`).
- **Contexts**: `pt_expression_context_*()` / `pt_statement_context_*()`
  (factories, derivations, getters).
- **PHP collaborators that are not ported yet**: one small helper per called
  method, grouped in a block at the top of the file, each over a
  file-level `pt_method_site` (`pt_call_method_cached()` /
  `pt_call_static_cached()` resolve the zend_function once per class per
  request); a PHP object's declared property through a `pt_property_site`.
  Classes you instantiate or call statically go through the class map (rule
  5); drop the key when their port lands.
- **DI services** (`#[AutowiredService]`): register `__construct` by its
  generated signature — Nette reflects the arginfo — and write the promoted
  slots. The processExpr() glue parses its seven parameters with the raw
  ZEND_PARSE_PARAMETERS macros (zp::parse stops at six).
- **Verification**: `tests/walk-trace.php` records a whole NodeScopeResolver
  walk (every node the callback sees, both type flavours of every expression,
  the scope at every statement) once with the PHP classes and once with the
  natives, in separate processes, and requires identical traces:
  `TURBO_DLL=$PWD/turbo-ext/phpstan_turbo.so php turbo-ext/tests/walk-trace.php --shards=8`
  (default corpus: NSRT, src/Analyser, src/Type/Constant; pass paths to narrow
  it, `--keep=DIR` to inspect a divergence). Register engine classes in
  smoke.php's `$coveredElsewhere` with `'walk-trace.php'`, or add a prefixed
  differential next to the twin (the ports then run on the PHP collaborators,
  which exercises every direct entry's fallback path — see the ScalarHandler /
  VariableHandler section).

- **The walk hub** (NodeScopeResolver.cpp, StatementsHandler.cpp): call
  `pt_node_scope_resolver_process_expr_node()` / `_process_stmt_node()` /
  `_process_stmt_nodes_internal()` / `_call_node_callback()` /
  `_store_expression_result()` / `_process_expr_on_demand()` and the other
  `pt_node_scope_resolver_*` / `pt_statements_handler_*` entries (support.h)
  instead of the methods — NodeScopeResolver is not final, so they take the
  native body only for exactly the native class.
- **A twin's `finally` block** is `pt_finally([&]() { ... })` (Engine.h): it
  runs with a pending exception set aside, so the calls it makes into PHP
  execute, and chains an exception it throws itself like the engine does.
- **Node callbacks and gatherer frames** go through
  `pt_engine_call_node_callback()` (native closures and the native
  ClassStatementsGatherer directly, any object callable without resolving it
  by name). `pt_engine_node_get_attribute()` / `_set_attribute()` /
  `_get_comments()` read a php-parser node's attributes array directly.
- **Native recursion over the AST or the walk** wraps its step in
  `pt_engine_with_stack([&]() { ... })`: when the C stack left above PHP's
  limit runs low, the step continues on a fresh C stack segment — the PHP twin
  recursed on the VM stack and must not become less robust natively.

## Build and verify commands

```bash
cd turbo-ext
make WARN_FLAGS="$(make -s print-warn-flags)"
php -d extension=$(pwd)/phpstan_turbo.so tests/smoke.php   # must print ALL OK
```

`print-warn-flags` prints the strict set the CI compile legs build with. It
is defined once, as `STRICT_WARN_FLAGS` in the Makefile, so this command and
the workflows cannot drift apart. The three `-Wno-` exemptions there are for
zend macro expansions only; new warnings in our code are fixed, not exempted.
Zend header noise is handled by the pragma guards in `support.h`.

That comment also records the warnings measured and **rejected**, with the
counts that rejected them — add a candidate only after measuring it over
every translation unit with both compilers, and note that our files report
relative paths while the engine's headers report absolute ones, which makes
an our-code-vs-engine split easy to get backwards.

`make` also applies hardening and size flags by default — `HARDENING_FLAGS`,
and the `-fvisibility=hidden -fno-exceptions -fno-rtti` line in `CXXFLAGS` —
each probed against the compiler in use, since the targets disagree about
nearly all of them. **Adopt a codegen flag only with its own measurement**
(interleaved A/B pairs on user CPU) and record the number beside it, the way
the existing entries do; the full-workload runs have a ~±2% noise floor, so
screen on a smaller target where the floor is ~0.35%. Measured on
2026-09-23 with the extension really loaded (see the protocol below): PGO
4.0% faster on Linux and 3.6% on macOS (kept); GCC LTO 3.9% faster on top of
PGO and 15% smaller (adopted for GCC); clang full LTO 1.2% faster but 9%
larger (not adopted); `-Os` 10.4% slower (rejected); dropping unwind tables,
section GC, stripping, cold registration and the pointer-free generated
signatures — size only, no measurable effect on time. Earlier entries —
thin LTO (+1.76% slower), `-O3` (wash) — were measured loading the extension
with `-d extension=`, which PHPStan's OPcache restart can silently drop;
re-measure before relying on them. `-fstrict-flex-arrays=3` traps on the
engine's struct-hack, and `-D_GLIBCXX_ASSERTIONS` is absent only because it
was never measured on a Linux host — the libc++ equivalent was.

Static analysis and sanitizers, from the repository root (both gate in CI,
`.github/workflows/lint.yml`):

```bash
make lint-turbo       # clang-tidy, check list in turbo-ext/.clang-tidy
make sanitize-turbo   # the differential tests under UBSan, from a clean build
```

A finding is fixed, never baselined and never silenced with `NOLINT` — if a
check is wrong *for this codebase* (several are: the analyzer models a zval
read as an uninitialized union access, and other checks object to engine
macros or to the handler signatures), disable it in `.clang-tidy` with the
count it produced and the reason, the way the existing entries do. Generated
sources are excluded from linting; fix their generator instead.

The clang-tidy version is pinned by `CLANG_TIDY_VERSION` in
`turbo-ext/Makefile` — CI reads that number and installs exactly it, and the
target refuses to run with another major. Two versions report two different
trees, so without the pin a green local run would not mean what a green CI
run means, and the counts in `.clang-tidy` would hold for neither.

## Benchmark protocol

Judge **user CPU**, never wall clock, on interleaved A/B pairs (the machine
drifts ±1s thermally — never run all A then all B):

```bash
bin/phpstan clear-result-cache -c build/phpstan.neon -q
mkdir -p /tmp/turbo-ini && echo "extension=$PWD/turbo-ext/phpstan_turbo.so" > /tmp/turbo-ini/turbo.ini
PHP_INI_SCAN_DIR=":/tmp/turbo-ini" /usr/bin/time php -d memory_limit=6G bin/phpstan analyse -c build/phpstan.neon --debug -q src
# baseline runs: drop PHP_INI_SCAN_DIR. Nothing disables a loaded extension,
# so keep it out of php.ini and load it per run instead
```

Never load it with `php -d extension=` for a `bin/phpstan` run: when the
OPcache settings are not the ones TurboProcessRestarter wants (OPcache off
for CLI, timestamps validated — the CI images and a default Homebrew PHP
both), PHPStan re-executes itself, and a command-line `-d extension=` does
not survive the restart. The run then measures PHP alone, while
`php -d extension=... -r 'echo phpversion("phpstan_turbo");'` still reports
the version. Confirm with a quantity that must change: user CPU drops by
roughly 40% with the extension really active. (The tests under `tests/`
run no restart, so `-d extension=` is fine there.)

Output identity: `--error-format=raw` runs in both modes must diff empty.

## Zend-level gotchas (each of these cost real debugging time)

- PHP literal `[]` is the read-only `zend_empty_array` in RODATA:
  `ZVAL_ARR`/`RETVAL_ARR` force refcounted flags and a later addref SIGBUSes.
  Check `GC_FLAGS(ht) & IS_ARRAY_IMMUTABLE` first (see `pt_*` array helpers).
- Expression-table keys can be numeric strings that PHP coerced to int keys:
  always use `zend_symtable_*` / the dual string+index `pt_ht_*` helpers,
  never plain `zend_hash_find` on user-derived keys.
- Private userland methods are callable from C via `ce->function_table`
  lookup + `zend_call_known_function` — no visibility check applies.
- Globals are plain statics; keep new state in `pt_globals`. This holds in
  ZTS builds too — PHPStan's CLI processes are single-threaded (parallelism
  is worker processes, not threads) — but only EG()/CG() access is truly
  thread-aware (via the TSRMLS cache in `main.cpp`), so never introduce
  actual multi-threaded use.
- Node callbacks run nested walks — native code may be re-entered.
- Cloned scopes must reset per-instance memo properties to constructor
  defaults.

## Updating php-parser (the native parser engine)

Follow README.md's "Updating php-parser" procedure. The agent-relevant traps:

- The reduce actions are GENERATED — never hand-edit
  `ParserRunnerActions{1,2,3}.cpp` or `ParserRunnerActionsSplit.h` (CI
  regenerates and diffs). After any `Php8.php` change, run
  `php turbo-ext/bin/generate-parser-actions.php`; it fails listing any
  closure whose body changed upstream and has no override — port those
  (usually by updating the matching
  `src/parser/action-overrides/<sha1>.inc`; overrides are keyed by body
  content, so pure renumbering never needs hand work), re-run, then
  corpus-verify.
- `ParserEngine::reduce` dispatches via `PN_REDUCE_SPLIT_1/2` from the generated
  `ParserRunnerActionsSplit.h`; the generator rebalances the three action
  files automatically.
- The corpus differentials are the acceptance bar — byte-identical
  serialized ASTs, errors, and token streams. `tests/parser-upstream-corpus.php`
  runs php-parser's own test cases at the installed commit, so it covers new
  syntax as soon as the lock file moves; `tests/parser-corpus.php` only
  proves what the repo contains, so new syntax also needs fixtures here.
- Finish with both pins: `SUPPORTED_PHP_PARSER_VERSION` in
  `.github/workflows/phar.yml` plus the extension version bump in
  `TurboExtensionEnabler` (`src/parser/` changed).

## PHP-side constraints

- No `match` expressions in `src/` (the PHP downgrade tooling cannot handle
  them) — use `switch` or if/else.
- The PHP twin stays the reference implementation: fix behavior there first,
  port second, and keep both sides structurally parallel so they stay
  reviewable next to each other.
