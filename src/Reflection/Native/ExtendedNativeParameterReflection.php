<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\AllowedConstantsResult;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ParameterAllowedConstants;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

final class ExtendedNativeParameterReflection implements ExtendedParameterReflection
{

	/**
	 * @param list<AttributeReflection> $attributes
	 */
	public function __construct(
		private string $name,
		private bool $optional,
		private Type $type,
		private Type $phpDocType,
		private Type $nativeType,
		private PassedByReference $passedByReference,
		private bool $variadic,
		private ?Type $defaultValue,
		private ?Type $outType,
		private TrinaryLogic $immediatelyInvokedCallable,
		private ?Type $closureThisType,
		private array $attributes,
		private ?ParameterAllowedConstants $allowedConstants,
		private TrinaryLogic $pureUnlessCallableIsImpureParameter,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function isOptional(): bool
	{
		return $this->optional;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function getPhpDocType(): Type
	{
		return $this->phpDocType;
	}

	public function hasNativeType(): bool
	{
		return !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed();
	}

	public function getNativeType(): Type
	{
		return $this->nativeType;
	}

	public function passedByReference(): PassedByReference
	{
		return $this->passedByReference;
	}

	public function isVariadic(): bool
	{
		return $this->variadic;
	}

	public function getDefaultValue(): ?Type
	{
		return $this->defaultValue;
	}

	public function getOutType(): ?Type
	{
		return $this->outType;
	}

	public function isImmediatelyInvokedCallable(): TrinaryLogic
	{
		return $this->immediatelyInvokedCallable;
	}

	public function getClosureThisType(): ?Type
	{
		return $this->closureThisType;
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function getAllowedConstants(): ?ParameterAllowedConstants
	{
		return $this->allowedConstants;
	}

	public function checkAllowedConstants(array $constants): AllowedConstantsResult
	{
		if ($this->allowedConstants === null) {
			return new AllowedConstantsResult([], [], false);
		}

		return $this->allowedConstants->check($constants);
	}

	public function isPureUnlessCallableIsImpureParameter(): TrinaryLogic
	{
		return $this->pureUnlessCallableIsImpureParameter;
	}

}
