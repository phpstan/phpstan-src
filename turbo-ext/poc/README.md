# Proof-of-concept patches for vendored packages

`vendor/` is git-ignored, so a PHP-side fix in a vendored package cannot be
committed directly. Each patch here is the `git diff` of the original vendored
file against the edited one (paths relative to the repository root, apply with
`git apply turbo-ext/poc/<file>.patch`) and records its measured effect on the
PHPStan self-analysis benchmark, so it can be reviewed and contributed upstream.

- `better-reflection-reflection-memo.patch` (ondrejmirtes/better-reflection):
  memoizes `ReflectionFunctionAbstract::getName()` (4.8M calls per
  self-analysis run recomputed namespace + short name; the memo is reset by
  `ReflectionMethod::withImplementingClass()` since the alias name changes),
  short-circuits `getAttributesByName()` / `ReflectionAttributeHelper::filterAttributesByName()`
  on the common attribute-less case (no closure + `array_filter` per call), and
  reads `ReflectionClass::$cachedMethods/$cachedConstants/$cachedProperties`
  before constructing the `AlreadyVisitedClasses` guard in `getMethod()`,
  `getMethods()`, `getConstants()` and `getProperties()` (1.6M throw-away
  objects per run; the guard mutates in place, so it cannot be shared).
  Measured effect on the self-analysis A/B (src/Analyser + src/Rules + src/Type,
  extension on, 6 interleaved pairs): -1.6% user CPU (44.29s -> 43.57s)
  together with the `PhpMethodReflection::getName()` memo in src/.
