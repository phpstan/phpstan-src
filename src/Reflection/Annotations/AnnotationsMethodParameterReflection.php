<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\AllowedConstantsResult;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ParameterAllowedConstants;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

final class AnnotationsMethodParameterReflection implements ExtendedParameterReflection
{

	public function __construct(private string $name, private Type $type, private PassedByReference $passedByReference, private bool $isOptional, private bool $isVariadic, private ?Type $defaultValue)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function isOptional(): bool
	{
		return $this->isOptional;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function getPhpDocType(): Type
	{
		return $this->type;
	}

	public function hasNativeType(): bool
	{
		return false;
	}

	public function getNativeType(): Type
	{
		return new MixedType();
	}

	public function getOutType(): ?Type
	{
		return null;
	}

	public function isImmediatelyInvokedCallable(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getClosureThisType(): ?Type
	{
		return null;
	}

	public function passedByReference(): PassedByReference
	{
		return $this->passedByReference;
	}

	public function isVariadic(): bool
	{
		return $this->isVariadic;
	}

	public function getDefaultValue(): ?Type
	{
		return $this->defaultValue;
	}

	public function getAttributes(): array
	{
		return [];
	}

	public function getAllowedConstants(): ?ParameterAllowedConstants
	{
		return null;
	}

	public function checkAllowedConstants(array $constants): AllowedConstantsResult
	{
		return new AllowedConstantsResult([], [], false);
	}

	public function isPureUnlessCallableIsImpureParameter(): bool
	{
		return false;
	}

}
