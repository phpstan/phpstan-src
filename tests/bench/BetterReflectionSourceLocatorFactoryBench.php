<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\BetterReflection\BetterReflectionSourceLocatorFactory;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;

final class BetterReflectionSourceLocatorFactoryBench
{

	private ?BetterReflectionSourceLocatorFactory $factory = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$this->factory = BenchContainer::create($this->scratch)->getByType(BetterReflectionSourceLocatorFactory::class);
	}

	/**
	 * Every process creates the source locator when it boots, also one that never locates a class.
	 */
	public function benchCreate(): SourceLocator
	{
		if ($this->factory === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->factory->create();
	}

}
