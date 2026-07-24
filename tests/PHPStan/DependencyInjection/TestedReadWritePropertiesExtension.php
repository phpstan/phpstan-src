<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

final class TestedReadWritePropertiesExtension implements ReadWritePropertiesExtension
{

	public function isAlwaysRead(ExtendedPropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	public function isAlwaysWritten(ExtendedPropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	public function isInitialized(ExtendedPropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

}
