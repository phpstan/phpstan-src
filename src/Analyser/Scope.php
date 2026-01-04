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
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;

/** @api */
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

	public function getFile(): string;

	public function getFileDescription(): string;

	public function isDeclareStrictTypes(): bool;

	/**
	 * @phpstan-assert-if-true !null $this->getTraitReflection()
	 */
	public function isInTrait(): bool;

	public function getTraitReflection(): ?ClassReflection;

	public function getFunction(): ?PhpFunctionFromParserNodeReflection;

	public function getFunctionName(): ?string;

	public function getParentScope(): ?self;

	public function hasVariableType(string $variableName): TrinaryLogic;

	public function getVariableType(string $variableName): Type;

	public function canAnyVariableExist(): bool;

	/**
	 * @return array<int, string>
	 */
	public function getDefinedVariables(): array;

	/**
	 * @return array<int, string>
	 */
	public function getMaybeDefinedVariables(): array;

	public function hasConstant(Name $name): bool;

	/** @deprecated Use getInstancePropertyReflection or getStaticPropertyReflection instead */
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

	public function getType(Expr $node): Type;

	public function getNativeType(Expr $expr): Type;

	public function getKeepVoidType(Expr $node): Type;

	/**
	 * The `getType()` method along with FNSR enabled
	 * waits for the Expr analysis to be completed
	 * in order to evaluate the type at the right place in the code.
	 *
	 * This prevents tricky bugs when reasoning about code like
	 * `doFoo($a = 1, $a)`.
	 *
	 * Sometimes this is counter-productive because we actually want
	 * to use the current Scope object contents to resolve the Expr type.
	 *
	 * In these cases use `getScopeType()`.
	 */
	public function getScopeType(Expr $expr): Type;

	public function getScopeNativeType(Expr $expr): Type;

	public function resolveName(Name $name): string;

	public function resolveTypeByName(Name $name): TypeWithClassName;

	/**
	 * @param mixed $value
	 */
	public function getTypeFromValue($value): Type;

	public function hasExpressionType(Expr $node): TrinaryLogic;

	public function isInClassExists(string $className): bool;

	public function isInFunctionExists(string $functionName): bool;

	public function isInClosureBind(): bool;

	/** @return list<FunctionReflection|ExtendedMethodReflection> */
	public function getFunctionCallStack(): array;

	/** @return list<array{FunctionReflection|ExtendedMethodReflection, ExtendedParameterReflection|null}> */
	public function getFunctionCallStackWithParameters(): array;

	public function isParameterValueNullable(Param $parameter): bool;

	/**
	 * @param Node\Name|Node\Identifier|Node\ComplexType|null $type
	 */
	public function getFunctionType($type, bool $isNullable, bool $isVariadic): Type;

	public function isInExpressionAssign(Expr $expr): bool;

	public function isUndefinedExpressionAllowed(Expr $expr): bool;

	public function filterByTruthyValue(Expr $expr): self;

	public function filterByFalseyValue(Expr $expr): self;

	public function isInFirstLevelStatement(): bool;

	public function getPhpVersion(): PhpVersions;

	/** @internal */
	public function toMutatingScope(): MutatingScope;

}
