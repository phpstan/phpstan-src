<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use function class_exists;
use function function_exists;
use function interface_exists;
use function PHPStan\autoloadFunctions;
use function PHPStan\autoloadFunctionsPrependedToComposer;
use function trait_exists;

final class AutoloadFunctionsSourceLocator implements SourceLocator
{

	/**
	 * @param bool $prependedToComposer When true, consult only the autoloaders
	 *   registered before Composer's class loader (prepended); otherwise consult
	 *   the ones registered after it (appended).
	 */
	public function __construct(
		private AutoloadSourceLocator $autoloadSourceLocator,
		private ReflectionClassSourceLocator $reflectionClassSourceLocator,
		private bool $prependedToComposer = false,
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

		// If the name is already a defined function, this locator must not run the bootstrap
		// autoloaders for it: a catch-all autoloader (e.g. PHP_CodeSniffer's, which falls back to
		// Composer's findFile()) would resolve the name to the function's own file and plain-include
		// it a second time - it was loaded once already, e.g. by a package that ships one function
		// per PSR-4 path and requires it from its bootstrap - fatally redeclaring the function.
		// Returning null only declines this locator; a class and a function may share a name in PHP,
		// and a class that genuinely exists under this name in another file is still located by the
		// later source locators in the chain. See https://github.com/phpstan/phpstan/issues/14988
		if (function_exists($className)) {
			return null;
		}

		$autoloadFunctions = $this->prependedToComposer
			? autoloadFunctionsPrependedToComposer()
			: autoloadFunctions();
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

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
