<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

final class PhpVersionBlacklistSourceLocator implements SourceLocator
{

	public function __construct(
		private SourceLocator $sourceLocator,
		private PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
	)
	{
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
