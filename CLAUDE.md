# PHPStan - PHP Static Analysis Tool

PHPStan finds bugs in PHP code without running it. It analyses the entire codebase and reports type errors, undefined methods/properties/variables, dead code, incorrect PHPDoc types, and many other issues. It understands PHP's type system deeply, including generics, union/intersection types, literal types, conditional return types, and template types expressed through PHPDocs.

## Key concepts

- **Rule levels 0-10**: Incremental adoption from basic checks (level 0) to strict mixed type enforcement (level 10). Levels are cumulative. Level 0 covers unknown classes/functions/methods and undefined variables. Level 5 checks argument types. Level 6 requires typehints. Level 9 enforces explicit `mixed`. Level 10 enforces implicit `mixed`.
- **Baseline**: Allows adopting higher rule levels by recording existing errors in a baseline file, so only new errors are reported.
- **Bleeding edge**: Preview of next major version features, shipped in current stable release via `bleedingEdge.neon`.
- **Result cache**: PHPStan caches analysis results and only re-analyses changed files and their dependents.
- **Parallel analysis**: Files are analysed in parallel across multiple child processes using React PHP.
- **Configuration**: NEON format (Nette configuration). Main config is `phpstan.neon`, level configs in `conf/config.level*.neon`, services in `conf/services.neon`.

## Running PHPStan

```bash
# Analyse with a specific level
vendor/bin/phpstan analyse -l 8 src tests

# Clear result cache
vendor/bin/phpstan clear-result-cache

# Generate baseline
vendor/bin/phpstan analyse --generate-baseline

# Debug mode (shows which files are being analysed)
vendor/bin/phpstan analyse --debug
```

## Running tests

```bash
make tests
```

Rules are tested using `PHPStan\Testing\RuleTestCase`, type extensions with `PHPStan\Testing\TypeInferenceTestCase`.

## Architecture

