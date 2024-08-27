<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use ReflectionClass as CoreReflectionClass;
use function class_exists;
use function interface_exists;
use function trait_exists;

final class RewriteClassAliasSourceLocator implements SourceLocator
{

	public function __construct(private SourceLocator $originalSourceLocator)
	{
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if (!$identifier->isClass()) {
			return $this->originalSourceLocator->locateIdentifier($reflector, $identifier);
		}

		if (
			class_exists($identifier->getName(), false)
			|| interface_exists($identifier->getName(), false)
			|| trait_exists($identifier->getName(), false)
		) {
			$classReflection = new CoreReflectionClass($identifier->getName());

			return $this->originalSourceLocator->locateIdentifier($reflector, new Identifier($classReflection->getName(), $identifier->getType()));
		}

		return $this->originalSourceLocator->locateIdentifier($reflector, $identifier);
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->originalSourceLocator->locateIdentifiersByType($reflector, $identifierType);
	}

}
