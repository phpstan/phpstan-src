# phpstan_turbo — native acceleration extension for PHPStan

Native PHP extension that reimplements PHPStan's hottest
code paths in C++. It is entirely optional: PHPStan behaves identically
without it, just slower. With the extension loaded, analysis output is
bit-for-bit identical — only faster (~25% on PHPStan's own single-threaded
self-analysis).

<img width="512" height="512" alt="PHPStan Turbo" src="https://github.com/user-attachments/assets/3538e70b-4a44-4ac4-95ba-e0b4eb24f13e" />


## Installation

**Most users do not need to install anything.** The
[phpstan/phpstan](https://github.com/phpstan/phpstan) Composer package ships
prebuilt binaries for the most common platforms — Linux (glibc and musl,
x86_64 and arm64), macOS (arm64), and Windows (x86_64), for PHP 8.3 and newer — and
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
- There is no switch to turn it off — an installed, matching extension is
  always used.
- The extension only activates when its version matches the one your PHPStan
  release expects — on a mismatch PHPStan prints a note and runs without it,
  so an outdated extension can never affect results, only speed.

# Developer notes

A plain Zend C++ extension, no framework dependencies.

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

Two more `Runtime` entry points serve fork mode (see `ForkParallelChecker`):
`enablePharForkGuard()` gives each pcntl_fork()ed worker a private cursor on
the running phar's fd (`PharForkGuard.cpp`), and `exitImmediately()` —
`_exit()` with the engine's exit status — is how `ForkedChildTerminator` ends
a forked worker. A forked child inherits every loaded extension but none of
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

The extension is version-pinned (`TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION`);
a mismatched extension never gets `activateShadowing()` called and the PHP
implementations load as usual (`Runtime`'s fork guard and trusted-types pass
do not depend on it). There is no runtime kill switch — the only way to run
without it is not to load it.

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

or, from the repository root, `make build-turbo` — the same build, run in
parallel over the machine's cores and followed by a check that the binary's
baked version is the one `TurboExtensionEnabler` activates (a build made
between a `turbo-ext/src` commit and its `make bump-turbo` loads but stays
inactive).

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
against 8.3 with VS16, the oldest toolset, since PHP checks the toolset of
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
8.6 included) in the CI build images with clang, and fails on any function,
data section or relocation that differs outside the four version-specific
objects. It runs as `turbo-shared-core-gate` in `phar.yml`, and the commit
job waits for it. clang, not the GCC the Linux binaries ship with: the
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
up by name), `compare-functions.py`, `compare-data-linux.sh`, and their
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
   add the version to `VERSIONS` in `bin/shared-core/check-linux.sh` and run
   it. Every function it reports reads something that changed; for each,
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
   for the Linux gnu legs. The cores need no change: they keep compiling
   against the version they are pinned to (`php-version` in
   `turbo-compile-core`'s targets, `["8.3"]` with the vs16 toolset in
   `turbo-compile-core-windows`). Run the smoke test of every version
   against one core (`link-split-linux.sh`); in CI the
   `turbo-shared-core-gate` job reruns step 1.
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

### Build flags: hardening and size

`make` applies protections by default, each *probed* against the compiler in
use rather than assumed, because the targets disagree: GCC 11.4 (the CI
floor) rejects `-ftrivial-auto-var-init`, `-fcf-protection` is x86-only, and
`-fstack-clash-protection` is accepted but silently unused by Apple clang on
arm64. What survives the probe on Linux is stack canaries, clash protection,
`_FORTIFY_SOURCE=3`, register clearing on return, array-bounds checks that
trap without linking a sanitizer runtime, and a GOT made read-only before
control is handed over. `make HARDENING_FLAGS=` builds without them.

Every flag was measured before adoption, on interleaved A/B pairs judged on
user CPU: the protections together came to −0.16% (t=−0.70), and the size
flags `-fvisibility=hidden` and `-fno-exceptions -fno-rtti` to +0.20% and
+0.05% — all inside the noise floor, while the shipped `.so` shrank ~26%.

Those timings are from macOS, where `-fvisibility=hidden` changes no machine
code at all: Mach-O's two-level namespace already prevents interposition, so
only the symbol table shrinks. On ELF it is far from cosmetic — measured in
the CI image, it removes 8,866 of 9,907 dynamic symbols and **18.9% of
`__TEXT`**, because a default-visibility symbol can be interposed at load
time and therefore neither inlined nor garbage-collected. The Linux binaries
are where that matters, and where it has not been timed.

`-fno-exceptions -fno-rtti` binds new code: a `throw` or a `dynamic_cast`
becomes a compile error. That matches the style rules above — the engine
unwinds with longjmp, and the ports neither throw nor use RTTI — but it is a
constraint, not merely an optimisation.

Measured and **not** adopted, so they are not re-tried: thin LTO (+1.76%
slower, t=+3.44 — it inlines across translation units and loses more in
locality than it gains), `-O3` (a wash at +4.63% code), PGO (no longer shows
the benefit it was originally landed on, even measured on its own training
corpus), and `-fstrict-flex-arrays=3`, which traps on ordinary string and
property access because the engine's public structures use the C struct-hack
(`zend_string.val[1]`, `zend_object.properties_table[1]` behind
`OBJ_PROP_NUM`) — all 29 violation sites in this codebase were that macro
expanding, none of them fixable here.

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
  a native `ExpressionResultStorage` (the per-expression
  result table; its `SplObjectStorage` copies and inserts allocate
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

## History

- The original proof of concept used Zephir (removed).
- The first full implementation was hand-written C (`phpize`); it is preserved
  on the `turbo-c-extension` branch together with the matching PHPStan
  sources, and this C++ version is its port.
