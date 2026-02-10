<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PHPStan\Php\PhpVersions;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;

/**
 * Represents the state of the analyser at a specific position in the AST.
 *
 * The Scope tracks everything PHPStan knows at a given point in code: variable types,
 * the current class/function/method context, whether strict_types is enabled, and more.
 * It is the primary interface through which rules and extensions query information
 * about the analysed code.
 *
 * The Scope is passed as a parameter to:
 * - Custom rules (2nd parameter of processNode())
 * - Dynamic return type extensions (last parameter of getTypeFrom*Call())
 * - Dynamic throw type extensions
 * - Type-specifying extensions (3rd parameter of specifyTypes())
 *
 * The Scope is immutable from the extension's perspective. Each AST node gets
 * its own Scope reflecting the analysis state at that point. For example, after
 * an `if ($x instanceof Foo)` check, the Scope inside the if-branch knows that
 * $x is of type Foo.
 *
 * @api
 */
interface Scope extends ClassMemberAccessAnswerer, NamespaceAnswerer
{

	/** @var list<string> PHP superglobal variable names that are always available */
	public const SUPERGLOBAL_VARIABLES = [
		'GLOBALS',
		'_SERVER',
		'_GET',
		'_POST',
		'_FILES',
		'_COOKIE',
		'_SESSION',
		'_REQUEST',
		'_ENV',
	];

	/**
	 * Returns the absolute path of the file being analysed.
	 *
	 * When analysing a trait, this returns the file where the trait is used (the class file),
	 * not the trait file itself. Use getFileDescription() to get the trait file path
	 * with class context information.
	 */
	public function getFile(): string;

	/**
	 * Returns a human-readable file description for error messages.
	 *
	 * For regular files, this is the same as getFile().
	 * For traits, this returns the trait file path with the using class context,
	 * e.g. "TraitFile.php (in context of class MyClass)".
	 */
	public function getFileDescription(): string;

	/**
	 * Returns whether the current file has declare(strict_types=1).
	 *
	 * When true, PHP enforces strict type checking for function/method arguments
	 * and return values — no implicit type coercion is performed. This affects
	 * how Type::accepts() behaves (e.g. int is not accepted by float in strict mode).
	 */
	public function isDeclareStrictTypes(): bool;

	/**
	 * Returns whether the current analysis context is inside a trait.
	 *
	 * When true, getTraitReflection() is guaranteed to return non-null.
	 * Used by rules that need trait-specific behavior, such as skipping
	 * certain checks that don't apply in trait context.
	 *
	 * @phpstan-assert-if-true !null $this->getTraitReflection()
	 */
	public function isInTrait(): bool;

	/**
	 * Returns the ClassReflection of the trait being analysed, or null.
	 *
	 * Only non-null when isInTrait() is true. The returned reflection
	 * represents the trait itself, not the class using the trait.
	 * Use getClassReflection() (from ClassMemberAccessAnswerer) to get the
	 * class that uses the trait.
	 */
	public function getTraitReflection(): ?ClassReflection;

	/**
	 * Returns the reflection of the current function or method, or null.
	 *
	 * Returns null when outside of any function/method (e.g. at the top level
	 * of a file, or in a class but outside a method). For closures and arrow
	 * functions, returns their reflection.
	 */
	public function getFunction(): ?PhpFunctionFromParserNodeReflection;

	/**
	 * Returns the name of the current function or method, or null.
	 *
	 * For methods, returns the method name (not the fully qualified name).
	 * For closures and arrow functions, returns null.
	 * For top-level code, returns null.
	 */
	public function getFunctionName(): ?string;

	/**
	 * Returns the parent scope, or null if this is the top-level scope.
	 *
	 * The parent scope is the scope that encloses the current one. For example,
	 * when inside a closure, the parent scope is the scope of the function
	 * that contains the closure. Used for variable resolution in closures
	 * and arrow functions.
	 */
	public function getParentScope(): ?self;

	/**
	 * Returns whether a variable with the given name exists in the current scope.
	 *
	 * Returns TrinaryLogic::Yes if the variable is definitely defined,
	 * TrinaryLogic::Maybe if it might be defined (e.g. defined in one branch of an if),
	 * and TrinaryLogic::No if it is not defined.
	 */
	public function hasVariableType(string $variableName): TrinaryLogic;

	/**
	 * Returns the type of a variable in the current scope.
	 *
	 * If the variable is not defined, returns ErrorType.
	 * Check hasVariableType() first if you need to distinguish between
	 * undefined variables and variables with unknown types.
	 */
	public function getVariableType(string $variableName): Type;

