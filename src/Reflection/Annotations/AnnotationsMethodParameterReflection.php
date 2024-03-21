<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

class AnnotationsMethodParameterReflection implements ParameterReflectionWithPhpDocs
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

}
