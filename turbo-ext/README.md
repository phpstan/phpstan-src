# phpstan_turbo — native acceleration extension for PHPStan

**Experimental.** A C++ extension built with
[PHP-CPP](https://github.com/CopernicaMarketingSoftware/PHP-CPP) that
reimplements PHPStan's hottest code paths natively. It is entirely optional:
PHPStan behaves identically without it, just slower. With the extension
loaded, analysis output is bit-for-bit identical — only faster (~25% on
PHPStan's own single-threaded self-analysis).

## How it works — the stub-shadowing pattern

Every shadowed piece of PHP code follows the same three steps:

1. The code is extracted into a dedicated PHP class (plain PHP, this is what
   runs when the extension is absent) — e.g. `PHPStan\Analyser\ScopeOps`,
   `PHPStan\Analyser\ExprHandlerDispatch`, `PHPStan\Node\NodeScanner`, or an
   existing value class like `PHPStan\TrinaryLogic`.
2. The extension implements the same class natively in the `PHPStanTurbo`
   namespace (one class per file in `src/`).
3. When the extension is enabled, `PHPStan\Turbo\TurboExtensionEnabler`
   `require`s an empty stub from `stubs/` — `final class Foo extends
   \PHPStanTurbo\Foo {}` — *before* the Composer autoloader registers.
   All PHP code keeps calling the original class name, transparently getting
   the native implementation via inheritance.

Because instances must satisfy the original type hints, the native code never
instantiates its own classes directly: `TurboExtensionEnabler` passes the stub
class names (`…Impl` entries) to `PHPStanTurbo\Runtime::configure()`, and
factories/singletons instantiate those subclasses.

The extension is version-pinned (`TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION`);
a mismatched extension is ignored. `PHPSTAN_TURBO=0` disables it explicitly.

The version is the short SHA of the last commit that touched `turbo-ext/src/`
or one of the shadowed PHP classes. The binary's (actual) version is baked in
at build time — the Makefile computes it from git over that same watched set
— so only the expected side, `TurboExtensionEnabler::EXPECTED_EXTENSION_VERSION`,
is declared by hand. After changing either side of a shadowed pair, verify the
implementations still match and add a follow-up commit updating the constant
to the short SHA of the changing commit; the `turbo-ext.yml` version job
enforces the SHA and the compile job verifies the built binary reports what
the enabler expects. Builds outside a git checkout bake the version "dev",
which the enabler rejects — the extension then simply stays inactive.

## Keeping the two implementations in sync

`shadowed-classes.json` is the manifest of shadowed pairs: each PHP class and
the C++ file implementing it natively. It is not edited by hand:
`php bin/side-by-side.php --update-manifest` regenerates it from ground truth
(each stub in `stubs/` names the shadowed class, the Composer autoloader
locates its PHP implementation, the same-named `.cpp` is the native side).
Run it after a native port is brought on par with the PHP implementation and
the behaviour is verified by tests — then the CI checks below hold the
committed manifest against both sides. It drives three things:

- **CI method parity** — `php bin/side-by-side.php --check` (part of the
  version job) verifies every public method of each PHP class has a
  `PHP_METHOD` counterpart in the C++ file and every `PHP_METHOD` corresponds
  to a method of the PHP class. Non-public PHP methods may stay PHP-only
  (native code inlines them or uses C helpers). It also verifies the manifest
  is complete — stubs, the enabler's `require_once` list and the per-class
  `.cpp` files must all correspond 1:1 to manifest entries — and that stubs
  are empty shells (a member declared in a stub would exist only when the
  extension is loaded).
- **CI signature parity** — `php tests/signature-parity.php` (compile job,
  needs the built extension) reflects each native class against its PHP twin:
  visibility, staticness, parameter names/optionality/by-ref/variadic, and
  types. It also verifies each manifest entry points at the file the class
  actually lives in (and that the `vendored` flag matches), so a stale
  manifest fails instead of silently comparing against the wrong source. Native arginfo may erase types to none/`object` (it cannot bake
  class names of phar-prefixed namespaces into the binary, and engine-level
  type checks cost per call), but what it does declare must match, and
  parameter names must match exactly — a renamed parameter would break named
  arguments only in turbo mode.
- **CI version coupling** — the version job watches the manifest's PHP files
  in addition to `turbo-ext/src/` (see above), so a PHP-side edit cannot
  silently diverge from the native port. The vendored `PhpParser\NodeTraverser`
  pair is excluded from the git check; it is pinned by `composer.lock`.
- **Side-by-side review** — `php bin/side-by-side.php` renders
  `side-by-side.html` (gitignored), pairing each method's PHP and C++
  implementations next to each other for maintenance review.

Semantic equivalence is still proven by the differential smoke test and by
running the full test suite with the extension loaded — the manifest checks
guard structure and force the version bump ritual, not behavior.

## The native parser engine

`src/parser/` reimplements php-parser 5.8.0's LALR engine and node building
(`PhpParser\ParserAbstract` + the generated `Parser\Php8`), shadowed through
the `PHPStan\Parser\ParserRunner` seam. The parsing tables are read at run
time from the first `Php8` parser object seen — they are generated data, so
nothing is duplicated — and node classes resolve relative to the parser's
namespace, which keeps the scoped phar working. Tokenization stays in PHP's
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
(`SUPPORTED_PHP_PARSER_VERSION` in `.github/workflows/turbo-ext.yml`), so a
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

## Building

```bash
cd turbo-ext
git clone https://github.com/CopernicaMarketingSoftware/PHP-CPP.git  # if absent
ln -sfn include PHP-CPP/phpcpp
make phpcpp   # builds the bundled PHP-CPP (static lib)
make          # builds phpstan_turbo.so
```

PHP-CPP carries one local patch, `patches/php-cpp-base-count-int64.patch`
(on macOS/arm64 `long` matches no `Php::Value` constructor unambiguously).
`make phpcpp` applies it automatically, and the CI workflow applies the same
file — the patch lives in exactly one place.

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
through the fluent builder in `src/reg.h` — PHP-CPP's `extension.add()` look,
but emitting the raw zend structures with raw handler pointers, so there is
no per-call trampoline or `Php::Parameters` boxing; each method's name,
flags, signature and parameter-parsing glue live together in one declaration.
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
   zvals/hashtables in place. This is also why the hot classes are registered
   with the raw Zend API inside PHP-CPP's `onStartup` rather than through
   PHP-CPP's call trampolines: `Php::Parameters` allocates a vector of
   `Php::Value` per call, which is exactly the per-element boundary cost these
   rules forbid. PHP-CPP hosts the extension lifecycle and the cold-path
   `Runtime` class.
6. **Never shadow a DI-service class.** Nette's `getByType()` normalizes
   requested types through reflection to the real class name and breaks
   containers cached in the other mode.
7. **Every port must prove itself**: interleaved A/B benchmark on a long run
   (user CPU, result cache cleared) plus a byte-identical output diff. Ports
   measuring ≤0.5% get reverted — the failure mode is silent no-gain, and
   unproven native code is pure maintenance debt.

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
