<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\Type\IntersectionTypeMethodReflection;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function array_slice;
use function sprintf;

final class IntersectionTypeMethodReflectionBench
{

	private ?IntersectionTypeMethodReflection $method = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function methods(): iterable
	{
		foreach ([2, 8] as $methods) {
			yield 'methods-' . $methods => [$methods];
		}
	}

	#[Before]
	public function boot(int $methods): void
	{
		$reflectionProvider = BenchContainer::create($this->scratch)->getByType(ReflectionProvider::class);
		$code = "namespace IntersectionTypeMethodReflectionBench;\n\n";
		for ($i = 0; $i < 8; $i++) {
			$code .= sprintf("interface I%d\n{\n\n\t/** @return list<int> */\n\tpublic function values(): array;\n\n}\n\n", $i);
		}
		BenchFixtures::declare($this->scratch, $code);

		$reflections = [];
		for ($i = 0; $i < 8; $i++) {
			$reflections[] = $reflectionProvider->getClass('IntersectionTypeMethodReflectionBench\I' . $i)->getNativeMethod('values');
			$reflections[$i]->getVariants();
		}
		(new IntersectionTypeMethodReflection('values', [$reflections[6], $reflections[7]]))->getVariants();

		$this->method = new IntersectionTypeMethodReflection('values', array_slice($reflections, 0, $methods));
	}

	/**
	 * The method of an intersection of interfaces that all declare it, such as `I0&I1`.
	 *
	 * @return list<ExtendedParametersAcceptor>
	 */
	#[DataProvider(method: 'methods')]
	#[ExpectedFor(dataSet: 'methods-2', instructions: '186.4K', cycles: '493.4K', memory: '5.094KiB')]
	#[ExpectedFor(dataSet: 'methods-8', instructions: '1.070M', cycles: '1.642M', memory: '7.250KiB')]
	public function benchGetVariants(int $methods): array
	{
		if ($this->method === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->method->getVariants();
	}

}