	/**
	 * Returns whether any variable can potentially exist in this scope.
	 *
	 * Returns true at the top level of a file (outside functions/closures)
	 * or after an extract() call — contexts where arbitrary variables may exist.
	 * Returns false inside functions, methods, and closures (unless extract()
	 * was called), where the set of available variables is known.
	 *
	 * Used by the DefinedVariableRule to suppress "undefined variable" errors
	 * when the full variable set is not known.
	 */
	public function canAnyVariableExist(): bool;

	/**
	 * Returns the names of all variables that are definitely defined in this scope.
	 *
	 * Only includes variables with TrinaryLogic::Yes certainty.
	 *
	 * @return array<int, string>
	 */
	public function getDefinedVariables(): array;

	/**
	 * Returns the names of variables that might be defined in this scope.
	 *
	 * Only includes variables with TrinaryLogic::Maybe certainty — variables
	 * that are defined in some code paths but not others (e.g. defined inside
	 * an if-branch but not in the else-branch).
	 *
	 * @return array<int, string>
	 */
	public function getMaybeDefinedVariables(): array;

	/**
	 * Returns whether a global constant with the given name exists.
	 *
	 * Checks both PHP built-in constants and user-defined constants.
	 * The Name node is resolved according to the current namespace.
	 */
	public function hasConstant(Name $name): bool;

	/**
	 * @deprecated Use getInstancePropertyReflection or getStaticPropertyReflection instead
	 */
	public function getPropertyReflection(Type $typeWithProperty, string $propertyName): ?ExtendedPropertyReflection;

	/**
	 * Returns the reflection for an instance property on the given type, or null.
	 *
	 * Resolves the property through the type system, handling union types,
	 * intersection types, and visibility checks. Returns null if the property
	 * doesn't exist or is not accessible from the current scope.
	 */
	public function getInstancePropertyReflection(Type $typeWithProperty, string $propertyName): ?ExtendedPropertyReflection;

	/**
	 * Returns the reflection for a static property on the given type, or null.
	 *
	 * Like getInstancePropertyReflection() but for static properties (Foo::$bar).
	 * Returns null if the property doesn't exist or is not accessible.
	 */
	public function getStaticPropertyReflection(Type $typeWithProperty, string $propertyName): ?ExtendedPropertyReflection;

	/**
	 * Returns the reflection for a method on the given type, or null.
	 *
	 * Resolves the method through the type system, handling union types,
	 * intersection types, and visibility checks. Returns null if the method
	 * doesn't exist or is not accessible from the current scope.
	 */
	public function getMethodReflection(Type $typeWithMethod, string $methodName): ?ExtendedMethodReflection;

	/**
	 * Returns the reflection for a class constant on the given type, or null.
	 *
	 * Resolves the constant through the type system. Returns null if the
	 * constant doesn't exist or is not accessible from the current scope.
	 */
	public function getConstantReflection(Type $typeWithConstant, string $constantName): ?ClassConstantReflection;

	/**
	 * Returns the explicitly configured type for a global constant, if any.
	 *
	 * Checks the PHPStan configuration for user-specified constant type overrides
	 * (via the `constants` configuration option). Falls back to the given $constantType
	 * if no override is configured.
	 */
	public function getConstantExplicitTypeFromConfig(string $constantName, Type $constantType): Type;

	/**
	 * Returns the key type of an iterable type.
	 *
	 * Unlike calling $iteratee->getIterableKeyType() directly, this method
	 * goes through the Scope to properly resolve template types and handle
	 * scope-specific type refinements.
	 */
	public function getIterableKeyType(Type $iteratee): Type;

	/**
	 * Returns the value type of an iterable type.
	 *
	 * Unlike calling $iteratee->getIterableValueType() directly, this method
	 * goes through the Scope to properly resolve template types and handle
	 * scope-specific type refinements.
	 */
	public function getIterableValueType(Type $iteratee): Type;

	/**
	 * Returns whether the current analysis context is inside an anonymous function
	 * (closure or arrow function).
	 *
	 * When true, both getAnonymousFunctionReflection() and
	 * getAnonymousFunctionReturnType() are guaranteed to return non-null.
	 *
	 * @phpstan-assert-if-true !null $this->getAnonymousFunctionReflection()
	 * @phpstan-assert-if-true !null $this->getAnonymousFunctionReturnType()
	 */
	public function isInAnonymousFunction(): bool;

