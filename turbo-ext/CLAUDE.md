# turbo-ext — instructions for working on the native extension

`README.md` is the user-facing overview. This file is for working on the
extension: the operational checklist first, then the reference — how
shadowing works, the sync machinery, the native parser, the arena, building,
the shared core. The seven **Design rules for new ports** in the reference
are binding.

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
   parameter class names (design rule 6 below): Nette autowires by reflecting
   the constructor, and erased types fail container compilation for every
   shadowed service at once.
3. **Implement natively**: one class per `.cpp` in `src/`, namespace
   `PHPStanTurbo`, class **non-final**, `instanceof`-style checks instead of
   exact class-entry comparisons. The file registers its class with the raw
   Zend API (raw handler pointers, nothing that allocates per call) from its
   own `PT_MINIT_REGISTRATION(pt_register_foo) { ... }` function
   (`support.h`): MINIT runs every such function, and the build files glob
   `src/*.cpp` — a new file needs no edit in `main.cpp`, `support.h` or
   `config.w32`. Reuse the `pt_*` helpers in `support.h`/`support.cpp`
   before writing new ones.

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
10. **Version**: nothing to bump. Both sides are
    `git log -1 --format=%H -- turbo-ext/src | cut -c1-7` — baked into the
    binary at build time (builds outside the monorepo read the VERSION.txt
    that subsplit-turbo-ext.yml generates into phpstan/turbo-ext — never
    create that file here), and written into
    `vendor/turbo-extension-version.php` for `TurboExtensionEnabler` on every
    autoloader dump and by `make build-turbo`. After committing a
    `turbo-ext/src/` change and rebuilding with a plain `make` in
    `turbo-ext/`, run `composer dump-autoload` (or build with
    `make build-turbo` from the root), or the extension stays inactive.

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
OPcache settings are not the ones RestartManager wants (OPcache off
for CLI, timestamps validated — the CI images and a default Homebrew PHP
both), PHPStan re-executes itself, and a command-line `-d extension=` does
not survive the restart. The run then measures PHP alone, while
`php -d extension=... -r 'echo phpversion("phpstan_turbo");'` still reports
the version. Confirm with a quantity that must change: user CPU drops by
roughly 40% with the extension really active. (The tests under `tests/`
run no restart, so `-d extension=` is fine there.)

Output identity: `--error-format=raw` runs in both modes must diff empty.

## Shared core: code must not depend on the PHP version's layout

Everything outside `Abi.cpp`, `Shadow.cpp`, `TrustedTypes.cpp` and
`main.cpp` is compiled once per platform into the shared core and runs on
every supported PHP ("Shared core" below). In those sources:

- never read a `zend_class_entry` member after `ce_flags` directly — use
  `PT_CE(ce, member)`, adding the member to `PT_ABI_CLASS_ENTRY_MEMBERS` if
  it is new; object handlers through `PT_OBJ_HANDLER()` / `Z_OBJ_HANDLER()`
  (add new ones to `PT_ABI_OBJECT_HANDLERS`), `internal_function.handler`
  through `PT_INTERNAL_HANDLER()`, user `arg_info[i]` through `PT_ARG_INFO()`;
- a new `EG()`/`CG()` field fails to compile until it is added to
  `PT_ABI_EG_FIELDS` / `PT_ABI_CG_FIELDS` — add it there;
- no `#if PHP_VERSION_ID`: `PT_ABI_SINCE(version, then, otherwise)`, or an
  engine call whose signature differs goes into `Abi.cpp` behind a `pt_abi`
  function pointer;
- the version-specific sources call the core only through `PT_CORE_API`
  functions (never data), and the core never calls them.

Run `turbo-ext/bin/shared-core/check-linux.sh` (needs Docker) after touching
engine-facing code; CI's `turbo-shared-core-gate-compare` fails on any
difference it finds (without holding up the phar commit, so watch for it),
and never dismiss one without naming its mechanism.

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

Follow the "Updating php-parser" procedure in the reference below. The agent-relevant traps:

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
- Finish with the pin: `SUPPORTED_PHP_PARSER_VERSION` in
  `.github/workflows/phar.yml`.

## PHP-side constraints

- No `match` expressions in `src/` (the PHP downgrade tooling cannot handle
  them) — use `switch` or if/else.
- The PHP twin stays the reference implementation: fix behavior there first,
  port second, and keep both sides structurally parallel so they stay
  reviewable next to each other.

# Reference

## How it works — shadowing under the real names

Every shadowed piece of PHP code follows the same three steps:

1. The code is extracted into a dedicated PHP class (plain PHP, this is what
   runs when the extension is absent) — e.g. `PHPStan\Analyser\ScopeOps`,
   `PHPStan\Node\NodeScanner`, or an existing value class like
   `PHPStan\TrinaryLogic`.
2. The extension implements the same class natively (one class per file in
   `src/`), registered through the `reg::Class` builder with the twin's real
   name, final flag, parent and interfaces: `reg::Class cls("PHPStan\\TrinaryLogic"); cls.final(); … cls.shadow(&pt_ce_trinary);`
