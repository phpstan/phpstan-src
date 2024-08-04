<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ExtendedPropertyReflection;
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

	public function getNakedProperty(): ExtendedPropertyReflection
	{
		return $this->property;
	}

	public function getTransformedProperty(): ExtendedPropertyReflection
	{
		return $this->property;
	}

	public function withFechedOnType(Type $type): UnresolvedPropertyPrototypeReflection
	{
		return $this;
	}

}