	/**
	 * Returns the ClosureType reflection of the current anonymous function, or null.
	 *
	 * Only non-null when isInAnonymousFunction() is true. The ClosureType
	 * contains the closure's parameter types, return type, and template types.
	 */
	public function getAnonymousFunctionReflection(): ?ClosureType;

	/**
	 * Returns the declared return type of the current anonymous function, or null.
	 *
	 * Only non-null when isInAnonymousFunction() is true. Used by return type
	 * rules to validate that the closure returns the correct type.
	 */
	public function getAnonymousFunctionReturnType(): ?Type;

	/**
	 * Returns the type of a PHP expression at this point in the analysis.
	 *
	 * This is the most important method on Scope. It evaluates the type of any
	 * expression AST node, taking into account all type information available at
	 * the current analysis position — variable assignments, type narrowing from
	 * conditions, PHPDoc annotations, and more.
	 *
	 * The returned type reflects PHPDoc-enhanced types. Use getNativeType() to get
	 * the type based only on PHP's native type system (typehints, assignments).
	 *
	 * Note: This method may defer evaluation until the expression's analysis is
	 * complete (see getScopeType() for cases where immediate evaluation is needed).
	 */
	public function getType(Expr $node): Type;

	/**
	 * Returns the native PHP type of an expression, ignoring PHPDoc annotations.
	 *
	 * Unlike getType() which includes PHPDoc-enhanced type information (like
	 * generic types, more specific return types from @return tags, etc.), this
	 * method returns only what PHP's native type system knows.
	 *
	 * Used when you need to distinguish between what PHP enforces at runtime
	 * vs. what PHPDoc promises at the documentation level.
	 */
	public function getNativeType(Expr $expr): Type;

	/**
	 * Like getType(), but preserves the void type for function/method calls.
	 *
	 * Normally, getType() replaces void return types with null (since void
	 * functions effectively return null). This method keeps the void type,
	 * which is needed by return type rules that must distinguish between
	 * "returns null" and "returns void".
	 */
	public function getKeepVoidType(Expr $node): Type;

	/**
	 * Returns the type of an expression using the current scope state directly.
	 *
	 * Unlike getType(), which may defer evaluation until the expression's
	 * full analysis is complete (to handle cases like `doFoo($a = 1, $a)`
	 * where argument evaluation order matters), this method uses the scope's
	 * current state immediately.
	 *
	 * Use this when you intentionally want the type as it exists in the
	 * current scope snapshot, not the final resolved type.
	 */
	public function getScopeType(Expr $expr): Type;

	/**
	 * Like getScopeType(), but returns the native PHP type only.
	 *
	 * Combines the immediate-evaluation behavior of getScopeType() with
	 * the PHPDoc-ignoring behavior of getNativeType().
	 */
	public function getScopeNativeType(Expr $expr): Type;

	/**
	 * Resolves a Name AST node to a fully qualified class name string.
	 *
	 * Handles special names: `self` and `static` resolve to the current class,
	 * `parent` resolves to the parent class. Other names are returned as-is
	 * (they should already be fully qualified by the PHP parser's name resolver).
	 *
	 * Inside a Closure::bind() context, `self`/`static` resolve to the bound class.
	 */
	public function resolveName(Name $name): string;

	/**
	 * Resolves a Name AST node to a TypeWithClassName.
	 *
	 * Unlike resolveName() which returns a plain string, this returns a proper
	 * Type object that preserves late-static-binding information:
	 * - `static` returns a StaticType (preserves LSB in subclasses)
	 * - `self` returns a ThisType when inside the same class hierarchy
	 * - Other names return an ObjectType
	 */
	public function resolveTypeByName(Name $name): TypeWithClassName;

	/**
	 * Returns the PHPStan Type representing a given PHP value.
	 *
	 * Converts runtime PHP values to their corresponding constant types:
	 * integers become ConstantIntegerType, strings become ConstantStringType,
	 * arrays become ConstantArrayType (if small enough), etc.
	 *
	 * @param mixed $value
	 */
	public function getTypeFromValue($value): Type;

	/**
	 * Returns whether an expression has a tracked type in this scope.
	 *
	 * Returns TrinaryLogic::Yes if the expression's type is definitely known,
	 * TrinaryLogic::Maybe if it might be known, and TrinaryLogic::No if there
	 * is no type information for it.
	 *
	 * This checks the scope's expression type map without computing the type
	 * (unlike getType() which always computes a type).
	 */
	public function hasExpressionType(Expr $node): TrinaryLogic;

	/**
	 * Returns whether the given class name is being checked inside a
	 * class_exists(), interface_exists(), or trait_exists() call.
	 *
	 * When true, rules should suppress "class not found" errors because
	 * the code is explicitly checking for the class's existence.
	 */
	public function isInClassExists(string $className): bool;

