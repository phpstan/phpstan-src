<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;
use function sprintf;

final class PhpDocInheritanceResolverBench
{

	private ?ClassReflection $class = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function depths(): iterable
	{
		foreach ([4, 16] as $depth) {
			yield 'depth-' . $depth => [$depth];
		}
	}

	#[Before]
	public function boot(int $depth): void
	{
		$reflectionProvider = BenchContainer::create($this->scratch)->getByType(ReflectionProvider::class);
		foreach ([2, $depth] as $chain) {
			BenchFixtures::declare($this->scratch, self::chain($chain));
		}

		$warmup = $reflectionProvider->getClass('PhpDocInheritanceResolverBench\Chain2\Level2');
		$warmup->getAncestors();
		$warmup->getNativeMethod('values')->getVariants()[0]->getReturnType();

		// the class reflections of the chain, without their methods
		$this->class = $reflectionProvider->getClass(sprintf('PhpDocInheritanceResolverBench\Chain%d\Level%d', $depth, $depth));
		$this->class->getAncestors();
	}

	/**
	 * A method overridden on every level of a class hierarchy, documented only at its root, inherits
	 * the PHPDoc of the root.
	 */
	#[DataProvider(method: 'depths')]
	public function benchInheritedReturnType(int $depth): Type
	{
		if ($this->class === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->class->getNativeMethod('values')->getVariants()[0]->getReturnType();
	}

	private static function chain(int $depth): string
	{
		$code = sprintf(<<<'PHP'
			namespace PhpDocInheritanceResolverBench\Chain%d;

			class Level0
			{

				/**
				 * @param list<int> $values
				 * @return list<int>
				 */
				public function values(array $values): array
				{
					return $values;
				}

			}

			PHP, $depth);
		for ($level = 1; $level <= $depth; $level++) {
			$code .= sprintf("class Level%d extends Level%d\n{\n\n\tpublic function values(array \$values): array\n\t{\n\t\treturn \$values;\n\t}\n\n}\n\n", $level, $level - 1);
		}

		return $code;
	}

}
