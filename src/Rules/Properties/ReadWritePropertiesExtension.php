<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\PropertyReflection;

/** @api */
interface ReadWritePropertiesExtension
{

	public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool;

	public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool;

	public function isInitialized(PropertyReflection $property, string $propertyName): bool;

}
