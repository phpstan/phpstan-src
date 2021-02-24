<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

class SkipClassAliasSourceLocator implements SourceLocator
{

	private SourceLocator $sourceLocator;

	public function __construct(SourceLocator $sourceLocator)
	{
		$this->sourceLocator = $sourceLocator;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$className = $identifier->getName();
			if (!class_exists($className, false)) {
				return $this->sourceLocator->locateIdentifier($reflector, $identifier);
			}

			$reflection = new \ReflectionClass($className);
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
