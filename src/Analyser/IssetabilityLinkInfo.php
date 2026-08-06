<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Rules\Properties\FoundPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * One resolved link of an isset/empty/?? chain. IssetabilityDescriptor::resolve()
 * walks the chain once and produces these (the expensive part: types, offset
 * existence, property reflection are resolved here and never again), so the engine
 * (IssetabilityResolution::isSet) and the rule (PHPStan\Rules\IssetCheck) read the
 * facts instead of re-walking and re-resolving.
 */
final class IssetabilityLinkInfo
{

	private const KIND_VARIABLE = 'variable';
	private const KIND_OFFSET = 'offset';
	private const KIND_PROPERTY = 'property';
	private const KIND_LEAF = 'leaf';

	private function __construct(
		private string $kind,
		private ?string $variableName = null,
		private ?TrinaryLogic $hasVariable = null,
		private ?TrinaryLogic $isOffsetAccessible = null,
		private ?TrinaryLogic $hasOffsetValue = null,
		private bool $hasExpressionTypeOfExpr = false,
		private ?Type $varType = null,
		private ?Type $dimType = null,
		private ?Type $valueType = null,
		private ?FoundPropertyReflection $propertyReflection = null,
		private ?Expr $propertyFetch = null,
		private bool $reflectionNative = false,
		private bool $hasNativeType = false,
		private ?TrinaryLogic $isVirtual = null,
		private ?Type $nativeType = null,
		private bool $hasExpressionTypeOfFetch = false,
		private bool $initializedThisProperty = false,
		private bool $nativeReflectionExists = false,
		private bool $nativeIsPromoted = false,
		private bool $nativeIsReadOnly = false,
		private bool $nativeIsHooked = false,
		private bool $nativeHasDefaultValue = false,
		private ?Expr $leafExpr = null,
		private bool $leafIsNullsafePropertyFetch = false,
	)
	{
	}

	public static function variable(string $variableName, TrinaryLogic $hasVariable, Type $valueType): self
	{
		return new self(self::KIND_VARIABLE, variableName: $variableName, hasVariable: $hasVariable, valueType: $valueType);
	}

	public static function offset(TrinaryLogic $isOffsetAccessible, TrinaryLogic $hasOffsetValue, bool $hasExpressionTypeOfExpr, Type $varType, Type $dimType, Type $valueType): self
	{
		return new self(
			self::KIND_OFFSET,
			isOffsetAccessible: $isOffsetAccessible,
			hasOffsetValue: $hasOffsetValue,
			hasExpressionTypeOfExpr: $hasExpressionTypeOfExpr,
			varType: $varType,
			dimType: $dimType,
			valueType: $valueType,
		);
	}

	public static function property(
		?FoundPropertyReflection $propertyReflection,
		Expr $propertyFetch,
		bool $reflectionNative,
		bool $hasNativeType,
		TrinaryLogic $isVirtual,
		Type $writableType,
		Type $nativeType,
		bool $hasExpressionTypeOfFetch,
		bool $initializedThisProperty,
		bool $nativeReflectionExists,
		bool $nativeIsPromoted,
		bool $nativeIsReadOnly,
		bool $nativeIsHooked,
		bool $nativeHasDefaultValue,
	): self
	{
		return new self(
			self::KIND_PROPERTY,
			valueType: $writableType,
			propertyReflection: $propertyReflection,
			propertyFetch: $propertyFetch,
			reflectionNative: $reflectionNative,
			hasNativeType: $hasNativeType,
			isVirtual: $isVirtual,
			nativeType: $nativeType,
			hasExpressionTypeOfFetch: $hasExpressionTypeOfFetch,
			initializedThisProperty: $initializedThisProperty,
			nativeReflectionExists: $nativeReflectionExists,
			nativeIsPromoted: $nativeIsPromoted,
			nativeIsReadOnly: $nativeIsReadOnly,
			nativeIsHooked: $nativeIsHooked,
			nativeHasDefaultValue: $nativeHasDefaultValue,
		);
	}

