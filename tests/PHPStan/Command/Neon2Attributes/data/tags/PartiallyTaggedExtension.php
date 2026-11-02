<?php declare(strict_types = 1);

namespace Neon2AttributesFixtures\Tags;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

final class PartiallyTaggedExtension implements ReadWritePropertiesExtension, \PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtension
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

	public function isAlwaysUsed(\PHPStan\Reflection\ClassConstantReflection $constant): bool
	{
		return false;
	}

}
