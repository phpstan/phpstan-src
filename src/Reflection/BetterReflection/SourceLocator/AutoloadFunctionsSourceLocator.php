<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use function class_exists;
use function interface_exists;
use function PHPStan\autoloadFunctions;
use function trait_exists;

final class AutoloadFunctionsSourceLocator implements SourceLocator
{

	public function __construct(
		private AutoloadSourceLocator $autoloadSourceLocator,
		private ReflectionClassSourceLocator $reflectionClassSourceLocator,
	)
	{
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if (!$identifier->isClass()) {
			return null;
		}

		$className = $identifier->getName();
		if (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false)) {
			return null;
		}

		$autoloadFunctions = autoloadFunctions();
		foreach ($autoloadFunctions as $autoloadFunction) {
			$autoloadFunction($className);
			$reflection = $this->autoloadSourceLocator->locateIdentifier($reflector, $identifier);
			if ($reflection !== null) {
				return $reflection;
			}

			$reflection = $this->reflectionClassSourceLocator->locateIdentifier($reflector, $identifier);
			if ($reflection !== null) {
				return $reflection;
			}
		}

		return null;
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
