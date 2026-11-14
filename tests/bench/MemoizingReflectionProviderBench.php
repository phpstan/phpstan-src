<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;

final class MemoizingReflectionProviderBench
{

	private ?ReflectionProvider $reflectionProvider = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$this->reflectionProvider = BenchContainer::create($this->scratch)->getByType(ReflectionProvider::class);
		BenchFixtures::declare($this->scratch, <<<'PHP'
			namespace MemoizingReflectionProviderBench;

			class Warmup {}
			class Member {}
			PHP);
		$this->reflectionProvider->hasClass('MemoizingReflectionProviderBench\Warmup');
		$this->reflectionProvider->getClass('MemoizingReflectionProviderBench\Warmup');
		$this->reflectionProvider->hasClass('MemoizingReflectionProviderBench\Member');
		$this->reflectionProvider->getClass('MemoizingReflectionProviderBench\Member');
	}

	public function benchHasClassAgain(): bool
	{
		return $this->reflectionProvider()->hasClass('MemoizingReflectionProviderBench\Member');
	}

	/**
	 * The memo is case-insensitive since 2.1.34, like class names.
	 */
	public function benchHasClassInOtherCase(): bool
	{
		return $this->reflectionProvider()->hasClass('memoizingReflectionProviderBench\member');
	}

	public function benchGetClassAgain(): ClassReflection
	{
		return $this->reflectionProvider()->getClass('MemoizingReflectionProviderBench\Member');
	}

	private function reflectionProvider(): ReflectionProvider
	{
		if ($this->reflectionProvider === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->reflectionProvider;
	}

}