The codebase lives under `src/` with PSR-4 autoloading mapping `PHPStan\` to `src/`. Key architectural components:

### PHPStan\Analyser - Core analysis engine

The analysis pipeline: `Analyser` orchestrates `FileAnalyser`, which uses `NodeScopeResolver` to walk the AST and invoke registered `Rule` implementations.

**`NodeScopeResolver`** (`src/Analyser/NodeScopeResolver.php`) - The central engine of PHPStan. It traverses the AST (from nikic/php-parser) and maintains a `MutatingScope` that gets updated at each node. It handles:
- Control flow analysis (if/else, switch, try/catch, loops, match)
- Variable assignments and type narrowing
- Function/method call resolution
- Closure and arrow function scoping
- Type narrowing from conditions (`instanceof`, `is_*()`, `===`, etc.)
- PHPDoc type resolution and `@var`/`@param`/`@return` processing
- Invoking registered Rules and Collectors at each AST node

**`MutatingScope`** (`src/Analyser/MutatingScope.php`) - Holds the current state of analysis after each AST node. It tracks:
- Variable types (assigned, possibly-defined, narrowed)
- Current context: namespace, class, method/function, trait, anonymous function
- Property types and initialization state
- Constant values
- Expression types via `getType(Expr $expr): Type`
- Native types vs PHPDoc types

The `Scope` interface (`src/Analyser/Scope.php`) is the public API that rules and extensions use. `MutatingScope` is the internal implementation.

**`TypeSpecifier`** (`src/Analyser/TypeSpecifier.php`) - Narrows types based on conditions. When NodeScopeResolver processes an `if ($x instanceof Foo)`, TypeSpecifier determines that `$x` is `Foo` in the truthy branch and `not Foo` in the falsy branch. It uses `TypeSpecifierContext` (truthy/falsey/true/false/null) to track which branch is being processed. Extensible via `FunctionTypeSpecifyingExtension`, `MethodTypeSpecifyingExtension`, and `StaticMethodTypeSpecifyingExtension`.

**`ExpressionTypeHolder`** (`src/Analyser/ExpressionTypeHolder.php`) - Stores an expression's type together with a `TrinaryLogic` certainty (Yes = definitely this type, Maybe = possibly this type). This is the building block of how `MutatingScope` tracks variable types - it maps expression keys to `ExpressionTypeHolder` instances.

**`ConstantResolver`** (`src/Analyser/ConstantResolver.php`) - Resolves PHP constant names to their types. Handles 150+ predefined PHP constants with PHP-version-aware types (e.g. different return types based on `PHP_VERSION_ID`). Also resolves user-configured constant type mappings.

**`InitializerExprTypeResolver`** (`src/Reflection/InitializerExprTypeResolver.php`) - Resolves types of constant expressions and initializers (default parameter values, property defaults, constant values). Handles arithmetic, casts, function calls, and binary operations in constant contexts where a full Scope is not available.

### Analysis pipeline in detail

1. `AnalyseCommand` parses CLI arguments, builds the DI container
2. `Analyser` receives the list of files, sets up `NodeScopeResolver` with the full file list
3. For parallel runs, `ParallelAnalyser` distributes files across worker processes via TCP using NDJSON protocol (React event loop). Default: up to 32 processes, 20 files per job, 600s timeout.
4. Each worker runs `FileAnalyser::analyseFile()` which parses a file to AST, then calls `NodeScopeResolver::processStmtNodes()`
5. `NodeScopeResolver` walks the AST depth-first. At each node, it updates `MutatingScope` and invokes all registered `Rule` and `Collector` callbacks for that node type
6. Results (errors, dependencies, collected data) flow back to the main process
7. After all files are processed, `CollectedDataNode` rules run with the aggregated collector data
8. `ResultCacheManager` saves results keyed by file SHA256 hashes and a dependency graph for incremental re-analysis

### Result cache (`src/Analyser/ResultCache/`)

`ResultCacheManager` enables incremental analysis. It tracks:
- SHA256 hashes of all analysed files
- Dependency graph between files (so changing one file re-analyses its dependents)
- Exported nodes (class/function signatures) to detect API changes
- PHP version, loaded extensions, and config hash for cache invalidation

On subsequent runs, only changed files and their transitive dependents are re-analysed.

### Error ignoring (`src/Analyser/Ignore/`)

`IgnoredErrorHelper` processes error ignore patterns from configuration and inline `@phpstan-ignore` comments. Supports regex patterns, error identifiers, and file-specific ignoring. Tracks which ignore patterns were matched so unmatched patterns can be reported.

### PHPStan\Type - Type system

Implementations of the `Type` interface (`src/Type/Type.php`) represent everything PHPStan knows about types. Each type knows:
- What it accepts (`accepts()`) and what is a supertype of it (`isSuperTypeOf()`)
- What properties/methods/constants it has
- What operations result in what types (array operations, arithmetic, string operations, etc.)
- How to describe itself for error messages (`describe()`)
- How to narrow itself (`tryRemove()`, generalize, traverse)

Key type classes:
- `ObjectType`, `StringType`, `IntegerType`, `FloatType`, `BooleanType`, `NullType`, `ArrayType`, `MixedType`, `NeverType`, `VoidType`
- `UnionType`, `IntersectionType` - composite types
- `Constant\ConstantStringType`, `Constant\ConstantIntegerType`, `Constant\ConstantArrayType` - literal/known values
- `Generic\GenericObjectType`, `Generic\TemplateType` - generics
- `Accessory\AccessoryNonEmptyStringType`, `Accessory\NonEmptyArrayType`, etc. - combined via intersection for refined types like `non-empty-string`
- `IntegerRangeType` - integer ranges like `int<0, 100>`
- `Enum\EnumCaseObjectType` - enum cases
- `ClosureType`, `CallableType` - callable types
- `StaticType`, `ThisType` - late static binding

**`TypeCombinator`** - Used instead of constructing `UnionType`/`IntersectionType` directly. Handles type normalization (e.g. `mixed|int` becomes `mixed`, `string&int` becomes `never`).

**`TrinaryLogic`** - Three-valued logic (yes/no/maybe) used throughout the type system. Many Type methods return `TrinaryLogic` instead of `bool` because type relationships aren't always certain (e.g. `mixed` might be a string - that's `maybe`).

To query whether a type is a specific type, use `isSuperTypeOf()`, not `instanceof`. For example, `(new StringType())->isSuperTypeOf($type)->yes()` correctly handles union types, intersection types with accessory types, etc. There are also shortcut methods like `$type->isString()`, `$type->isInteger()`, etc.

### PHPStan\Rules - Static analysis checks

Rules implement the `Rule<TNodeType>` interface (`src/Rules/Rule.php`):
- `getNodeType(): string` - returns the AST node class to listen for
- `processNode(Node $node, Scope $scope): array` - returns errors found at this node

Rules are organized into subdirectories by category: `Classes/`, `Methods/`, `Properties/`, `Functions/`, `Variables/`, `DeadCode/`, `Generics/`, `PhpDoc/`, `Cast/`, `Comparison/`, `Exceptions/`, `Pure/`, `Arrays/`, `Types/`, etc.

Rules are registered in configuration via the `phpstan.rules.rule` service tag or the `rules:` section. Different rule levels activate different rules via `conf/config.level*.neon`.

**Collectors** (`src/Collectors/`) - For rules that need cross-file information (e.g. unused code detection). Collectors gather data across all files in parallel processes, then a rule registered for `CollectedDataNode` processes the aggregated data.

### PHPStan\Reflection - Code metadata

PHPStan has its own reflection layer, primarily backed by the BetterReflection library (`ondrejmirtes/better-reflection`), which provides static reflection (reading code without loading it).

**`ReflectionProvider`** (`src/Reflection/ReflectionProvider.php`) - Central entry point for looking up classes, functions, and constants.

**`ClassReflection`** (`src/Reflection/ClassReflection.php`) - Represents classes, interfaces, traits, and enums. Provides access to methods, properties, constants, parent classes, interfaces, traits, generics, PHPDocs, and attributes.

**Class reflection extensions** allow describing magic properties/methods from `__get`/`__set`/`__call`.

The reflection layer also includes `ParametersAcceptor` for function/method signatures (with multi-variant support for overloaded built-in functions), `SignatureMap` for built-in PHP function signatures, and stub files for overriding third-party type information.

### PHPStan\Node - Virtual AST nodes

PHPStan augments the nikic/php-parser AST with custom virtual nodes (`src/Node/`):
- `FileNode` - wraps an entire file
- `InClassNode`, `InClassMethodNode`, `InFunctionNode`, `InTraitNode` - provide scope-aware context (e.g. `InClassNode` lets rules access `$scope->getClassReflection()`)
- `ClassPropertiesNode`, `ClassMethodsNode`, `ClassConstantsNode` - aggregate all properties/methods/constants of a class (useful for checking completeness)
- `ClassPropertyNode` - unifies traditional and promoted properties
- `CollectedDataNode` - carries aggregated data from collectors
- `ExecutionEndNode` - marks unreachable code points
- `MatchExpressionNode`, `BooleanAndNode`, `BooleanOrNode` - enhanced representations of expressions

### PHPStan\PhpDoc - PHPDoc parsing

Uses `phpstan/phpdoc-parser` to parse PHPDoc comments into an AST, then resolves PHPDoc types into `PHPStan\Type\Type` objects via `TypeNodeResolver`. Handles `@param`, `@return`, `@var`, `@throws`, `@template`, `@extends`, `@implements`, `@phpstan-assert`, `@phpstan-type`, `@phpstan-import-type`, conditional return types, and more.

### PHPStan\DependencyInjection - Service container

Uses Nette DI container. Services are configured in NEON files. Extensions register via service tags like `phpstan.rules.rule`, `phpstan.broker.dynamicMethodReturnTypeExtension`, `phpstan.typeSpecifier.functionTypeSpecifyingExtension`, `phpstan.collector`, etc.

The `#[AutowiredService]` and `#[AutowiredParameter]` attributes are used for automatic service registration.

