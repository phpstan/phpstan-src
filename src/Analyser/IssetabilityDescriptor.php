<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Rules\Properties\FoundPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\NeverType;

/**
 * The inside-out carrier for isset/empty/?? chains. Each chain-link
 * ExpressionResult (variable / array dim fetch / property fetch) holds the
 * descriptor for its own link plus references to the child ExpressionResult(s),
 * built during the single pass like containsNullsafe.
 *
 * resolve() walks the chain once on the asking scope and produces an
 * IssetabilityResolution of fully-resolved IssetabilityLinkInfo facts. The engine
 * (IssetabilityResolution::isSet) and the rule (PHPStan\Rules\IssetCheck) read
 * those facts; neither re-traverses the AST nor re-resolves types/reflections.
 */
final class IssetabilityDescriptor
{

	private const KIND_VARIABLE = 'variable';
	private const KIND_OFFSET = 'offset';
	private const KIND_PROPERTY = 'property';

	/**
	 * @param Closure(MutatingScope): ?FoundPropertyReflection|null $reflectionResolver
	 * @param PropertyFetch|StaticPropertyFetch|null $propertyFetch
	 */
	private function __construct(
		private string $kind,
		private ?string $variableName = null,
		private ?ExpressionResult $varResult = null,
		private ?ExpressionResult $dimResult = null,
		private ?ExpressionResult $innerResult = null,
		private ?Closure $reflectionResolver = null,
		private ?Expr $propertyFetch = null,
	)
	{
	}

	public static function variable(string $variableName): self
	{
		return new self(self::KIND_VARIABLE, variableName: $variableName);
	}

	public static function offset(ExpressionResult $varResult, ExpressionResult $dimResult): self
	{
		return new self(self::KIND_OFFSET, varResult: $varResult, dimResult: $dimResult);
	}

	/**
	 * @param Closure(MutatingScope): ?FoundPropertyReflection $reflectionResolver
	 * @param PropertyFetch|StaticPropertyFetch $propertyFetch
	 */
	public static function property(?ExpressionResult $innerResult, Closure $reflectionResolver, Expr $propertyFetch): self
	{
		return new self(self::KIND_PROPERTY, innerResult: $innerResult, reflectionResolver: $reflectionResolver, propertyFetch: $propertyFetch);
	}

	/**
	 * Walks the chain once on the asking scope, resolving every link's facts.
	 * $expr is the expression this descriptor belongs to (threaded by
	 * ExpressionResult::getIssetabilityResolution); $useNativeTypes selects native
	 * vs phpdoc types (the rule's treatPhpDocTypesAsCertain).
	 */
	public function resolve(MutatingScope $scope, bool $useNativeTypes, Expr $expr): IssetabilityResolution
	{
		if ($this->kind === self::KIND_VARIABLE) {
			$variableName = $this->variableName;
			if ($variableName === null) {
				throw new ShouldNotHappenException();
			}

			$hasVariable = $scope->hasVariableType($variableName);
			$valueType = $hasVariable->yes()
				? ($useNativeTypes ? $scope->doNotTreatPhpDocTypesAsCertain()->getVariableType($variableName) : $scope->getVariableType($variableName))
				: new NeverType();

			return new IssetabilityResolution(IssetabilityLinkInfo::variable($variableName, $hasVariable, $valueType), null);
		}

		if ($this->kind === self::KIND_OFFSET) {
			$varResult = $this->varResult;
			$dimResult = $this->dimResult;
			if ($varResult === null || $dimResult === null) {
				throw new ShouldNotHappenException();
			}

			$varType = $varResult->getTypeOnScope($scope, $useNativeTypes);
			$dimType = $dimResult->getTypeOnScope($scope, $useNativeTypes);
			$hasOffsetValue = $varType->hasOffsetValueType($dimType);
			$valueType = $hasOffsetValue->no() ? new NeverType() : $varType->getOffsetValueType($dimType);

			return new IssetabilityResolution(
				IssetabilityLinkInfo::offset(
					$varType->isOffsetAccessible(),
					$hasOffsetValue,
					$scope->hasExpressionType($expr)->yes(),
					$varType,
					$dimType,
					$valueType,
				),
				$varResult->getIssetabilityResolution($scope, $useNativeTypes),
			);
		}

		$reflectionResolver = $this->reflectionResolver;
		$propertyFetch = $this->propertyFetch;
		if ($reflectionResolver === null || $propertyFetch === null) {
			throw new ShouldNotHappenException();
		}

		$inner = $this->innerResult !== null ? $this->innerResult->getIssetabilityResolution($scope, $useNativeTypes) : null;

		$propertyReflection = $reflectionResolver($scope);
		if ($propertyReflection === null) {
			return new IssetabilityResolution(
				IssetabilityLinkInfo::property(null, $propertyFetch, false, false, TrinaryLogic::createNo(), new NeverType(), new NeverType(), false, false, false, false, false, false, false, false),
				$inner,
			);
		}

		$hasNativeType = $propertyReflection->hasNativeType();
		$nativeReflection = $propertyReflection->getNativeReflection();
		$initializedThisProperty = $propertyFetch instanceof PropertyFetch
			&& $propertyFetch->name instanceof Identifier
			&& $propertyFetch->var instanceof Variable
			&& $propertyFetch->var->name === 'this'
			&& $scope->hasExpressionType(new PropertyInitializationExpr($propertyReflection->getName()))->yes();

		return new IssetabilityResolution(
			IssetabilityLinkInfo::property(
				$propertyReflection,
				$propertyFetch,
				$propertyReflection->isNative(),
				$hasNativeType,
				$propertyReflection->isVirtual(),
				$propertyReflection->getWritableType(),
				$hasNativeType ? $propertyReflection->getNativeType() : new NeverType(),
				$scope->hasExpressionType($propertyFetch)->yes(),
				isset($scope->getConditionalExpressions()[$scope->getNodeKey($propertyFetch)]),
				$initializedThisProperty,
				$nativeReflection !== null,
				$nativeReflection !== null && $nativeReflection->isPromoted(),
				$nativeReflection !== null && $nativeReflection->isReadOnly(),
				$nativeReflection !== null && $nativeReflection->isHooked(),
				$nativeReflection !== null && $nativeReflection->getNativeReflection()->hasDefaultValue(),
			),
			$inner,
		);
	}

}
