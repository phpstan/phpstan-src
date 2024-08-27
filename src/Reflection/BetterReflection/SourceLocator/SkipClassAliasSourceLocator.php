<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use ReflectionClass;
use function class_exists;

final class SkipClassAliasSourceLocator implements SourceLocator
{

	public function __construct(private SourceLocator $sourceLocator)
	{
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$className = $identifier->getName();
			if (!class_exists($className, false)) {
				return $this->sourceLocator->locateIdentifier($reflector, $identifier);
			}

			$reflection = new ReflectionClass($className);
			if ($reflection->getName() === 'ReturnTypeWillChange') {
				return $this->sourceLocator->locateIdentifier($reflector, $identifier);
			}
			if ($reflection->getFileName() === false) {
				return $this->sourceLocator->locateIdentifier($reflector, $identifier);
			}

			return null;
		}

		return $this->sourceLocator->locateIdentifier($reflector, $identifier);
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->sourceLocator->locateIdentifiersByType($reflector, $identifierType);
	}

}
