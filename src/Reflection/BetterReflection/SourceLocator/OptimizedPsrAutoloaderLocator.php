<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\PsrAutoloaderMapping;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

class OptimizedPsrAutoloaderLocator implements SourceLocator
{

	private PsrAutoloaderMapping $mapping;

	private \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository;

	public function __construct(
		PsrAutoloaderMapping $mapping,
		OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository
	)
	{
		$this->mapping = $mapping;
		$this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		foreach ($this->mapping->resolvePossibleFilePaths($identifier) as $file) {
			if (!file_exists($file)) {
				continue;
			}

			$reflection = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($file)->locateIdentifier($reflector, $identifier);
			if ($reflection === null) {
				continue;
			}

			return $reflection;
		}

		return null;
	}

	/**
	 * @return Reflection[]
	 */
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