3. The PHP class is marked with the `#[ShadowedByTurboExtension]` attribute.
   On every `composer dump-autoload`, `build/generate-turbo-manifest.php`
   collects the attributes with runtime reflection into
   `vendor/turbo-shadowed-classes.json` — the manifest of shadowed pairs
   (shadowed classes living in vendor/ cannot carry the attribute and are
   hardcoded in `build/TurboAttributeCollector.php`; currently
   `PhpParser\NodeTraverser`).

Nothing is registered under PHPStan's names at module startup:
`reg::Class::shadow()` only records a plan. Activation happens in PHP —
`TurboExtensionEnabler::activateIfCompatible()`, called right after the
Composer autoloader registers — which checks the extension's version and
calls `Runtime::activateShadowing()` (`Shadow.cpp`). That declares each plan
as a linked *user* class carrying the twin's real name: built the way the
compiler builds one (`zend_initialize_class_data`, internal method entries,
`zend_do_link_class()`), so the parent and the interfaces are resolved
through the autoloader and the class passes the same inheritance and
signature checks a PHP declaration gets. `PHPStan\TrinaryLogic` *is* the
native class; the PHP twin is never loaded, and every reference, `instanceof`,
type hint and DI lookup resolves to it. PHP classes may extend a native
class and a native class may extend a PHP one or another native one. The
twin's source file is recorded as the class's file, so reflection — and
PHPStan's own `AutoloadSourceLocator` — keeps reading the PHP declaration
with its PHPDocs and attributes.

Internal classes could not do this: they are registered at module startup,
before any userland parent or interface exists, and PHPStan's classes
implement userland interfaces (`PHPStan\Type\Type`) and extend each other.

The consequences for a port:

- A native method calling another non-final method of its own class must
  dispatch through the object's class entry (fast path when the object is
  of the native class itself), because a PHP subclass may override it.
- Methods implementing an interface declare their return types (reg.h's
  `returns` argument) — the engine checks covariance at link time; parameter
  types may still be erased (contravariance allows it).
- The differential tests run the extension the other way round:
  `tests/activate-prefixed.php` declares the native classes as
  `PHPStanTurbo\<ShortName>` next to the PHP twins in one process, so both
  sides can be compared. The manifest derives that name the way the
  extension derives it, and rejects two shadowed classes sharing a short
  name — they would arrive at one native name. The Type ports are the
  exception: a Type's results flow into the PHP compound types and back
  through `self`-typed statics (`IsSuperTypeOfResult::extremeIdentity()`),
  so a native result object meeting the PHP result class is a TypeError.
  `tests/type-family.php` therefore observes the whole Type family under
  the real names — once as the PHP twins, once with the natives activated
  in their place, exactly as production runs — and `smoke.php` runs both
  and requires the two observation sets to be identical.

Class names the native code references at run time come through
`PHPStanTurbo\Runtime::configure()`: `TurboExtensionEnabler` feeds it the
generated `vendor/turbo-class-map.php`, derived from the
`#[ReferencedByTurboExtension]` attributes (vendored PhpParser classes are
hardcoded in the collector), so a renamed class updates the map on the next
autoloader dump. Shadowed classes are never referenced that way — the native
code holds their class entries itself and instantiates them directly.

Fork mode (see `ForkParallelChecker`) uses two more entry points:
`Runtime::enablePharForkGuard()` gives each pcntl_fork()ed worker a private
cursor on the running phar's fd (`PharForkGuard.cpp`), and
`exitImmediately()` — `_exit()` with the engine's exit status — is how
`ForkedChildTerminator` ends a forked worker. `exitImmediately()` lives on
`PHPStan\Turbo\ForkedChild`, a shadowed class that is part of PHPStan's
`@api` (`ForkedChild.cpp`), because a program that forks its own workers
needs it too; its PHP twin throws `TurboNotActiveException`. The `Runtime`
methods cannot move there: `TurboExtensionEnabler` calls them before
shadowing is active. A forked child inherits every loaded extension but none of
their threads, so PHP's full teardown can wedge in a fork-unsafe extension's
module shutdown (ext-grpc without `grpc.enable_fork_support`); a forked child
that does not exec() must `_exit()` instead.

`Runtime::trustTypesUnder()` is the one entry point that changes how PHPStan's
own code runs rather than replacing it: it arms an opcache optimizer pass
(`TrustedTypes.cpp`) that drops the engine's argument and return type checks
from scripts compiled under the given prefix — the running phar, passed by
`TurboExtensionEnabler::trustOwnTypesIfSuitable()` — the running phar, or the
source checkout `bin/phpstan` runs from. PHPStan's code is verified
by PHPStan, so those checks re-check what analysis proved, at about 8% of the
analysis CPU. Nothing outside the prefix is touched, and a check sits in the
callee, so extensions and bootstrapped code keep checking what PHPStan hands
them; what goes is the TypeError at the boundary when *they* pass PHPStan a
wrong value — a `--debug` run keeps the checks for exactly that. Float-coercing
signatures, typed variadics and typed property writes stay checked
(`TrustedTypes.cpp` explains each); `tests/trusted-types.php` pins the
behaviour.

The extension is version-pinned (`TurboExtensionEnabler::getExpectedVersion()`);
a mismatched extension never gets `activateShadowing()` called and the PHP
implementations load as usual (`Runtime`'s fork guard and trusted-types pass
do not depend on it). There is no runtime kill switch — the only way to run
without it is not to load it.