	/**
	 * Returns whether the given function name is being checked inside a
	 * function_exists() call.
	 *
	 * When true, rules should suppress "function not found" errors because
	 * the code is explicitly checking for the function's existence.
	 */
	public function isInFunctionExists(string $functionName): bool;

	/**
	 * Returns whether the current analysis context is inside a Closure::bind()
	 * or Closure::bindTo() call.
	 *
	 * When true, the closure's $this and self/static may refer to a different
	 * class than the one where the closure was defined.
	 */
	public function isInClosureBind(): bool;

	/**
	 * Returns the stack of function/method calls that are currently being analysed.
	 *
	 * When analysing arguments of a function call, this returns the chain of
	 * enclosing calls. Used by extensions that need to know the calling context,
	 * such as type-specifying extensions for functions like class_exists().
	 *
	 * @return list<FunctionReflection|MethodReflection>
	 */
	public function getFunctionCallStack(): array;

	/**
	 * Like getFunctionCallStack(), but also includes the parameter being passed to.
	 *
	 * Each entry is a tuple of the function/method reflection and the parameter
	 * reflection for the argument position being analysed (or null if unknown).
	 *
	 * @return list<array{FunctionReflection|MethodReflection, ParameterReflection|null}>
	 */
	public function getFunctionCallStackWithParameters(): array;

	/**
	 * Returns whether a function parameter has a default value of null.
	 *
	 * Checks the parameter's default value AST node to determine if
	 * `= null` was specified. Used by function definition checks.
	 */
	public function isParameterValueNullable(Param $parameter): bool;

	/**
	 * Resolves a type AST node (from a parameter/return type declaration) to a Type.
	 *
	 * Handles named types, identifier types (int, string, etc.), union types,
	 * intersection types, and nullable types. The $isNullable flag adds null
	 * to the type, and $isVariadic wraps the type in an array.
	 *
	 * @param Node\Name|Node\Identifier|Node\ComplexType|null $type
	 */
	public function getFunctionType($type, bool $isNullable, bool $isVariadic): Type;

	/**
	 * Returns whether the given expression is currently being assigned to.
	 *
	 * Returns true during the analysis of the right-hand side of an assignment
	 * to this expression. For example, when analysing `$a = expr`, this returns
	 * true for the $a variable during the analysis of `expr`.
	 *
	 * Used to prevent infinite recursion when resolving types during assignment.
	 */
	public function isInExpressionAssign(Expr $expr): bool;

	/**
	 * Returns whether accessing the given expression in an undefined state is allowed.
	 *
	 * Returns true when the expression is on the left-hand side of an assignment
	 * or in similar contexts where it's valid for the expression to be undefined
	 * (e.g. `$a['key'] = value` where $a['key'] doesn't need to exist yet).
	 */
	public function isUndefinedExpressionAllowed(Expr $expr): bool;

	/**
	 * Returns a new Scope with types narrowed by assuming the expression is truthy.
	 *
	 * Given an expression like `$x instanceof Foo`, returns a scope where
	 * $x is known to be of type Foo. This is the scope used inside the
	 * if-branch of `if ($x instanceof Foo)`.
	 *
	 * Uses the TypeSpecifier internally to determine type narrowing.
	 */
	public function filterByTruthyValue(Expr $expr): self;

	/**
	 * Returns a new Scope with types narrowed by assuming the expression is falsy.
	 *
	 * The opposite of filterByTruthyValue(). Given `$x instanceof Foo`, returns
	 * a scope where $x is known NOT to be of type Foo. This is the scope used
	 * in the else-branch of `if ($x instanceof Foo)`.
	 */
	public function filterByFalseyValue(Expr $expr): self;

	/**
	 * Returns whether the current statement is a "first-level" statement.
	 *
	 * A first-level statement is one that is directly inside a function/method
	 * body, not nested inside control structures like if/else, loops, or
	 * try/catch. Used to determine whether certain checks should be more
	 * or less strict.
	 */
	public function isInFirstLevelStatement(): bool;

	/**
	 * Returns the PHP version(s) being analysed against.
	 *
	 * Returns a PhpVersions object that can represent a range of PHP versions
	 * (when the exact version is not known). Use its methods like
	 * supportsEnums(), supportsReadonlyProperties(), etc. to check for
	 * version-specific features.
	 */
	public function getPhpVersion(): PhpVersions;

	/** @internal */
	public function toMutatingScope(): MutatingScope;

}
