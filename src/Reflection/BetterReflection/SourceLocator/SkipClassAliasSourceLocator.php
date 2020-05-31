<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

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
