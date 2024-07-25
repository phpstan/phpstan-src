<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use function array_key_exists;

final class OptimizedSingleFileSourceLocatorRepository
{

	/** @var array<string, OptimizedSingleFileSourceLocator> */
	private array $locators = [];

	public function __construct(private OptimizedSingleFileSourceLocatorFactory $factory)
	{
	}

	public function getOrCreate(string $fileName): OptimizedSingleFileSourceLocator
	{
		if (array_key_exists($fileName, $this->locators)) {
			return $this->locators[$fileName];
		}

		$this->locators[$fileName] = $this->factory->create($fileName);

		return $this->locators[$fileName];
	}

}