	public static function leaf(Type $valueType, Expr $leafExpr, bool $leafIsNullsafePropertyFetch): self
	{
		return new self(self::KIND_LEAF, valueType: $valueType, leafExpr: $leafExpr, leafIsNullsafePropertyFetch: $leafIsNullsafePropertyFetch);
	}

	public function isVariable(): bool
	{
		return $this->kind === self::KIND_VARIABLE;
	}

	public function isOffset(): bool
	{
		return $this->kind === self::KIND_OFFSET;
	}

	public function isProperty(): bool
	{
		return $this->kind === self::KIND_PROPERTY;
	}

	public function getVariableName(): string
	{
		if ($this->variableName === null) {
			throw new ShouldNotHappenException();
		}

		return $this->variableName;
	}

	public function getHasVariable(): TrinaryLogic
	{
		if ($this->hasVariable === null) {
			throw new ShouldNotHappenException();
		}

		return $this->hasVariable;
	}

	/** The type the operator's callback inspects: variable type, offset value type, property writable type, or leaf type. */
	public function getValueType(): Type
	{
		if ($this->valueType === null) {
			throw new ShouldNotHappenException();
		}

		return $this->valueType;
	}

	public function getIsOffsetAccessible(): TrinaryLogic
	{
		if ($this->isOffsetAccessible === null) {
			throw new ShouldNotHappenException();
		}

		return $this->isOffsetAccessible;
	}

	public function getHasOffsetValue(): TrinaryLogic
	{
		if ($this->hasOffsetValue === null) {
			throw new ShouldNotHappenException();
		}

		return $this->hasOffsetValue;
	}

	public function hasExpressionTypeOfExpr(): bool
	{
		return $this->hasExpressionTypeOfExpr;
	}

	public function getVarType(): Type
	{
		if ($this->varType === null) {
			throw new ShouldNotHappenException();
		}

		return $this->varType;
	}

	public function getDimType(): Type
	{
		if ($this->dimType === null) {
			throw new ShouldNotHappenException();
		}

		return $this->dimType;
	}

	public function getPropertyReflection(): ?FoundPropertyReflection
	{
		return $this->propertyReflection;
	}

	/**
	 * @return Expr\PropertyFetch|Expr\StaticPropertyFetch
	 */
	public function getPropertyFetch(): Expr
	{
		if (!$this->propertyFetch instanceof Expr\PropertyFetch && !$this->propertyFetch instanceof Expr\StaticPropertyFetch) {
			throw new ShouldNotHappenException();
		}

		return $this->propertyFetch;
	}

	public function isReflectionNative(): bool
	{
		return $this->reflectionNative;
	}

	public function hasNativeType(): bool
	{
		return $this->hasNativeType;
	}

	public function isVirtual(): TrinaryLogic
	{
		if ($this->isVirtual === null) {
			throw new ShouldNotHappenException();
		}

		return $this->isVirtual;
	}

	public function getNativeType(): Type
	{
		if ($this->nativeType === null) {
			throw new ShouldNotHappenException();
		}

		return $this->nativeType;
	}

	public function hasExpressionTypeOfFetch(): bool
	{
		return $this->hasExpressionTypeOfFetch;
	}

	public function isInitializedThisProperty(): bool
	{
		return $this->initializedThisProperty;
	}

	public function nativeReflectionExists(): bool
	{
		return $this->nativeReflectionExists;
	}

	public function nativeIsPromoted(): bool
	{
		return $this->nativeIsPromoted;
	}

	public function nativeIsReadOnly(): bool
	{
		return $this->nativeIsReadOnly;
	}

	public function nativeIsHooked(): bool
	{
		return $this->nativeIsHooked;
	}

	public function nativeHasDefaultValue(): bool
	{
		return $this->nativeHasDefaultValue;
	}

	public function getLeafExpr(): Expr
	{
		if ($this->leafExpr === null) {
			throw new ShouldNotHappenException();
		}

		return $this->leafExpr;
	}

	public function leafIsNullsafePropertyFetch(): bool
	{
		return $this->leafIsNullsafePropertyFetch;
	}

}
