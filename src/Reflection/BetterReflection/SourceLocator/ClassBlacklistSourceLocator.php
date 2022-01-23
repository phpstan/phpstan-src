<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Nette\Utils\Strings;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

class ClassBlacklistSourceLocator implements SourceLocator
{

	/**
	 * @param string[] $patterns
	 */
	public function __construct(
		private SourceLocator $sourceLocator,
		private array $patterns,
	)
	{
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			foreach ($this->patterns as $pattern) {
				if (Strings::match($identifier->getName(), $pattern) !== null) {
					return null;
				}
			}
		}

		return $this->sourceLocator->locateIdentifier($reflector, $identifier);
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->sourceLocator->locateIdentifiersByType($reflector, $identifierType);
	}

}
