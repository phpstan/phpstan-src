<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;

/** @api */
interface Scope extends ClassMemberAccessAnswerer, NamespaceAnswerer
{

	public function getFile(): string;

	public function getFileDescription(): string;

	public function isDeclareStrictTypes(): bool;

	/**
	 * @phpstan-assert-if-true !null $this->getTraitReflection()
	 */
	public function isInTrait(): bool;

	public function getTraitReflection(): ?ClassReflection;

	/**
	 * @return FunctionReflection|ExtendedMethodReflection|null
	 */
	public function getFunction();

	public function getFunctionName(): ?string;

	public function getParentScope(): ?self;

	public function hasVariableType(string $variableName): TrinaryLogic;

	public function getVariableType(string $variableName): Type;

	public function canAnyVariableExist(): bool;

	/**
	 * @return array<int, string>
	 */
	public function getDefinedVariables(): array;

	public function hasConstant(Name $name): bool;

	public function getPropertyReflection(Type $typeWithProperty, string $propertyName): ?PropertyReflection;

	public function getMethodReflection(Type $typeWithMethod, string $methodName): ?ExtendedMethodReflection;

	public function getConstantReflection(Type $typeWithConstant, string $constantName): ?ConstantReflection;

	public function getIterableKeyType(Type $iteratee): Type;

	public function getIterableValueType(Type $iteratee): Type;

	public function isInAnonymousFunction(): bool;

	public function getAnonymousFunctionReflection(): ?ParametersAcceptor;

	public function getAnonymousFunctionReturnType(): ?Type;

	public function getType(Expr $node): Type;

	public function getNativeType(Expr $expr): Type;

	public function getKeepVoidType(Expr $node): Type;

	/**
	 * @deprecated Use getNativeType()
	 */
	public function doNotTreatPhpDocTypesAsCertain(): self;

	public function resolveName(Name $name): string;

	public function resolveTypeByName(Name $name): TypeWithClassName;

	/**
	 * @param mixed $value
	 */
	public function getTypeFromValue($value): Type;

	/** @deprecated use hasExpressionType instead */
	public function isSpecified(Expr $node): bool;

	public function hasExpressionType(Expr $node): TrinaryLogic;

	public function isInClassExists(string $className): bool;

	public function isInFunctionExists(string $functionName): bool;

	public function isInClosureBind(): bool;

	/** @return list<FunctionReflection|MethodReflection> */
	public function getFunctionCallStack(): array;

	/** @return list<array{FunctionReflection|MethodReflection, ParameterReflection|null}> */
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

}
