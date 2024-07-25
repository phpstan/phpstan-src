<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\Type\Type;

final class EnumUnresolvedPropertyPrototypeReflection implements UnresolvedPropertyPrototypeReflection
{

	public function __construct(private EnumPropertyReflection $property)
	{
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedPropertyPrototypeReflection
	{
		return $this;
	}

	public function getNakedProperty(): PropertyReflection
	{
		return $this->property;
	}

	public function getTransformedProperty(): PropertyReflection
	{
		return $this->property;
	}

	public function withFechedOnType(Type $type): UnresolvedPropertyPrototypeReflection
	{
		return $this;
	}

}
