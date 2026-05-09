<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use LogicException;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

final class ThrowingSourceLocator implements SourceLocator
{

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		throw new LogicException('SourceLocator::locateIdentifier must not be called during result cache construction');
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		throw new LogicException('SourceLocator::locateIdentifiersByType must not be called during result cache construction');
	}

}
