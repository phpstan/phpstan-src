<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;
use RecursiveArrayIterator;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;
use function count;

/**
 * Every method call looks up the extensions of the class it is called on, its parents and its
 * interfaces.
 */
final class DynamicReturnTypeExtensionRegistryBench
{

	private const CALLS = 100;

	private ?DynamicReturnTypeExtensionRegistry $registry = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$this->registry = BenchContainer::create($this->scratch)->getByType(DynamicReturnTypeExtensionRegistry::class);
		$this->registry->getDynamicMethodReturnTypeExtensionsForClass(RecursiveArrayIterator::class);
		$this->registry->getDynamicStaticMethodReturnTypeExtensionsForClass(RecursiveArrayIterator::class);
	}

	public function benchMethodExtensionsForClass(): int
	{
		$registry = $this->registry();
		$count = 0;
		for ($i = 0; $i < self::CALLS; $i++) {
			$count += count($registry->getDynamicMethodReturnTypeExtensionsForClass(RecursiveArrayIterator::class));
		}

		return $count;
	}

	public function benchStaticMethodExtensionsForClass(): int
	{
		$registry = $this->registry();
		$count = 0;
		for ($i = 0; $i < self::CALLS; $i++) {
			$count += count($registry->getDynamicStaticMethodReturnTypeExtensionsForClass(RecursiveArrayIterator::class));
		}

		return $count;
	}

	private function registry(): DynamicReturnTypeExtensionRegistry
	{
		if ($this->registry === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->registry;
	}

}
