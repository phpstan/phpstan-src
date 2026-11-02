<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Tags;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

final class TaggedExtension implements ReadWritePropertiesExtension
{

	public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	public function isInitialized(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

}
