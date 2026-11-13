<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\Constant;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Linear;
use Tally\ScratchDirectory;
use function count;
use function intdiv;

final class ConstantArrayTypeBench
{

	private ?ConstantArrayType $shape = null;

	private ?ConstantArrayType $sameShape = null;

	private ?ConstantStringType $key = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function keys(): iterable
	{
		foreach ([16, 64, 256] as $keys) {
			yield 'keys-' . $keys => [$keys];
		}
	}

	#[Before]
	public function boot(int $keys): void
	{
		BenchContainer::create($this->scratch);

		// the counted calls do not autoload, and do not hit what the warm-up memoized
		$warmup = self::shape('warmup', 4, true);
		$warmup->getOffsetValueType(new ConstantStringType('warmup1'));
		$warmup->isSuperTypeOf(self::shape('warmup', 4, true));

		$this->shape = self::shape('key', $keys, false);
		// builds the key index
		$this->shape->getOffsetValueType(new ConstantStringType('key0'));
		$this->sameShape = self::shape('key', $keys, false);
		$this->key = new ConstantStringType('key' . intdiv($keys, 2));
	}

	/**
	 * A constant key is looked up in the key index, so the size of the array does not matter.
	 * The index is built on the first lookup, which boot() makes with another key.
	 */
	#[DataProvider(method: 'keys')]
	#[Constant(size: 'keys')]
	public function benchGetOffsetValueType(int $keys): Type
	{
		if ($this->key === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->subject()->getOffsetValueType($this->key);
	}

	#[DataProvider(method: 'keys')]
	#[Linear(size: 'keys')]
	public function benchIsSuperTypeOf(int $keys): bool
	{
		if ($this->sameShape === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->subject()->isSuperTypeOf($this->sameShape)->yes();
	}

	private function subject(): ConstantArrayType
	{
		if ($this->shape === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->shape;
	}

	public static function shape(string $prefix, int $keys, bool $optional): ConstantArrayType
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		for ($i = 0; $i < $keys; $i++) {
			$builder->setOffsetValueType(new ConstantStringType($prefix . $i), new IntegerType(), $optional);
		}

		$arrays = $builder->getArray()->getConstantArrays();
		if (count($arrays) !== 1) {
			throw new LogicException('The builder did not build one constant array.');
		}

		return $arrays[0];
	}

}
