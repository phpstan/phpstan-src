<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Reflection\Availability\AvailabilityByPhpVersionChecker;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class PhpVersionBlacklistSourceLocator implements SourceLocator
{

	private SourceLocator $sourceLocator;

	private AvailabilityByPhpVersionChecker $availabilityByPhpVersionChecker;

	public function __construct(
		SourceLocator $sourceLocator,
		AvailabilityByPhpVersionChecker $availabilityByPhpVersionChecker
	)
	{
		$this->sourceLocator = $sourceLocator;
		$this->availabilityByPhpVersionChecker = $availabilityByPhpVersionChecker;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			if ($this->availabilityByPhpVersionChecker->isClassAvailable($identifier->getName()) === false) {
				return null;
			}
		}

		if ($identifier->isFunction()) {
			if ($this->availabilityByPhpVersionChecker->isFunctionAvailable($identifier->getName()) === false) {
				return null;
			}
		}

		return $this->sourceLocator->locateIdentifier($reflector, $identifier);
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->sourceLocator->locateIdentifiersByType($reflector, $identifierType);
	}

}
