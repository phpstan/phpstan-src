<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

final class LazySourceLocator implements SourceLocator
{

	/** @var callable(): SourceLocator */
	private $sourceLocatorFactory;

	private ?SourceLocator $actualSourceLocator = null;

	/**
	 * @param callable(): SourceLocator $sourceLocatorFactory
	 */
	public function __construct(callable $sourceLocatorFactory)
	{
		$this->sourceLocatorFactory = $sourceLocatorFactory;
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		return $this->getSourceLocator()->locateIdentifier($reflector, $identifier);
	}

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->getSourceLocator()->locateIdentifiersByType($reflector, $identifierType);
	}

	private function getSourceLocator(): SourceLocator
	{
		$factory = $this->sourceLocatorFactory;
		return $this->actualSourceLocator ??= $factory();
	}

}