The version is the short SHA of the last commit that touched `turbo-ext/src/`,
and neither side of the comparison is maintained by hand. The binary's
version is baked in at build time — the Makefile computes it from git over
that path. The expected side is computed the same way
(`build/PHPStan/Build/TurboExtensionVersion.php`) and written into
`vendor/turbo-extension-version.php` on every autoloader dump; the phar
compilation dumps the autoloader too, so the phar carries the version of the
commit it was compiled from, and `make build-turbo` regenerates the file, so
a rebuild after a `turbo-ext/src` commit needs no dump. The compile jobs
verify each built binary reports what `php build/turbo-extension-version.php`
prints, and the compiler job that the phar carries it. A PHP-twin-only edit
does not move the version — keeping the pair in sync is on the review and
the differential tests, not the version gate. Builds outside the monorepo — the phpstan/turbo-ext
subsplit and PIE source builds from it — cannot ask git (the subsplit's
replayed commits have different SHAs, tarballs have no checkout at all), so
the subsplit workflow generates and commits a `VERSION.txt` there and the
builds fall back to it; the file must never exist in the monorepo. With
neither git nor `VERSION.txt` the version bakes as "dev", which the enabler
rejects — the extension then simply stays inactive.

## Keeping the two implementations in sync

The manifest of shadowed pairs — each PHP class and the C++ file
implementing it natively — is derived from the `#[ShadowedByTurboExtension]`
attributes: the attributed file is the PHP side, the attribute names the
native class and the implementing `.cpp`. Nothing is maintained by hand;
`build/generate-turbo-manifest.php` derives the same map into
`vendor/turbo-shadowed-classes.json` on every `composer dump-autoload` (for
the runtime consumers: the enabler's activation, the phar's preload
builder, `tests/signature-parity.php`), and the vendored
`PhpParser\NodeTraverser` pair, which cannot carry the attribute, is
hardcoded in `build/TurboAttributeCollector.php` — the collection and
rendering shared by that script and `bin/side-by-side.php`. The manifest
drives three things:

- **CI method parity** — `php bin/side-by-side.php` (part of the version
  job, needs vendor/) verifies every public method of each PHP class has a
  `PHP_METHOD` counterpart in the C++ file and every `PHP_METHOD` corresponds
  to a method of the PHP class. Non-public PHP methods may stay PHP-only
  (native code inlines them or uses C helpers). It also verifies every
  class-defining `.cpp` corresponds 1:1 to the attributes and declares the
  twin's exact name, and re-derives the two generated `vendor/turbo-*` files
  from the attributes and
  byte-compares them, so a stale autoloader dump (or a hand edit of a
  generated file) fails.
- **CI signature parity** — `php tests/signature-parity.php` (compile job,
  needs the built extension and vendor/) reflects each native class against
  its PHP twin: final flag, parent, interfaces, and per method visibility,
  staticness, parameter names/optionality/by-ref/variadic, and
  types. It also verifies each manifest entry points at the file the class
  actually lives in (and that the `vendored` flag matches), so a stale
  autoloader dump fails instead of silently comparing against the wrong
  source. Native arginfo may erase types to none/`object` (baking class
  names into the binary would couple it to userland names, and engine-level
  type checks cost per call), but what it does declare must match, and
  parameter names must match exactly — a renamed parameter would break named
  arguments only in turbo mode.
Semantic equivalence is still proven by the differential smoke test and by
running the full test suite with the extension loaded — the manifest checks
guard structure, not behavior. The smoke
test also guards two structural invariants at the real runtime: the
generated class map must cover the native class-reference table exactly
(`Runtime::classRefs()`), and every shadowed class must have registered
differential coverage (in `$covered` there, or via its dedicated script).

## Design rules for new ports

Measured in the July 2026 benchmarks (callback-free absorptions gained
5–8.5% each, callback-dense ones ~1% or nothing):

1. **Cross the PHP/C++ boundary per operation, never per element.** Absorb a
   whole loop into one native call; a native loop invoking a PHP callback per
   element performs like the PHP loop it replaced.
2. **Fast paths natively, callbacks only on slow paths** (pointer-compare
   before `Type::equals()`, etc.).
3. **Resolve callables once per site** (`zend_function` pointers cached in
   plans/caches).
4. **Third-party userland objects degrade per-operation, never per-element.**
5. **No materialization at the boundary** — operate on the engine's own
   zvals/hashtables in place. This is also why every class is registered with
   raw handler pointers: a framework trampoline that boxes each argument per
   call is exactly the per-element boundary cost these rules forbid. (The
   extension originally hosted its lifecycle in PHP-CPP; it is a plain Zend
   module since the Windows port.)
6. **A shadowed DI-service class has to stay autowirable.** Nette reflects
   `__construct` while it compiles the container, so the native arginfo must
   declare the real parameter class names — erasing them fails container
   compilation for every shadowed service at once. (Aliasing a service class
   is what genuinely cannot work: `getByType()` normalizes the requested type
   through reflection to the real class name. A shadowing class carries the
   original name itself, so that does not apply to it.)
