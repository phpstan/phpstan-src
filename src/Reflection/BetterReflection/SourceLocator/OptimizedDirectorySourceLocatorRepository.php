<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\DependencyInjection\AutowiredService;
use function array_key_exists;

#[AutowiredService]
final class OptimizedDirectorySourceLocatorRepository
{

	/** @var array<string, OptimizedDirectorySourceLocator> */
	private array $locators = [];

	public function __construct(private OptimizedDirectorySourceLocatorFactory $factory)
	{
	}

	/**
	 * With a batch, a locator not created yet is added to it, and cannot be used before the batch
	 * is scanned.
	 */
	public function getOrCreate(string $directory, ?OptimizedDirectorySourceLocatorBatch $batch = null): OptimizedDirectorySourceLocator
	{
		if (array_key_exists($directory, $this->locators)) {
			return $this->locators[$directory];
		}

		$this->locators[$directory] = $batch !== null ? $batch->createByDirectory($directory) : $this->factory->createByDirectory($directory);

		return $this->locators[$directory];
	}

}
