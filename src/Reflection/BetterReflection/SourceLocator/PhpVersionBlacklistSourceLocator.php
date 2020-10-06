<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class PhpVersionBlacklistSourceLocator implements SourceLocator
{

	private SourceLocator $sourceLocator;

	private PhpStormStubsSourceStubber $phpStormStubsSourceStubber;

	public function __construct(
		SourceLocator $sourceLocator,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber
	)
	{
		$this->sourceLocator = $sourceLocator;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			if ($this->phpStormStubsSourceStubber->isPresentClass($identifier->getName()) === false) {
				return null;
			}
		}

		if ($identifier->isFunction()) {
			if ($this->phpStormStubsSourceStubber->isPresentFunction($identifier->getName()) === false) {
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
