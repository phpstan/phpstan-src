<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class DumpingSourceLocator implements SourceLocator
{

	/** @var SourceLocator */
	private $nestedSourceLocator;

	public function __construct(SourceLocator $nestedSourceLocator)
	{
		$this->nestedSourceLocator = $nestedSourceLocator;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		var_dump($identifier->getName());

		return $this->nestedSourceLocator->locateIdentifier($reflector, $identifier);
	}

	/**
	 * @param \Roave\BetterReflection\Reflector\Reflector $reflector
	 * @param \Roave\BetterReflection\Identifier\IdentifierType $identifierType
	 * @return Reflection[]
	 */
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		var_dump('by type');
		return $this->nestedSourceLocator->locateIdentifiersByType($reflector, $identifierType);
	}

}
