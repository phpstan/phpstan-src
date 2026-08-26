<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AutowiredServices;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

#[AutowiredService]
final class TestedDiscoveredReadWritePropertiesExtension implements ReadWritePropertiesExtension
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
