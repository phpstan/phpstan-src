<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use ReflectionClass;

final class ReflectionClassSourceLocator implements SourceLocator
{

	public function __construct(
		private Locator $astLocator,
		private ReflectionSourceStubber $reflectionSourceStubber,
	)
	{
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if (!$identifier->isClass()) {
			return null;
		}

		/** @var class-string $className */
		$className = $identifier->getName();

		$stub = $this->reflectionSourceStubber->generateClassStub($className);
		if ($stub === null) {
			return null;
		}

		$reflection = new ReflectionClass($className);

		return $this->astLocator->findReflection(
			$reflector,
			new LocatedSource($stub->getStub(), $reflection->getName(), null),
			new Identifier($reflection->getName(), new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
		);
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
