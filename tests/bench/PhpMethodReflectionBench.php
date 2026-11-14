<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\ScratchDirectory;

final class PhpMethodReflectionBench
{

	private ?ExtendedMethodReflection $method = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$reflectionProvider = BenchContainer::create($this->scratch)->getByType(ReflectionProvider::class);
		BenchFixtures::declare($this->scratch, <<<'PHP'
			namespace PhpMethodReflectionBench;

			class Member
			{

				public function doubleValues(): void
				{
				}

			}
			PHP);
		$this->method = $reflectionProvider->getClass('PhpMethodReflectionBench\Member')->getNativeMethod('doubleValues');
		$this->method->getName();
	}

	#[Expected(instructions: '700', cycles: '5.670K', memory: '0B')]
	public function benchGetNameAgain(): string
	{
		if ($this->method === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->method->getName();
	}

}