### PHPStan\Node\ClassStatementsGatherer

Collects structural information about a class during AST traversal: properties, methods, method calls, property reads/writes. Used by virtual nodes like `ClassPropertiesNode` and `ClassMethodsNode` to provide aggregate information for rules that check class-level invariants (e.g. unused private properties, uninitialized properties).

### PHPStan\Fixable - Auto-fixing

`Patcher` coordinates automatic error fixes. Rules can attach fix information to errors via `RuleErrorBuilder`. The `PhpPrinter` handles code generation, `ReplacingNodeVisitor` performs AST node replacements, and indentation is preserved via `PhpPrinterIndentationDetectorVisitor`.

### Other components

- **`PHPStan\Parser`** - Wraps nikic/php-parser with caching, visitor registration, and PHPStan-specific AST enrichment (e.g. `ArrayMapArgVisitor`, `ImmediatelyInvokedClosureVisitor`)
- **`PHPStan\Parallel`** - `ParallelAnalyser` + `Scheduler` + `ProcessPool` distribute file analysis across child processes via React TCP server
- **`PHPStan\Command`** - CLI commands (`AnalyseCommand`, `ClearResultCacheCommand`, etc.) and error formatters (table, json, github actions, etc.)
- **`PHPStan\Dependency`** - Tracks file dependencies for incremental analysis / result cache. `ExportedNode` represents a class/function/constant signature for detecting API changes.
- **`PHPStan\File`** - File path resolution and reading
- **`PHPStan\Php`** - `PhpVersion` abstraction with version source tracking (runtime, config, composer platform). Methods like `supportsEnums()`, `supportsReadonlyProperties()`, etc. for version-specific behavior.
- **`PHPStan\Cache`** - Caching infrastructure
- **`PHPStan\Testing`** - `RuleTestCase`, `TypeInferenceTestCase`, and other test utilities

