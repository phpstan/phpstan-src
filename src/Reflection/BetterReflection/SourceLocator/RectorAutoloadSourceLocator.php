<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use function class_exists;
use function interface_exists;
use function strpos;
use function strtolower;
use function trait_exists;

class RectorAutoloadSourceLocator implements SourceLocator
{

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if (!$identifier->isClass()) {
			return null;
		}

		$className = strtolower($identifier->getName());
		if (strpos($className, 'rectorprefix') === 0 || strpos($className, 'rector\\') === 0) {
			class_exists($identifier->getName()) || interface_exists($identifier->getName()) || trait_exists($identifier->getName());
		}

		return null;
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
