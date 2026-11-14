<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;

final class ArrayTypeBench
{

	private const CALLS = 1000;

	private ?ArrayType $array = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);

		$this->array = new ArrayType(new IntegerType(), new MixedType());
		$this->array->isList();
		new ArrayType(IntegerRangeType::fromInterval(0, 100), new MixedType());
	}

	/**
	 * array<int, mixed> may be a list, which takes two comparisons of its key type to find out.
	 */
	public function benchIsList(): TrinaryLogic
	{
		$result = TrinaryLogic::createNo();
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->array()->isList();
		}

		return $result;
	}

	/**
	 * The constructor turns a benevolent (int|string) key into mixed, which other key types do not
	 * need to be described for.
	 */
	public function benchConstruct(): Type
	{
		$keyType = IntegerRangeType::fromInterval(0, 100);
		$itemType = new MixedType();
		$array = $this->array();
		for ($i = 0; $i < self::CALLS; $i++) {
			$array = new ArrayType($keyType, $itemType);
		}

		return $array;
	}

	private function array(): ArrayType
	{
		if ($this->array === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->array;
	}

}
