# phpstan_turbo — native acceleration extension for PHPStan

Native PHP extension that reimplements PHPStan's hottest
code paths in C++. It is entirely optional: PHPStan behaves identically
without it, just slower. With the extension loaded, analysis output is
bit-for-bit identical — only faster (~25% on PHPStan's own single-threaded
self-analysis).

## Installation

**Most users do not need to install anything.** The
[phpstan/phpstan](https://github.com/phpstan/phpstan) Composer package ships
prebuilt binaries for the most common platforms — Linux (glibc and musl,
x86_64 and arm64), macOS, and Windows (x86_64), for PHP 8.3 and newer — and
PHPStan automatically loads the one matching your runtime into its worker
processes.

Installing the extension with [PIE](https://github.com/php/pie) is only
needed when you download and run `phpstan.phar` manually, outside of
Composer (the prebuilt binaries ship next to the phar in the Composer
package, not inside it):

```bash
pie install phpstan/turbo
```

Useful to know:

- `vendor/bin/phpstan diagnose` reports the extension's status.
- `PHPSTAN_TURBO=0` turns it off.
- The extension only activates when its version matches the one your PHPStan
  release expects — on a mismatch PHPStan prints a note and runs without it,
  so an outdated extension can never affect results, only speed.

# Developer notes

A plain Zend C++ extension, no framework dependencies.

## How it works — the stub-shadowing pattern

Every shadowed piece of PHP code follows the same three steps:

1. The code is extracted into a dedicated PHP class (plain PHP, this is what
   runs when the extension is absent) — e.g. `PHPStan\Analyser\ScopeOps`,
   `PHPStan\Analyser\ExprHandlerDispatch`, `PHPStan\Node\NodeScanner`, or an
   existing value class like `PHPStan\TrinaryLogic`.
2. The extension implements the same class natively in the `PHPStanTurbo`
   namespace (one class per file in `src/`).
3. The PHP class is marked with the `#[ShadowedByTurboExtension]` attribute
   naming its native counterpart. On every `composer dump-autoload`,
   `build/generate-turbo-stubs.php` collects the attributes with runtime
   reflection and generates `vendor/turbo-stubs.php` — an empty stub shell
   per class, `final class Foo extends \PHPStanTurbo\Foo {}` (shadowed
   classes living in vendor/ cannot carry the attribute and are hardcoded in
   `build/TurboAttributeCollector.php`; currently `PhpParser\NodeTraverser`).
   When the extension
   is enabled, `PHPStan\Turbo\TurboExtensionEnabler` `require`s that file
   *before* the Composer autoloader registers. All PHP code keeps calling
   the original class name, transparently getting the native implementation
   via inheritance.

Class names the native code references at run time come through
`PHPStanTurbo\Runtime::configure()`: `TurboExtensionEnabler` feeds it the
generated `vendor/turbo-class-map.php`, derived from the
`#[ReferencedByTurboExtension]` attributes (vendored PhpParser classes are
hardcoded in the collector), so a renamed class updates the map on the next
autoloader dump. Because instances must satisfy the original type hints, the
native code never instantiates its own classes directly: a referenced class
that is itself shadowed resolves to its stub subclass, and
factories/singletons instantiate that.

The extension is version-pinned (`TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION`);
a mismatched extension is ignored. `PHPSTAN_TURBO=0` disables it explicitly.

The version is the short SHA of the last commit that touched `turbo-ext/src/`.
The binary's (actual) version is baked in at build time — the Makefile
computes it from git over the same path
— so only the expected side, `TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION`,
is maintained (by `make bump-turbo`, which edits and commits it). After
changing the native side, verify the implementations still match and run
`make bump-turbo` (recomputed after the change lands on the target branch —
a pull request commit gets a new SHA when rebased, and rerunning refreshes
an unpushed bump commit in place); the
phar.yml `turbo-version` job enforces the SHA and the compile job verifies
the built binary reports what the enabler expects. A PHP-twin-only edit does
not move the version — keeping the pair in sync there is on the review and
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
`build/generate-turbo-stubs.php` derives the same map into
`vendor/turbo-shadowed-classes.json` on every `composer dump-autoload` (for
the runtime consumers: the enabler's reflection sources, the phar's preload
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
  class-defining `.cpp` corresponds 1:1 to the attributes, and re-derives the
  three generated `vendor/turbo-*` files from the attributes and
  byte-compares them, so a stale autoloader dump (or a hand edit of a
  generated file) fails.
- **CI signature parity** — `php tests/signature-parity.php` (compile job,
  needs the built extension and vendor/) reflects each native class against
  its PHP twin: visibility, staticness, parameter
  names/optionality/by-ref/variadic, and
  types. It also verifies each manifest entry points at the file the class
  actually lives in (and that the `vendored` flag matches), so a stale
  autoloader dump fails instead of silently comparing against the wrong
  source. Native arginfo may erase types to none/`object` (baking class
  names into the binary would couple it to userland names, and engine-level
  type checks cost per call), but what it does declare must match, and
  parameter names must match exactly — a renamed parameter would break named
  arguments only in turbo mode.
- **CI version coupling** — the version job enforces the expected-version
  constant against `turbo-ext/src/` history (see above), so a native-side
  edit cannot ship without the explicit bump attesting the pair still
  matches.

Semantic equivalence is still proven by the differential smoke test and by
running the full test suite with the extension loaded — the manifest checks
guard structure and force the version bump ritual, not behavior. The smoke
test also guards two structural invariants at the real runtime: the
generated class map must cover the native class-reference table exactly
(`Runtime::classRefs()`), and every shadowed class must have registered
differential coverage (in `$covered` there, or via its dedicated script).

## The native parser engine

`src/parser/` reimplements php-parser 5.8.0's LALR engine and node building
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
collected errors, and identical token streams. It runs in CI on every build.

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
   until byte-identical over the whole corpus. New PHP syntax is only covered
   once fixtures using it exist in the repo — PHPStan's own test data for the
   new syntax provides them; make sure they land before or with the bump.
   Then the full test suite and `make phpstan` with the extension loaded,
   and `tests/parser-bench.php` to confirm the speedup held.
4. **Bump both pins**: `SUPPORTED_PHP_PARSER_VERSION` in the workflow, and —
   since `src/parser/` changed — the extension version
   (`TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION`) per
   the usual ritual. The version gate is also what protects users: a phar
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

The only requirements are a C++17 compiler and `php-config` on PATH (or
passed as `make PHP_CONFIG=...`). Both NTS and ZTS interpreters are
supported — the build inherits thread-safety from the `php-config` it is
pointed at (ZTS hosts like PMMP's bundled PHP get a matching build; PHPStan
itself only ever runs the native code single-threaded).

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

## Enabling

Add to `php.ini` (recommended — parallel worker processes inherit it):

```ini
extension=/absolute/path/to/phpstan-src/turbo-ext/phpstan_turbo.so
```

## Code style

The native sources are C++ that mirrors the PHP implementations they replace:
each shadowed class is a handle class in `namespace phpstanturbo` with the
twin's methods (see `src/TrinaryLogic.cpp` for the reference shape), built on
the zero-cost wrappers in `src/zv.h` — borrowed `zv::Ref` views, owned
move-only `zv::Val` RAII values, range-for HashTable iteration. The wrappers
compile to the same instructions as the raw zend macros (verified by
interleaved A/B benchmark), so readability costs nothing. Classes register
through the fluent builder in `src/reg.h`, which emits the raw zend
structures with raw handler pointers — no per-call trampoline or argument
boxing; each method's name, flags, signature and parameter-parsing glue live
together in one declaration.
Raw zend form remains where an abstraction would not be provably free —
always with a comment saying so.

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
6. **Never shadow a DI-service class.** Nette's `getByType()` normalizes
   requested types through reflection to the real class name and breaks
   containers cached in the other mode.
7. **Every port must prove itself**: interleaved A/B benchmark on a long run
   (user CPU, result cache cleared) plus a byte-identical output diff. Ports
   measuring ≤0.5% get reverted — the failure mode is silent no-gain, and
   unproven native code is pure maintenance debt.

## Performance frontiers (July 2026)

Status quo, measured on PHPStan's own single-threaded self-analysis of `src/`
(interleaved A/B, user CPU): **59.6s with the extension vs 77.3s without —
a 23% gain**. The remaining cost is structural, not hotspot-shaped: SPX
counts ~535M userland calls spread over 20K functions, the top 120 functions
by exclusive time explain only ~15% of the run, and an on-CPU sample
attributes 42.7% to VM call mechanics (frame setup, argument passing,
return-type checks), 23.3% to other VM opcodes, ~8% each to memory/GC and
syscalls — and only 2.3% to this extension's own code. Every further tier
therefore means absorbing whole call subtrees, not porting leaf bodies.

What each gain level over the no-extension baseline requires:

- **30%** (−5.5s) — reachable with targeted ports and known PHP-side fixes:
  a native `ExpressionResultStorage` (the fiber bridge's per-expression
  before-scope table; its `SplObjectStorage` copies and inserts allocate
  ~3GB per run), the `CachedParser` content-key re-read fix (72K full-file
  reads per run just to compute LRU keys), `getName()`/return-type memos in
  better-reflection (5.3M calls survive), member-lookup pricing
  (`ObjectType::getMethod` + `getMethodReflection` + dynamic-extension
  registry sweeps, ~1.9M calls), and the FileTypeMapper cache hydration
  format.
- **50%** (−20.9s) — requires the **native expression engine**: the
  `NodeScopeResolver` expression walk, `ExprHandler` dispatch loop,
  `ExpressionResult`/holder plumbing and scope-table mutation move into C++,
  crossing back to PHP only for `Type`-level operations and third-party
  extensions. `MutatingScope::getType` alone is ~22% of the run inclusive.
  Estimated 150–250M absorbed frames ≈ 10–15s; a quarter-rewrite, to be
  approached one handler chain at a time.
- **70%** (−36.4s) — "everything PHPStan-owned is native": on top of the
  expression engine, a native `Type` kernel (`isSuperTypeOf`/`accepts`/
  union/intersection graphs operating natively, PHP `Type` objects as
  views), the statement-level walk, and native reflection-data storage
  (extending the arena). The floor left in PHP — rule bodies, vendor
  better-reflection, phpdoc-parser, third-party plugins — is an estimated
  15–22s, so this target sits *at* the boundary of what a hybrid can do.
- **90%** (−51.9s) — below any architecture that keeps PHP rules, dynamic
  extensions and vendor parsers. This is not a port but a ground-up native
  analyzer; extension-ecosystem compatibility is the casualty. The realistic
  ceiling for the hybrid approach is ~60–75%.

(Benchmarks include the ShipMonk dead-code plugin, ~8% of the self-analysis
run — third-party PHP that no port removes.)

## Testing

```bash
# differential test of the native classes vs the PHP implementations
php -d extension=$(pwd)/phpstan_turbo.so tests/smoke.php

# PHPStan's own test suite with the extension loaded
php vendor/bin/phpunit ...

# output identity (clear the result cache between runs!)
bin/phpstan analyse ... --error-format=raw   # with extension
PHPSTAN_TURBO=0 bin/phpstan analyse ...      # without
```

## History

- The original proof of concept used Zephir (removed).
- The first full implementation was hand-written C (`phpize`); it is preserved
  on the `turbo-c-extension` branch together with the matching PHPStan
  sources, and this C++ version is its port.