## Extension points

PHPStan is highly extensible. Key extension interfaces:

- **Custom rules** - `PHPStan\Rules\Rule` interface, tag: `phpstan.rules.rule`
- **Dynamic return type extensions** - `DynamicMethodReturnTypeExtension`, `DynamicStaticMethodReturnTypeExtension`, `DynamicFunctionReturnTypeExtension`
- **Type-specifying extensions** - `MethodTypeSpecifyingExtension`, `StaticMethodTypeSpecifyingExtension`, `FunctionTypeSpecifyingExtension` - for custom type narrowing (like `is_int()`)
- **Class reflection extensions** - `PropertiesClassReflectionExtension`, `MethodsClassReflectionExtension` - for magic properties/methods
- **Dynamic throw type extensions** - describe when functions throw based on arguments
- **Closure type extensions** - override closure parameter/return types or `$this` binding
- **Custom PHPDoc types** - `TypeNodeResolverExtension` for custom type syntax
- **Collectors** - `PHPStan\Collectors\Collector` for cross-file analysis
- **Error formatters** - custom output formats
- **Restricted usage extensions** - simple interfaces to restrict where methods/properties/functions can be called from
- **Allowed subtypes** - define sealed class hierarchies
- **Always-read/written properties, always-used constants/methods** - suppress false positives for dead code detection

## Backward compatibility promise

Code marked with `@api` must not break backward compatibility for existing usages in third-party extensions and packages. The `@api` tag signals that the code is part of the public API for extension developers and is protected from breaking changes across minor versions. Key rules:

- **`@api` classes**: All public methods can be called by extensions. Non-final classes can be extended.
- **`@api` interfaces**: All methods can be called. Interfaces can be implemented unless also marked with `@api-do-not-implement` or similar restrictions.
- **Constructors**: Changing a constructor that is NOT marked with `@api` in an `@api`-marked class is okay — extensions should use dependency injection, not direct instantiation.
- **`@api` + `@api-do-not-implement` interfaces**: Adding new methods is okay, since third parties are not expected to implement these interfaces.
- **Non-`@api` code**: Any code without `@api` may change in minor versions without notice.

When making changes, check whether the affected code has `@api` tags. If it does, ensure existing call sites in third-party code would not break.

## Common bug fix patterns and development guidance

