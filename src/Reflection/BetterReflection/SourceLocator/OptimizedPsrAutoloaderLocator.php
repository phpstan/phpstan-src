<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\PsrAutoloaderMapping;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

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
