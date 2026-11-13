<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Constant\ConstantArrayType;
use Tally\Attribute\Before;
use Tally\Attribute\Complexity;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;
use function count;

final class ConstantArrayTypeGetAllArraysBench
{

	private ?ConstantArrayType $shape = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function optionalKeys(): iterable
	{
		foreach ([4, 6, 8, 10] as $keys) {
			yield 'optional-keys-' . $keys => [$keys];
		}
	}

	#[Before]
	public function boot(int $keys): void
	{
		BenchContainer::create($this->scratch);
		ConstantArrayTypeBench::shape('warmup', 4, true)->getAllArrays();
		$this->shape = ConstantArrayTypeBench::shape('optional', $keys, true);
	}

	/**
	 * Each optional key doubles the arrays, and each array has up to all the keys.
	 */
	#[DataProvider(method: 'optionalKeys')]
	#[Complexity(size: 'keys', formula: 'n * 2^n')]
	public function benchGetAllArrays(int $keys): int
	{
		if ($this->shape === null) {
			throw new LogicException('boot() did not run.');
		}

		return count($this->shape->getAllArrays());
	}

}
