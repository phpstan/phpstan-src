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

## Build and verify commands

```bash
cd turbo-ext
make WARN_FLAGS="-Wall -Wextra -Werror -Wno-assume -Wno-unused-parameter -Wno-unicode"
php -d extension=$(pwd)/phpstan_turbo.so tests/smoke.php   # must print ALL OK
```

The three `-Wno-` exemptions are for zend macro expansions only (documented in
`.github/workflows/phar.yml`); new warnings in our code are fixed, not
exempted. Zend header noise is handled by the pragma guards in `support.h`.

## Benchmark protocol

Judge **user CPU**, never wall clock, on interleaved A/B pairs (the machine
drifts ±1s thermally — never run all A then all B):

```bash
bin/phpstan clear-result-cache -c build/phpstan.neon -q
/usr/bin/time php -d memory_limit=6G -d extension=$PWD/turbo-ext/phpstan_turbo.so bin/phpstan analyse -c build/phpstan.neon --debug -q src
# baseline runs: drop the -d extension= flag. Nothing disables a loaded
# extension, so keep it out of php.ini and load it per run instead
```

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
