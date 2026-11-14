<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Expected;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function implode;
use function sprintf;

final class ClassReflectionBench
{

	private ?ReflectionProvider $reflectionProvider = null;

	private ?ClassReflection $class = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function depths(): iterable
	{
		foreach ([3, 6, 9] as $depth) {
			yield 'depth-' . $depth => [$depth];
		}
	}

	#[Before]
	public function boot(): void
	{
		$this->reflectionProvider = BenchContainer::create($this->scratch)->getByType(ReflectionProvider::class);
		BenchFixtures::declare($this->scratch, <<<'PHP'
			namespace ClassReflectionBench;

			class Member
			{

				/** @var list<int> */
				public array $values = [];

				/**
				 * @param list<int> $values
				 * @return list<int>
				 */
				public function doubled(array $values): array
				{
					return $values;
				}

			}
			PHP);
		$this->class = $this->reflectionProvider->getClass('ClassReflectionBench\Member');
		$scope = new OutOfClassScope();
		$this->class->getMethod('doubled', $scope);
		$this->class->getProperty('values', $scope); // @phpstan-ignore method.deprecated (the method the cache early return is in)
		$this->class->getName();
	}

	/**
	 * Asking again for a method answers from the cache filled by the first call.
	 */
	#[Expected(instructions: '2.194K', cycles: '28.37K', memory: '40B')]
	public function benchGetMethodAgain(): ExtendedMethodReflection
	{
		return $this->class()->getMethod('doubled', new OutOfClassScope());
	}

	#[Expected(instructions: '2.515K', cycles: '32.66K', memory: '40B')]
	public function benchGetPropertyAgain(): ExtendedPropertyReflection
	{
		// @phpstan-ignore method.deprecated (the method the cache early return is in)
		return $this->class()->getProperty('values', new OutOfClassScope());
	}

	/**
	 * The hottest call on the class, memoized since 2.2.14.
	 */
	#[Expected(instructions: '1.016K', cycles: '13.78K', memory: '0B')]
	public function benchGetName(): string
	{
		return $this->class()->getName();
	}

	/**
	 * A class implementing a lattice of interfaces in which each interface extends both interfaces
	 * of the level below, so walking it recursively visits the lower levels exponentially often.
	 *
	 * @return array<string, ClassReflection>
	 */
	#[DataProvider(method: 'depths')]
	#[ExpectedFor(dataSet: 'depth-3', instructions: '2.531M', cycles: '3.906M', memory: '35.68KiB')]
	#[ExpectedFor(dataSet: 'depth-6', instructions: '10.28M', cycles: '12.70M', memory: '66.72KiB')]
	#[ExpectedFor(dataSet: 'depth-9', instructions: '55.29M', cycles: '61.48M', memory: '115.8KiB')]
	public function benchGetInterfacesOfLattice(int $depth): array
	{
		$class = $this->reflectionProvider()->getClass(sprintf('ClassReflectionBench\Lattice%d\Implementation', $depth));

		return $class->getInterfaces();
	}

	#[Before]
	public function declareLattices(): void
	{
		foreach ([2, 3, 6, 9] as $depth) {
			BenchFixtures::declare($this->scratch, self::lattice($depth));
		}

		// autoloads and warms up getInterfaces() on a lattice no case uses
		$this->reflectionProvider()->getClass('ClassReflectionBench\Lattice2\Implementation')->getInterfaces();
		foreach ([3, 6, 9] as $depth) {
			$this->reflectionProvider()->getClass(sprintf('ClassReflectionBench\Lattice%d\Implementation', $depth));
		}
	}

	private static function lattice(int $depth): string
	{
		$code = sprintf("namespace ClassReflectionBench\\Lattice%d;\n\ninterface A0 {}\ninterface B0 {}\n", $depth);
		for ($level = 1; $level <= $depth; $level++) {
			$below = sprintf('A%d, B%d', $level - 1, $level - 1);
			$code .= sprintf("interface A%d extends %s {}\ninterface B%d extends %s {}\n", $level, $below, $level, $below);
		}

		return $code . sprintf("class Implementation implements %s {}\n", implode(', ', ['A' . $depth, 'B' . $depth]));
	}

	private function class(): ClassReflection
	{
		if ($this->class === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->class;
	}

	private function reflectionProvider(): ReflectionProvider
	{
		if ($this->reflectionProvider === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->reflectionProvider;
	}

}
