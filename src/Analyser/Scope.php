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
 * @api-do-not-implement
 */
interface Scope extends ClassMemberAccessAnswerer, NamespaceAnswerer
{

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
	 * When analysing a trait, returns the file where the trait is used,
	 * not the trait file itself. Use getFileDescription() for the trait file path.
	 */
	public function getFile(): string;

	/**
	 * For traits, returns the trait file path with the using class context,
	 * e.g. "TraitFile.php (in context of class MyClass)".
	 */
	public function getFileDescription(): string;

	public function isDeclareStrictTypes(): bool;

	/**
	 * @phpstan-assert-if-true !null $this->getTraitReflection()
	 */
	public function isInTrait(): bool;

	/**
	 * Returns the trait itself, not the class using the trait.
	 * Use getClassReflection() for the using class.
	 */
	public function getTraitReflection(): ?ClassReflection;

	public function getFunction(): ?PhpFunctionFromParserNodeReflection;

	public function getFunctionName(): ?string;

	public function getParentScope(): ?self;

	public function hasVariableType(string $variableName): TrinaryLogic;

	public function getVariableType(string $variableName): Type;

	/**
	 * True at the top level of a file or after extract() — contexts where
	 * arbitrary variables may exist.
	 */
	public function canAnyVariableExist(): bool;

	/** @return array<int, string> */
	public function getDefinedVariables(): array;

	/**
	 * Variables with TrinaryLogic::Maybe certainty — defined in some code paths but not others.
	 *
	 * @return array<int, string>
	 */
	public function getMaybeDefinedVariables(): array;

	public function hasConstant(Name $name): bool;

	/**
	 * @deprecated Use getInstancePropertyReflection or getStaticPropertyReflection instead
	 */
	public function getPropertyReflection(Type $typeWithProperty, string $propertyName): ?ExtendedPropertyReflection;

	public function getInstancePropertyReflection(Type $typeWithProperty, string $propertyName): ?ExtendedPropertyReflection;

	public function getStaticPropertyReflection(Type $typeWithProperty, string $propertyName): ?ExtendedPropertyReflection;

	public function getMethodReflection(Type $typeWithMethod, string $methodName): ?ExtendedMethodReflection;

	public function getConstantReflection(Type $typeWithConstant, string $constantName): ?ClassConstantReflection;

	public function getConstantExplicitTypeFromConfig(string $constantName, Type $constantType): Type;

	public function getIterableKeyType(Type $iteratee): Type;

	public function getIterableValueType(Type $iteratee): Type;

	/**
	 * @phpstan-assert-if-true !null $this->getAnonymousFunctionReflection()
	 * @phpstan-assert-if-true !null $this->getAnonymousFunctionReturnType()
	 */
	public function isInAnonymousFunction(): bool;

	public function getAnonymousFunctionReflection(): ?ClosureType;

	public function getAnonymousFunctionReturnType(): ?Type;

	/**
	 * Returns the PHPDoc-enhanced type. Use getNativeType() for native types only.
	 */
	public function getType(Expr $node): Type;

	/**
	 * Returns only what PHP's native type system knows, ignoring PHPDoc.
	 */
	public function getNativeType(Expr $expr): Type;

	/**
	 * Like getType(), but preserves void for function/method calls
	 * (normally getType() replaces void with null).
	 */
	public function getKeepVoidType(Expr $node): Type;

	/**
	 * Unlike getType() which may defer evaluation, this uses the scope's
	 * current state immediately.
	 */
	public function getScopeType(Expr $expr): Type;

	public function getScopeNativeType(Expr $expr): Type;

	/**
	 * Resolves a Name AST node to a fully qualified class name string.
	 *
	 * Handles special names: `self` and `static` resolve to the current class,
	 * `parent` resolves to the parent class. Other names are returned as-is
	 * (they should already be fully qualified by the PHP parser's name resolver).
	 *
	 * Inside a Closure::bind() context, `self`/`static` resolve to the bound class.
	 *
	 * @return non-empty-string
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
	 *
	 * @return static
	 */
	public function filterByTruthyValue(Expr $expr): self;

	/**
	 * Returns a new Scope with types narrowed by assuming the expression is falsy.
	 *
	 * The opposite of filterByTruthyValue(). Given `$x instanceof Foo`, returns
	 * a scope where $x is known NOT to be of type Foo. This is the scope used
	 * in the else-branch of `if ($x instanceof Foo)`.
	 *
	 * @return static
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
	public function toWalkScope(): MutatingScope;

	/** @deprecated The scope answers every ask directly - call the methods on the scope itself, or toWalkScope() for the engine-facing walk scope. */
	public function toMutatingScope(): MutatingScope;

}
