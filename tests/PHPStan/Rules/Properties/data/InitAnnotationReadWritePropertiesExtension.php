<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\PropertyReflection;
use function strpos;

class InitAnnotationReadWritePropertiesExtension implements ReadWritePropertiesExtension
{

	public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
	{
		return $this->isInitialized($property, $propertyName);
	}

	public function isInitialized(PropertyReflection $property, string $propertyName): bool
	{
		return $property->isPublic() &&
			strpos($property->getDocComment() ?? '', '@init') !== false;
	}

}
