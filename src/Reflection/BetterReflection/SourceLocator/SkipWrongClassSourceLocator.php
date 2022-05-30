<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\ShouldNotHappenException;
use ReflectionClass as CoreReflectionClass;
use function class_exists;
use function interface_exists;
use function str_replace;
use function trait_exists;

class SkipWrongClassSourceLocator implements SourceLocator
{

	public function __construct(private SourceLocator $originalSourceLocator)
	{
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		$reflection = $this->originalSourceLocator->locateIdentifier($reflector, $identifier);
		if (!$identifier->isClass()) {
			return $reflection;
		}
		if ($reflection === null) {
			return null;
		}

		if (
			!class_exists($reflection->getName(), false)
			&& !interface_exists($reflection->getName(), false)
			&& !trait_exists($reflection->getName(), false)
		) {
			return $reflection;
		}

		if (!$reflection instanceof ReflectionClass) {
			throw new ShouldNotHappenException();
		}

		$classReflection = new CoreReflectionClass($reflection->getName());
		if ($classReflection->getFileName() !== false) {
			if ($reflection->getFileName() === null) {
				return null;
			}

			if (str_replace('\\', '/', $classReflection->getFileName()) !== str_replace('\\', '/', $reflection->getFileName())) {
				return null;
			}
			if ($classReflection->getStartLine() !== $reflection->getStartLine()) {
				return null;
			}
		}

		return $reflection;
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->originalSourceLocator->locateIdentifiersByType($reflector, $identifierType);
	}

}