Based on analysis of recent releases (2.1.30-2.1.38), these are the recurring patterns for how bugs are found and fixed:

### Type system: never use `instanceof` to check types

A recurring cleanup theme: never use `$type instanceof StringType` or similar. This misses union types, intersection types with accessory types, and other composite forms. Always use `$type->isString()->yes()` or `(new StringType())->isSuperTypeOf($type)`. Multiple PRs have systematically replaced `instanceof *Type` checks throughout the codebase.

### Type system: add methods to the Type interface instead of one-offing conditions

When a bug requires checking a type property across the codebase, the fix is often to add a new method to the `Type` interface rather than scattering `instanceof` checks or utility function calls throughout rules and extensions. This ensures every type implementation handles the query correctly (including union/intersection types which delegate to their inner types) and keeps the logic centralized.

Historical analysis of `Type.php` via `git blame` shows that new methods are added for several recurring reasons:

- **Replacing scattered `instanceof` checks (~30%)**: Methods like `isNull()`, `isTrue()`, `isFalse()`, `isString()`, `isInteger()`, `isFloat()`, `isBoolean()`, `isArray()`, `isScalar()`, `isObject()`, `isEnum()`, `getClassStringObjectType()`, `getObjectClassNames()`, `getObjectClassReflections()` were added to replace `$type instanceof ConstantBooleanType`, `$type instanceof StringType`, etc. Each type implements the method correctly — e.g., `UnionType::isNull()` returns `yes` only if all members are null, `maybe` if some are, `no` if none are. This is impossible to get right with a single `instanceof` check.

- **Moving logic from TypeUtils/extensions into Type (~35%)**: Methods like `toArrayKey()`, `toBoolean()`, `toNumber()`, `toFloat()`, `toInteger()`, `toString()`, `toArray()`, `flipArray()`, `getKeysArray()`, `getValuesArray()`, `popArray()`, `shiftArray()`, `shuffleArray()`, `reverseSortArray()`, `getEnumCases()`, `isCallable()`, `getCallableParametersAcceptors()`, `isList()` moved scattered utility logic into polymorphic dispatch. When logic lives in a utility function it typically uses a chain of `if ($type instanceof X) ... elseif ($type instanceof Y) ...` which breaks when new type classes are added or misses edge cases in composite types.

- **Supporting new type features (~15%)**: Methods like `isNonEmptyString()`, `isNonFalsyString()`, `isLiteralString()`, `isClassString()`, `isNonEmptyArray()`, `isIterableAtLeastOnce()` were added as PHPStan gained support for more refined types (accessory types in intersections). These enable rules to query refined properties without knowing how the refinement is represented internally.

- **Bug fixes through better polymorphism (~10%)**: Some bugs are directly fixed by adding a new Type method. For example, `isOffsetAccessLegal()` fixed false positives about illegal offset access by letting each type declare whether `$x[...]` is valid. `setExistingOffsetValueType()` (distinct from `setOffsetValueType()`) fixed array list type preservation bugs. `toCoercedArgumentType()` fixed parameter type contravariance issues during type coercion.

- **Richer return types (~5%)**: Methods that returned `TrinaryLogic` were changed to return `AcceptsResult` or `IsSuperTypeOfResult`, which carry human-readable reasons for why a type relationship holds or doesn't. This enabled better error messages without changing the call sites significantly.

When considering a bug fix that involves checking "is this type a Foo?", first check whether an appropriate method already exists on `Type`. If not, consider whether adding one would be the right fix — especially if the check is needed in more than one place or involves logic that varies by type class.

### Testing patterns

- **Rule tests**: Extend `RuleTestCase`, implement `getRule()`, call `$this->analyse([__DIR__ . '/data/my-test.php'], [...expected errors...])`. Expected errors are `[message, line]` pairs. Test data files live in `tests/PHPStan/Rules/*/data/`.
- **Type inference tests**: Use `assertType()` and `assertNativeType()` helper functions in test data files. The test runner verifies PHPStan infers the declared types at each `assertType()` call.
- **Regression tests**: For each bug fix, add a test data file reproducing the issue (e.g. `tests/PHPStan/Rules/*/data/bug-12345.php` or `tests/PHPStan/Analyser/nsrt/bug-12345.php`).
- **Integration tests**: `AnalyserIntegrationTest` runs full analysis on test files and checks error output.

