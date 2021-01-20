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

	private \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory $optimizedDirectorySourceLocatorFactory;

	/** @var array<string, OptimizedDirectorySourceLocator|null> */
	private array $locators = [];

	public function __construct(
		PsrAutoloaderMapping $mapping,
		OptimizedDirectorySourceLocatorFactory $optimizedDirectorySourceLocatorFactory
	)
	{
		$this->mapping = $mapping;
		$this->optimizedDirectorySourceLocatorFactory = $optimizedDirectorySourceLocatorFactory;
	}

	private function getLocator(Identifier $identifier): ?OptimizedDirectorySourceLocator
	{
		if (!array_key_exists($identifier->getName(), $this->locators)) {
			$files = [];
			foreach ($this->mapping->resolvePossibleFilePaths($identifier) as $file) {
				if (!is_file($file)) {
					continue;
				}

				$files[] = $file;
			}

			if (count($files) > 0) {
				$this->locators[$identifier->getName()] = $this->optimizedDirectorySourceLocatorFactory->createByFiles($files);
			} else {
				$this->locators[$identifier->getName()] = null;
			}
		}

		return $this->locators[$identifier->getName()];
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if (!$identifier->isClass()) {
			return null;
		}

		$locator = $this->getLocator($identifier);
		if ($locator === null) {
			return null;
		}

		return $locator->locateIdentifier($reflector, $identifier);
	}

	/**
	 * @return Reflection[]
	 */
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
