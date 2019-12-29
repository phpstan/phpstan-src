<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

class OptimizedSingleFileSourceLocatorRepository
{

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorFactory */
	private $factory;

	/** @var array<string, OptimizedSingleFileSourceLocator> */
	private $locators = [];

	public function __construct(OptimizedSingleFileSourceLocatorFactory $factory)
	{
		$this->factory = $factory;
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
