<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\PropertyReflection;
use function in_array;

class EntityIdReadWritePropertiesExtension implements ReadWritePropertiesExtension
{

	public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
	{
		return $this->isEntityId($property, $propertyName);
	}

	public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
	{
		return $this->isEntityId($property, $propertyName);
	}

	public function isInitialized(PropertyReflection $property, string $propertyName): bool
	{
		return $this->isEntityId($property, $propertyName);
	}

	private function isEntityId(PropertyReflection $property, string $propertyName): bool
	{
		return $property->getDeclaringClass()->getName() === 'MissingReadOnlyPropertyAssign\\Entity'
			&& in_array($propertyName, ['id'], true);
	}

}
