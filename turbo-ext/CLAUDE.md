# turbo-ext — instructions for working on the native extension

Read `README.md` first: the stub-shadowing pattern, the sync machinery, and
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
2. **Check it is not a DI service** (rule 6 in README — this is fatal and
   was measured at 617 broken tests). Value classes and manually-instantiated
   classes are safe.
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
   in `reg.h` — one `cls.method("name", flags, requiredArgs, { args... },
   lambda)` declaration per method, where the lambda body is only
   ZEND_PARSE_PARAMETERS glue + one delegation line (see TrinaryLogic.cpp).
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
   `Runtime::classRefs()`). A referenced class that is itself shadowed is one
   the native code *instantiates*: its table entry gets no default name, and
   the resolved name is the stub subclass, so created instances satisfy the
   original type hints.
5. **Mark the class** with `#[ShadowedByTurboExtension(turboClass:
   'PHPStanTurbo\Foo', implementation: __DIR__ . '/../turbo-ext/src/Foo.cpp')]`
   and run `composer dump-autoload` — `build/generate-turbo-stubs.php`
   regenerates the stub shells in `vendor/turbo-stubs.php`, the manifest of
   shadowed pairs in `vendor/turbo-shadowed-classes.json` and the class map
   in `vendor/turbo-class-map.php` from the attributes (shadowed classes
   living in vendor/ cannot carry the attribute and are hardcoded in
   `build/TurboAttributeCollector.php`).
6. **Check method parity**: `php bin/side-by-side.php` must pass (it also
   re-derives the generated `vendor/turbo-*` files from the attributes and
   byte-compares them, so a stale autoloader dump fails there).
7. **Extend `tests/smoke.php`** with differential coverage (native result
   must equal the PHP implementation's result on the same inputs) and
   register the class in `$covered` next to its checks — the completeness
   check at the end fails for any shadowed class with no registered
   coverage.
8. **Verify**: strict build, smoke test,
   `php -d extension=$PWD/turbo-ext/phpstan_turbo.so turbo-ext/tests/signature-parity.php`
   (arginfo parameter names must match the PHP twin exactly — named arguments),
   full `make tests` with the extension loaded, and byte-identical analysis
   output with the extension on vs `PHPSTAN_TURBO=0`. Anything touching
   `src/parser/` additionally runs `turbo-ext/tests/parser-corpus.php`
   (byte-identical ASTs over the whole corpus); a php-parser version bump in
   composer.lock requires the same.
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
/usr/bin/time php -d memory_limit=6G bin/phpstan analyse -c build/phpstan.neon --debug -q src
# baseline runs: prefix with PHPSTAN_TURBO=0 (the ini loads the extension globally)
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
- Fibers can suspend from internal frames — native code may be re-entered.
- Cloned scopes must reset per-instance memo properties to constructor
  defaults, and native factories must instantiate the `…Impl` stub classes.

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
- The corpus differential (`tests/parser-corpus.php`) is the acceptance bar
  — byte-identical serialized ASTs, errors, and token streams — and it only
  proves what the corpus contains: new syntax needs fixtures in the repo
  before the check means anything for it.
- Finish with both pins: `SUPPORTED_PHP_PARSER_VERSION` in
  `.github/workflows/phar.yml` plus the extension version bump in
  `TurboExtensionEnabler` (`src/parser/` changed).

## PHP-side constraints

- No `match` expressions in `src/` (the PHP downgrade tooling cannot handle
  them) — use `switch` or if/else.
- The PHP twin stays the reference implementation: fix behavior there first,
  port second, and keep both sides structurally parallel so they stay
  reviewable next to each other.