7. **Every port must prove itself**: interleaved A/B benchmark on a long run
   (user CPU, result cache cleared) plus a byte-identical output diff. Ports
   measuring ≤0.5% get reverted — the failure mode is silent no-gain, and
   unproven native code is pure maintenance debt.

## The native parser engine

`src/parser/` reimplements php-parser 5.9.0's LALR engine and node building
(`PhpParser\ParserAbstract` + the generated `Parser\Php8`), shadowed through
the `PHPStan\Parser\ParserRunner` seam. The parsing tables are read at run
time from the first `Php8` parser object seen — they are generated data, so
nothing is duplicated — and node classes resolve relative to the parser's
namespace, so no node class name is baked into the binary. Tokenization
stays in PHP's
C tokenizer (one `Lexer::tokenize()` crossing per file); everything after —
the shift/reduce loop, all 482 semantic actions, attribute arrays, node
construction (direct property-slot writes derived from constructor parameter
names; classes with non-trivial constructors call the real PHP constructor),
error recovery, and comment annotation — is native. Non-`Php8` parsers and
non-string inputs fall back to `$parser->parse()`.

Because the input domain is "all PHP source code", method-level parity is not
enough here: `tests/parser-corpus.php` parses thousands of files with both
implementations and requires byte-identical serialized ASTs, identical
collected errors, and identical token streams. `tests/parser-upstream-corpus.php`
runs the same comparison over every input of php-parser's own test suite at
the installed commit (fetched from GitHub, or pass a checkout) — upstream's
cases cover every grammar rule, error recovery and version-gated behavior,
including syntax PHPStan's own code does not use yet. Both run in CI on every
build.

### Updating php-parser

The CI version job pins the php-parser version the engine was ported against
(`SUPPORTED_PHP_PARSER_VERSION` in `.github/workflows/phar.yml`), so a
`composer.lock` bump fails CI until the engine is consciously re-verified:

1. **Diff what is actually ported.** Only two vendored files matter:
   `lib/PhpParser/ParserAbstract.php` (engine loop + semantic helpers →
   `src/parser/ParserRunner.cpp` + `ParserRunnerHelpers.cpp`) and the reduce
   closures in `lib/PhpParser/Parser/Php8.php` (→
   `ParserRunnerActions{1,2,3}.cpp`, generated). The parsing tables need
   nothing — they are generated data read at run time from the parser
   object. New node classes also need nothing: classes resolve by name and
   property plans derive from constructor parameters at run time (only a new
   constructor with real logic needs the `PN_NEW_CTOR` treatment — a table
   in the generator).
2. **Regenerate the reduce actions.** `ParserRunnerActions{1,2,3}.cpp` and
   `ParserRunnerActionsSplit.h` (the `ParserEngine::reduce` dispatch boundaries) are
   generated by `turbo-ext/bin/generate-parser-actions.php` from the
   closures in the vendored `Php8.php`, so rule renumbering costs nothing.
   Run it; it fails loudly listing any closure whose body changed upstream
   (or is new) and has no handling: the transpiler covers the formulaic
   majority, and hand-ported special cases live in
   `src/parser/action-overrides/<sha1-of-normalized-body>.inc` — keyed by
   content, so unchanged bodies keep matching regardless of their rule
   number. Port the flagged bodies (usually by updating the corresponding
   override; the generated cases are the cookbook), re-run until clean.
   Orphaned override files (their body no longer exists upstream) are
   reported as warnings — delete them once their replacement is handled.
   Never hand-edit the generated files: CI regenerates and diffs them.
3. **Verify**: strict build, then `php turbo-ext/tests/parser-corpus.php`
   and `php turbo-ext/tests/parser-upstream-corpus.php` until byte-identical
   over both corpora. The upstream corpus covers the new version's syntax the
   moment the lock file points at it; the repo corpus only covers it once
   fixtures using it exist — PHPStan's own test data for the new syntax, or
   `tests/parser-fixtures/`; make sure they land before or with the bump.
   Then the full test suite and `make phpstan` with the extension loaded,
   and `tests/parser-bench.php` to confirm the speedup held.
4. **Bump the pin**: `SUPPORTED_PHP_PARSER_VERSION` in the workflow (the
   extension version moves by itself with the `src/parser/` commit). The
   version gate is also what protects users: a phar
   ships a consistent extension/sources/php-parser triple, and a stale
   extension build simply deactivates instead of parsing with drifted
   semantics.

The generator itself (`bin/generate-parser-actions.php`) resolves php-parser
constants (`Modifiers::*`, `Stmt\Use_::TYPE_*`, ...) under the Composer
autoloader at generation time, decides per node class between property-slot
writes (`PN_NEW`) and calling the real PHP constructor (`PN_NEW_CTOR`) —
verifying at generation time that slot-write classes have trivial
assignment-only constructors — and fails the build on anything it cannot
prove it handles. A brand-new node class with constructor logic shows up as
such a failure and needs an entry in the generator's class-policy tables.

## The shared-memory arena