### Adding support for new PHP versions

Recent work on PHP 8.5 support shows the pattern:
- **Parser support**: Update nikic/php-parser dependency, handle new AST node types
- **NodeScopeResolver**: Handle new syntax (pipe operator, clone-with, void cast)
- **Type system**: New type representations if needed
- **Rules**: Version-gated rules (e.g. deprecated casts only reported on PHP 8.5+, `#[NoDiscard]` only on PHP 8.5+)
- **InitializerExprTypeResolver**: Support new constant expression forms (casts, first-class callables, static closures in initializers)
- **Reflection**: Support new attributes, property features (asymmetric visibility on static properties, `#[Override]` on properties)
- **PhpVersion**: Add detection methods like `supportsPropertyHooks()`, `supportsPipeOperator()`, etc.
- **Stubs**: Update function/class stubs for new built-in functions and changed signatures

## Writing PHPDocs

When adding or editing PHPDoc comments in this codebase, follow these guidelines:

### What to document

- **Class-level docs on interfaces and key abstractions**: Explain the role of the interface, what implements it, and how it fits into the architecture. Mention non-obvious patterns like double-dispatch (CompoundType), the intersection-with-base-type requirement (AccessoryType), or the instanceof-avoidance rule (TypeWithClassName).
- **Non-obvious behavior**: Document when a method's behavior differs from what its name suggests, or when there are subtle contracts. For example: `getDeclaringClass()` returning the declaring class even for inherited members, `setExistingOffsetValueType()` vs `setOffsetValueType()` preserving list types differently, or `getWritableType()` potentially differing from `getReadableType()` due to asymmetric visibility.
- **`@api` tags**: Keep these — they mark the public API for extension developers.
- **`@phpstan-assert` tags**: Keep these — they provide type narrowing information that PHPStan uses.
- **`@return`, `@param`, `@template` tags**: Keep when they provide type information not expressible in native PHP types (e.g. `@return self::SOURCE_*`, `@param array<string, Type>`).

### What NOT to document

- **Obvious from the method name**: Do not write "Returns the name" above `getName()`, "Returns the value type" above `getValueType()`, or "Returns whether deprecated" above `isDeprecated()`. If the method name says it all, add no description.
- **Obvious to experienced PHP developers**: Do not explain standard visibility rules ("public methods are always callable, protected methods are callable from subclasses..."), standard PHP semantics, or basic design patterns.
- **Obvious from tags**: Do not add prose that restates what `@return`, `@phpstan-assert`, or `@param` tags already say. If `@return non-empty-string|null` is present, do not also write "Returns a non-empty string or null".
- **Factory method descriptions that repeat the class-level doc**: If the class doc already explains the levels/variants (like VerbosityLevel or GeneralizePrecision), don't repeat those descriptions on each factory method. A bare `@api` tag is sufficient.
- **Getter/setter/query methods on value objects**: Methods like `isInvariant()`, `isCovariant()`, `isEmpty()`, `count()`, `getType()`, `hasType()` on simple value objects need no PHPDoc.

### Style

- Keep descriptions concise — one or two sentences for method docs when needed.
- Use imperative voice without "Returns the..." preambles when a brief note suffices. Prefer `/** Replaces unresolved TemplateTypes with their bounds. */` over a multi-line block.
- Preserve `@api` and type tags on their own lines, with no redundant description alongside them.

## Important dependencies

- `nikic/php-parser` ^5.7.0 - PHP AST parsing
- `ondrejmirtes/better-reflection` - Static reflection (reading code without loading it)
- `phpstan/phpdoc-parser` - PHPDoc parsing
- `nette/di` - Dependency injection container
- `nette/neon` - Configuration file format
- `react/child-process`, `react/async` - Parallel analysis
- `symfony/console` - CLI interface
- `hoa/compiler` - Used for regex type parsing
