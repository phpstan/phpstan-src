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

	private ?SourceLocator $wrappedSourceLocator = null;

	/** @var callable():SourceLocator */
	private $initializer;

	/**
	 * @param callable():SourceLocator $initializer
	 */
	public function __construct(callable $initializer)
	{
		$this->initializer = $initializer;
	}

	private function lazyInitialize(): SourceLocator
	{
		return $this->wrappedSourceLocator ??= ($this->initializer)();
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		return $this->lazyInitialize()->locateIdentifier($reflector, $identifier);
	}

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->lazyInitialize()->locateIdentifiersByType($reflector, $identifierType);
	}

}
