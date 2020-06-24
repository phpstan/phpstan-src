<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Nette\Utils\Strings;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class ClassBlacklistSourceLocator implements SourceLocator
{

	private SourceLocator $sourceLocator;

	/** @var string[] */
	private array $patterns;

	/**
	 * @param SourceLocator $sourceLocator
	 * @param string[] $patterns
	 */
	public function __construct(
		SourceLocator $sourceLocator,
		array $patterns
	)
	{
		$this->sourceLocator = $sourceLocator;
		$this->patterns = $patterns;
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
