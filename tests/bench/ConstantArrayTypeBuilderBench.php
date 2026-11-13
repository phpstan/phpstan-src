<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Quadratic;
use Tally\ScratchDirectory;

final class ConstantArrayTypeBuilderBench
{

	/** @var list<ConstantStringType> */
	private array $keys = [];

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
		self::build([new ConstantStringType('warmup0'), new ConstantStringType('warmup1')]);
		for ($i = 0; $i < $keys; $i++) {
			$this->keys[] = new ConstantStringType('key' . $i);
		}
	}

	/**
	 * Setting a new key compares it with every key set before, so building an array key by key
	 * is quadratic in its size.
	 */
	#[DataProvider(method: 'keys')]
	#[Quadratic(size: 'keys')]
	public function benchSetOffsetValueType(int $keys): Type
	{
		if ($this->keys === []) {
			throw new LogicException('boot() did not run.');
		}

		return self::build($this->keys);
	}

	/**
	 * @param list<ConstantStringType> $keys
	 */
	private static function build(array $keys): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($keys as $key) {
			$builder->setOffsetValueType($key, new IntegerType());
		}

		return $builder->getArray();
	}

}
