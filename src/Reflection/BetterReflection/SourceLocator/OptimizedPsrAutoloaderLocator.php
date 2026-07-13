<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\PsrAutoloaderMapping;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\Reflection\ConstantNameHelper;
use function array_keys;
use function is_file;
use function strtolower;

#[GenerateFactory(interface: OptimizedPsrAutoloaderLocatorFactory::class)]
final class OptimizedPsrAutoloaderLocator implements SourceLocator
{

	/**
	 * Symbol → the first locator known to declare it, harvested from each
	 * located file's present symbols. Replaces a linear sweep over all known
	 * locators per lookup (measured: 286k sweep calls for a single hit on a
	 * partial self-analysis) with one hash access.
	 *
	 * @var array<string, OptimizedSingleFileSourceLocator>
	 */
	private array $classIndex = [];

	/** @var array<string, OptimizedSingleFileSourceLocator> */
	private array $functionIndex = [];

	/** @var array<string, OptimizedSingleFileSourceLocator> */
	private array $constantIndex = [];

	public function __construct(
		private PsrAutoloaderMapping $mapping,
		private OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
	)
	{
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		$indexedLocator = $this->findInIndex($identifier);
		if ($indexedLocator !== null) {
			$reflection = $indexedLocator->locateIdentifier($reflector, $identifier);
			if ($reflection !== null) {
				return $reflection;
			}
		}

		foreach ($this->mapping->resolvePossibleFilePaths($identifier) as $file) {
			if (!is_file($file)) {
				continue;
			}

			$locator = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($file);
			$reflection = $locator->locateIdentifier($reflector, $identifier);
			if ($reflection === null) {
				continue;
			}

			$this->addToIndex($locator);

			return $reflection;
		}

		return null;
	}

	private function findInIndex(Identifier $identifier): ?OptimizedSingleFileSourceLocator
	{
		if ($identifier->isClass()) {
			return $this->classIndex[strtolower($identifier->getName())] ?? null;
		}
		if ($identifier->isFunction()) {
			return $this->functionIndex[strtolower($identifier->getName())] ?? null;
		}
		if ($identifier->isConstant()) {
			return $this->constantIndex[ConstantNameHelper::normalize($identifier->getName())] ?? null;
		}

		return null;
	}

	private function addToIndex(OptimizedSingleFileSourceLocator $locator): void
	{
		$presentSymbols = $locator->getPresentSymbols();
		foreach (array_keys($presentSymbols['classes']) as $className) {
			$this->classIndex[$className] ??= $locator;
		}
		foreach (array_keys($presentSymbols['functions']) as $functionName) {
			$this->functionIndex[$functionName] ??= $locator;
		}
		foreach (array_keys($presentSymbols['constants']) as $constantName) {
			$this->constantIndex[$constantName] ??= $locator;
		}
	}

	/**
	 * @return list<Reflection>
	 */
	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
