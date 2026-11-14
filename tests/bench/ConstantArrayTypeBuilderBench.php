<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\Attribute\Quadratic;
use Tally\ScratchDirectory;

final class ConstantArrayTypeBuilderBench
{

	/** @var list<ConstantStringType> */
	private array $keys = [];

	/** @var list<ClosureType> */
	private array $closures = [];

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

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function closures(): iterable
	{
		foreach ([16, 64] as $closures) {
			yield 'closures-' . $closures => [$closures];
		}
	}

	#[Before]
	public function boot(int $keys): void
	{
		BenchContainer::create($this->scratch);
		self::build([new ConstantStringType('warmup0'), new ConstantStringType('warmup1')]);
		self::buildList(self::closureList(-4, 4))->getIterableValueType();
		$this->closures = self::closureList(0, $keys);
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
	 * A list of closures that return 0, 1, 2, …, like an array of handlers, and the type of its values.
	 */
	#[DataProvider(method: 'closures')]
	#[ExpectedFor(dataSet: 'closures-16', instructions: '13.03M', cycles: '17.98M', memory: '7.516KiB')]
	#[ExpectedFor(dataSet: 'closures-64', instructions: '62.79M', cycles: '76.63M', memory: '1.507MiB')]
	public function benchListOfClosures(int $closures): Type
	{
		if ($this->closures === []) {
			throw new LogicException('boot() did not run.');
		}

		return self::buildList($this->closures)->getIterableValueType();
	}

	/**
	 * @param list<Type> $values
	 */
	private static function buildList(array $values): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($values as $value) {
			$builder->setOffsetValueType(null, $value);
		}

		return $builder->getArray();
	}

	/**
	 * @return list<ClosureType>
	 */
	private static function closureList(int $from, int $count): array
	{
		$closures = [];
		for ($i = 0; $i < $count; $i++) {
			$closures[] = new ClosureType([], new ConstantIntegerType($from + $i), false);
		}

		return $closures;
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
