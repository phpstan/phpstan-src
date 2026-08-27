<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use Throwable;
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

	#[Override]
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
			try {
				$autoloadFunction($className);
			} catch (Throwable) {
				// This locator asks every bootstrap-registered autoloader for the class, which is not
				// the order PHP uses: at runtime the class loader that resolves the class first is
				// often another one, so an autoloader that throws for names outside its own scope is
				// never invoked for them and its exception cannot happen. Letting it propagate here
				// aborts the analysis of the file with an internal error instead, so it is swallowed
				// and the remaining autoloaders and source locators get their turn. The class may
				// still have been defined before the throw, so the locators below run either way.
				// See https://github.com/phpstan/phpstan/issues/14976
			}

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

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
