<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;

/**
 * Whether array<int, mixed> is a list, asked a thousand times.
 */
final class AccessoryArrayListTypeBench
{

	private const CALLS = 1000;

	private ?ArrayType $array = null;

	private ?AccessoryArrayListType $list = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);

		$this->array = new ArrayType(new IntegerType(), new MixedType());
		$this->list = new AccessoryArrayListType();
		$this->list->isSuperTypeOf($this->array);
		$this->list->accepts($this->array, true);
	}

	public function benchIsSuperTypeOf(): IsSuperTypeOfResult
	{
		[$list, $array] = $this->types();
		$result = IsSuperTypeOfResult::createNo();
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $list->isSuperTypeOf($array);
		}

		return $result;
	}

	public function benchAccepts(): bool
	{
		[$list, $array] = $this->types();
		$result = false;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $list->accepts($array, true)->yes();
		}

		return $result;
	}

	/**
	 * @return array{AccessoryArrayListType, ArrayType}
	 */
	private function types(): array
	{
		if ($this->list === null || $this->array === null) {
			throw new LogicException('boot() did not run.');
		}

		return [$this->list, $this->array];
	}

}