`src/ArenaCache.cpp` (shadowing `PHPStan\Cache\ArenaCache`, whose PHP twin is
a cache that never hits) shares lazily-computed read-mostly data across the
parallel worker processes of a single run. The master creates a named
shared-memory object (POSIX `shm_open` / Windows pagefile-backed section)
before spawning workers and passes the name via the worker command's
`--arena` option; whichever process first computes a record publishes it,
and the arena's physical pages are shared, so N workers stop paying N
copies. Lifetime is exactly one run — no persistence, no invalidation: the
master unlinks the name once every worker's TCP hello arrived (the mapping
stays valid; the kernel reclaims the memory with the last process, even
after SIGKILL) and destroys the mapping when the analysis ends.
`PHPSTAN_ARENA=0` disables just the arena.

Records are flat, offset-based, position-independent blobs of data-only PHP
values — nothing in the mapping is ever seen by the GC, so shared pages are
never dirtied by refcounting. Publication is lock-free (bump-allocate,
write, CAS an index slot from 0 with release ordering); racing publishers of
the same key converge on the first writer, wasteful-not-unsafe. Corruption
degrades to a miss via bounds checks — the caller recomputes locally, like a
worker that never attached.

Consumers: the function signature map (`FunctionSignatureMapProvider`),
published once as a hash record and read per-row so workers stop
materializing the multi-megabyte merged map; the per-directory symbol
indexes (`OptimizedDirectorySourceLocatorFactory`), fingerprint-bound and
read lazily per name; and — generically — every data-only entry of
`PHPStan\Cache\Cache`, so each var_export'd cache blob is include()d by one
process per run instead of every worker (object-carrying payloads stay
per-worker: encoding them double-buffers exactly when worker memory peaks).
`tests/arena-smoke.php` is the cross-process differential test.

## Building

```bash
cd turbo-ext
make          # builds phpstan_turbo.so
```

or, from the repository root, `make build-turbo` — the same build, run in
parallel over the machine's cores and followed by a check that the binary's
baked version is the one `TurboExtensionEnabler` activates, after
regenerating `vendor/turbo-extension-version.php` from git (a plain `make`
after a `turbo-ext/src` commit needs a `composer dump-autoload` too, or the
build loads but stays inactive).

The only requirements are a C++17 compiler and `php-config` on PATH (or
passed as `make PHP_CONFIG=...`, `make build-turbo PHP_CONFIG=...` from the
root). Both NTS and ZTS interpreters are supported — the build inherits
thread-safety from the `php-config` it is pointed at (ZTS hosts like PMMP's
bundled PHP get a matching build; PHPStan itself only ever runs the native
code single-threaded).

The standard `phpize && ./configure && make` pipeline works too
(`config.m4`) — it is what [PIE] drives when it builds the phpstan/turbo
package from source, e.g. for combinations without a prebuilt binary. That
path bakes the version from `VERSION.txt` (present only in the
phpstan/turbo-ext subsplit, where its workflow commits it — in the monorepo
build with `make` instead), and its `./configure` overwrites this
directory's Makefile with the generated one (`git restore Makefile` brings
it back; PIE builds in its own extracted copy). The hand-written Makefile
stays the primary build: it statically links libstdc++/libgcc on Linux —
the distributed binaries must not depend on the build host's GLIBCXX symbol
versions — and carries the strict warning setup, neither of which survives
the libtool link.

[PIE]: https://github.com/php/pie

On Windows the extension builds through the standard PHP extension pipeline
(`config.w32`): with a PHP devel pack, [php-sdk-binary-tools] and a VS2022
x64 developer prompt, run `phpize && configure --enable-phpstan-turbo &&
nmake` inside `turbo-ext/`. The toolset generation matters for distribution:
PHP's module loader rejects DLLs linked with a newer MSVC generation than
the PHP core, and the official php.net binaries are built with VS2022
(toolset 14.4x) — so CI builds on `windows-2022`, not `windows-latest`
(whose VS2026 image links with 14.5x). Set the `PHPSTANTURBO_VERSION`
environment variable before `configure` to bake the version (the Makefile
computes it from git automatically; `config.w32` reads it from the
environment, falling back to `VERSION.txt`).

[php-sdk-binary-tools]: https://github.com/php/php-sdk-binary-tools

## Shared core

The distributed binaries are not one self-contained extension per PHP
version and platform: each platform ships one **shared core** with all the
code, and per PHP version a **thin extension** (~70 KB) that PHP loads and
that loads the core from its own directory:

```
turbo-ext/linux-gnu-x86_64/phpstan_turbo_core.so      the core (one per platform)
turbo-ext/linux-gnu-x86_64/phpstan_turbo-8.3.so       the extension PHP 8.3 loads
turbo-ext/linux-gnu-x86_64/phpstan_turbo-8.6-zts.so   ... and thread-safe 8.6 (same core)
turbo-ext/windows-x86_64/phpstan_turbo_core.dll       Windows: one core per
turbo-ext/windows-x86_64/phpstan_turbo_core-zts.dll   thread-safety flavour
```

A self-contained build per version used to repeat ~7 MB of identical
machine code 33 times; the split keeps the same code once per platform.

### What is version-specific

Four sources are compiled per PHP version into the extension — the
Makefile's `SHIM_SOURCES`, and the list in `config.w32`:

- `main.cpp` — the module entry (it carries the engine's API number), the
  version handshake with the core, and on Windows the loading of the core;
- `Abi.cpp` — fills the `pt_abi` table (`abi.h`) at startup with everything
  of the running engine whose layout, value or signature differs between
  minors, and implements per minor what cannot be expressed as data;
- `Shadow.cpp`, `TrustedTypes.cpp` — they build `zend_class_entry` /
  `zend_op_array` structures by hand and walk them.

Everything else is the core, compiled once per platform against any one
version's headers. That is only correct because it compiles to **identical
code** against every supported version's headers — the shared-core gate
(below) proves it on every change. The shared sources reach anything whose
layout moved through `abi.h`:

| what changed between minors | in shared code |
|---|---|
| `EG()`/`CG()` field offsets (every minor) | `EG()`/`CG()` are redefined to read through pointers (`PT_ABI_EG_FIELDS`) |
| `zend_class_entry` members after `ce_flags` (8.6 inserted `ce_flags2`) | `PT_CE(ce, member)` (refuses anything but a class entry) |
| `zend_object_handlers` members (8.5 inserted `clone_obj_with`) | `PT_OBJ_HANDLER(obj, member)`, `Z_OBJ_HANDLER()` |
| `zend_internal_function.handler` (8.4), `zend_arg_info` stride (8.6) | `PT_INTERNAL_HANDLER(fn)`, `PT_ARG_INFO(argInfo, i)` |
| `zend_function_entry` (8.4, 8.6 grew it) | `reg::FunctionEntry`, turned into the engine's by `pt_abi` |
| `ZSTR_KNOWN()` ids, `IS_REFERENCE_EX`, `ZEND_ACC_USE_GUARDS`, lazy-object flags | `pt_abi` values (`PT_ABI_KNOWN_STRINGS`, …) |
| ZPP error codes / `Z_EXPECTED_*` numbering, parse slow paths (8.6) | neutral ZPP macros in `abi.h`, translated by `Abi.cpp` |
| functions whose signature or behaviour changed (`zend_create_closure`, `zend_dval_to_lval`, …) or that stopped being exported (`zend_call_known_function`, …) | function pointers in `pt_abi`, implemented in `Abi.cpp` |
| `EG(stack_base)` / `EG(stack_limit)` and `zend_call_stack.h` (8.3) | `pt_abi.eg_stack_*`; before 8.3, `Abi.cpp`'s own pair, determined like 8.3 does, and the two inline helpers spelled out in `abi.h` |
| `zend_fcall_info_cache.closure` (8.3 appended it) | 8.3's layout declared over the older headers' struct (`support.h`, `abi.h`); the older engines use the first four members, the shared code never reads `closure` |
| class names of internal arginfo (`_ZEND_TYPE_LITERAL_NAME_BIT`, 8.3) | pinned in `abi.h`, turned into the plain name bit by `Abi.cpp` before 8.3 |
| `zend_zval_value_name()` (8.3; 8.2's type errors use `zend_zval_type_name()`) | `pt_abi.zval_value_name` |
| `#if PHP_VERSION_ID` gates | `PT_ABI_SINCE(version, then, otherwise)`, decided at run time |
| helpers re-spelled without changing behaviour (macro → inline function) | one definition pinned in `abi.h` ("codegen pins") |

The dependency runs one way: the extension calls the core (the functions
marked `PT_CORE_API` — the only symbols the core exports — and only
functions, since a Windows DLL can delay-load functions but not data); the
core never references the extension. The extension refuses a core of
another version (`pt_core_version()`): it registers nothing and reports
`core-mismatch`, which `TurboExtensionEnabler` rejects, so a torn update
runs PHPStan without the extension rather than mixing builds.

Loading: on Linux the extension records the core as a dependency found next
to itself (`DT_NEEDED` + `$ORIGIN`), on macOS likewise (`@loader_path`). On
Windows the dependency is delay-loaded: PHP loads extensions with plain
`LoadLibrary()`, which searches the application's directory, not the
extension's, so `get_module()` (`main.cpp`) loads the core by its full path
first and the delay-load helper finds it by name. One Linux core serves the
thread-safe and the non-thread-safe extension (the core never touches TSRM);
Windows needs two, since a DLL imports the engine from `php8.dll` or
`php8ts.dll` by name.

### Building the pair

```bash
cd turbo-ext
make SPLIT=1                 # phpstan_turbo_core.so + phpstan_turbo.so (loads the core next to it)
make SPLIT=1 pgo             # the same, profile-guided (what turbo-compile-core runs)
make SPLIT=1 extension       # only the extension, against a phpstan_turbo_core.so already here
make SPLIT=1 strip           # both; a plain `make strip` leaves the core untouched
```

A plain `make`, the phpize build (`config.m4`, what PIE drives) and
`configure --enable-phpstan-turbo` on Windows still build one
self-contained extension. On Windows, `configure --enable-phpstan-turbo=core`
builds the core DLL and its import library, and `=extension` the extension
against them (the import library expected in `turbo-ext/`).

In CI (`.github/workflows/phar.yml`), `turbo-compile-core*` build one core
per platform — profile-guided, against `php-version` of its matrix (Windows:
against 8.3 with VS16, the oldest toolset — 8.2's too — since PHP checks the toolset of
the DLL it loads, the extension, and a core linked with an older one runs on
any newer VC runtime) — and are what `turbo-origins` reuses from earlier
runs; `turbo-compile*` link each version's extension against them in a
minute and upload both, so every test job finds the core next to the
extension. `.github/scripts/turbo-dist-layout.sh` maps the artifacts onto
the layout above for the aggregate artifact and the commit job, and refuses
legs of one platform carrying different cores.

### The shared-core gate

```bash
SHARED_CORE_WORK_DIR=/tmp/shared-core turbo-ext/bin/shared-core/check-linux.sh
```

compiles every source against each supported version's headers (thread-safe
builds included) in the CI build images with clang, and fails on any
function, data section or relocation that differs from the build against the
version the cores are compiled against (8.5, `GATE_REFERENCE`) outside the
four version-specific objects. It is two halves: `fingerprint-linux.sh`
compiles for the versions in `GATE_VERSIONS` and writes a fingerprint per
version (`gate.py fingerprint`: a hash per function, data section and
relocation table), and `gate.py compare` compares them. In `phar.yml` the
first half runs as one `turbo-shared-core-gate` leg per version — a leg
reuses the fingerprint of an earlier run with identical build inputs, like a
core — and the second as `turbo-shared-core-gate-compare`. The commit job
does not wait for them: like the other test jobs, a failure is reported
loudly but does not stop publication. clang, not the GCC the Linux binaries ship with: the
property proven is the source's (does it mean the same against every header
set?), and GCC's register allocation drifts with incidental header spelling
(a `const` added to a helper's parameter) where clang's output does not.
CI runs it on x86_64; run it for the other architecture too when the result
matters (`GATE_PLATFORM=linux/amd64` on an arm64 machine, or the reverse) —
some differences only exist in one architecture's code, like an `int`
parameter that became `size_t`, which arm64 passes with the same
instruction and x86_64 does not.

A failure lists the functions. Find the cause with the tools next to it —
`gate-linux.sh` (any image/compiler, `GATE_VERSIONS`, `GATE_MAKE_ARGS`;
`PGO_FLAGS='-fno-inline -fno-ipa-sra -fno-ipa-cp -fno-partial-inlining'`
keeps each inline helper a function of its own, so the differing one shows
up by name), `compare-functions.py` (the per-function comparison
`gate.py` builds on, with a size summary per object), and their
macOS counterparts (`gate-mac.sh`, `compare-data-mac.sh`,
`link-split-mac.sh`, with Homebrew's `php@8.x`); `link-split-linux.sh` links
a core from one version's objects with every version's extension and runs
`tests/smoke.php` with each. Never accept a difference as "compiler noise"
without naming its mechanism: one such difference was a `zend_arg_info`
whose 8.6 stride crashed the 8.4-built core on 8.6. Windows-only header
branches are not covered by the gate (it compiles Linux headers); the
Windows legs' tests are their check.

### Supporting a new PHP version

1. **Gate it.** Build the new version's CI image (`.github/turbo-build`),
   add the version (and its `-zts` twin) to `VERSIONS` in
   `bin/shared-core/check-linux.sh` and `bin/shared-core/fingerprint-linux.sh`
   and to the `turbo-shared-core-gate` matrix in `phar.yml`, and run
   `check-linux.sh`. Every function it reports reads something that changed; for each,
   find what (diff the headers' struct layouts — `sizeof`/`offsetof` of the
   structs the function touches — macro values, enum numbering and
   function declarations between the old and the new version) and route it
   through `abi.h`/`Abi.cpp` by the table above: a new `PT_ABI_*` list
   entry, a `PT_CE`-style accessor, a pin, or a function pointer
   implemented in `Abi.cpp` with an `#if PHP_VERSION_ID` there. Repeat until
   it reports nothing.
2. **Check the exports.** A function the new version stops exporting makes
   the core fail to load on it, which the gate cannot see — the build legs
   and `link-split-linux.sh` load it on every version.
3. **Version-specific sources.** `Abi.cpp`, `Shadow.cpp`, `TrustedTypes.cpp`
   and `main.cpp` compile against the new headers like any extension: fix
   them with `#if PHP_VERSION_ID` as needed.
4. **CI.** In `.github/workflows/phar.yml`, add the version to the
   `php-version` lists of the thin legs (`turbo-compile`,
   `turbo-compile-musl-arm64`, `turbo-compile-windows` — with its `vs`
   infix and toolset in `include`) and of the jobs testing them
   (`turbo-differential`, `turbo-differential-musl`, `turbo-run`,
   `turbo-docker-run`), and give the new version a `gnu-php<minor>` image
   for the Linux gnu legs. Until the version has a stable Windows release,
   the Windows legs install its current prerelease by themselves (the
   `php-windows-prereleases` job reads windows.php.net's manifests), so
   there is no version to pin. The cores need no change: they keep compiling
   against the version they are pinned to (`php-version` in
   `turbo-compile-core`'s targets, `["8.3"]` with the vs16 toolset in
   `turbo-compile-core-windows`). Run the smoke test of every version
   against one core (`link-split-linux.sh`); in CI the
   `turbo-shared-core-gate` legs rerun step 1. When the version the cores
   are compiled against moves, move the reference with it (`GATE_REFERENCE`
   in `check-linux.sh`, the version passed to `gate.py compare` in
   `turbo-shared-core-gate-compare`).
5. **Dropping a version** is the reverse of step 4 plus
   `TURBO_RETIRED_BINARIES` for the files phpstan/phpstan should delete. If
   it is the version a core is pinned to, move that pin to a version still
   supported — on Windows to the oldest one, because a DLL linked with a
   newer toolset than the PHP loading it is refused (see
   `turbo-compile-windows`).
6. **If a minor breaks too much to route** — a hot-path structure such as
   `zval`, `zend_string` or `HashTable` changing layout — it gets a second
   core: build the platform's core per group of minors and pick it by
   version in `TurboExtensionSelector`. Nothing so far has needed it.

## Code style

The native sources are C++ that mirrors the PHP implementations they replace:
each shadowed class is a handle class in `namespace phpstanturbo` with the
twin's methods (see `src/TrinaryLogic.cpp` for the reference shape), built on
the zero-cost wrappers in `src/zv.h` — borrowed `zv::Ref` views, owned
move-only `zv::Val` RAII values, range-for HashTable iteration, `zv::Args`
argument packs for engine calls — and the shared bodies in `src/TypeTraits.h`
(a member the ports would otherwise repeat verbatim forwards there). The wrappers
compile to the same instructions as the raw zend macros (verified by
interleaved A/B benchmark), so readability costs nothing. Classes register
through the fluent builder in `src/reg.h`, which emits the raw zend
structures with raw handler pointers — no per-call trampoline or argument
boxing; each method's name, flags, signature and parameter-parsing glue live
together in one declaration. A method that only parses its parameters and
delegates them is declared by its handle member and parameter kinds
(`cls.method<&UnionType::accepts, zp::Obj, zp::Bool>(...)`) and gets a
generated handler; other glue parses with `zp::parse<...>()`. Both expand to
the engine's own `ZEND_PARSE_PARAMETERS` macros, so the handlers compile to
what the hand-written glue did.
Raw zend form remains where an abstraction would not be provably free —
always with a comment saying so.

## Linting and sanitizers

```bash
make lint-turbo      # clang-tidy over the hand-written sources
make sanitize-turbo  # the differential tests under UndefinedBehaviorSanitizer
```

`lint-turbo` runs clang-tidy with the curated check list in `.clang-tidy`; a
finding fails the target, and there is no baseline. The list is curated
because this is a Zend extension, and whole check families object to exactly
that: the path-sensitive analyzer reads every `zval` access as an
uninitialized union member (the type tag decides which member is live and it
does not model that), others object to the engine's `do {} while (0)` macros,
to `php.h` being an umbrella header, to the handler signatures' unused
parameters, or propose linkage changes that measurably change inlining here.
Every exclusion in `.clang-tidy` carries the number of findings it produced
when the list was calibrated, so a future reader can re-judge it. Generated
sources are not linted at all — a finding in `src/generated/*.h` or in the
parser's action tables could only be fixed in their generator.

`sanitize-turbo` rebuilds the extension with UndefinedBehaviorSanitizer and
runs the differential tests under it (`smoke`, `arena-smoke`,
`signature-parity`, `parser-corpus`). The sanitizer runtime is linked into
the `.so`, so an ordinary interpreter loads it — no debug or instrumented PHP
build is needed. It builds from clean and cleans up after itself, because
objects must never mix flags. This is the half that covers memory safety:
the static checks cannot see zval lifetimes, and the differential tests
already exercise the code paths that matter.

Both run in CI on every pull request (`.github/workflows/lint.yml`). The
clang-tidy version is pinned in one place, `CLANG_TIDY_VERSION` in the
Makefile: CI reads that number and installs exactly it (Ubuntu ships an
older one, so it comes from apt.llvm.org), and `make lint-turbo` refuses to
run with a different major. Without that, a green CI run and a green local
run would be two different check lists rather than the same evidence, and
the finding counts recorded in `.clang-tidy` would hold for neither. Install
the pinned version with `brew install llvm@<version>` or from apt.llvm.org.
Moving the pin is deliberate: raise the number, re-run, and fix or exclude
what the new checks report — with its count and reason, the way the existing
entries carry theirs.

## Testing

```bash
# differential test of the native classes vs the PHP implementations (they
# are declared as PHPStanTurbo\* next to the twins, see tests/activate-prefixed.php)
php -d extension=$(pwd)/phpstan_turbo.so tests/smoke.php

# PHPStan's own test suite with the extension loaded
php vendor/bin/phpunit ...

# output identity (clear the result cache between runs!)
# nothing disables a loaded extension, so keep it out of php.ini and load it
# per run instead — through PHP_INI_SCAN_DIR, not -d extension=: PHPStan's
# OPcache restart re-executes the process and drops command-line -d flags
mkdir -p /tmp/turbo-ini && echo "extension=$(pwd)/phpstan_turbo.so" > /tmp/turbo-ini/turbo.ini
PHP_INI_SCAN_DIR=":/tmp/turbo-ini" php ../bin/phpstan analyse ... --error-format=raw   # with
php ../bin/phpstan analyse ... --error-format=raw                                     # without
```
