<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function array_fill;

final class ConstantArrayTypeAcceptsBench
{

	private ?ConstantArrayType $accepting = null;

	private ?ConstantArrayType $accepted = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function keys(): iterable
	{
		foreach ([16, 128] as $keys) {
			yield 'keys-' . $keys => [$keys];
		}
	}

	#[Before]
	public function boot(int $keys): void
	{
		BenchContainer::create($this->scratch);

		// the counted call does not autoload
		self::shape('warmup', 2, new UnionType([new IntegerType(), new StringType(), new NullType()]))
			->accepts(self::shape('warmup', 2, new UnionType([new IntegerType(), new StringType()])), true);

		// every offset of a shape has the same value type, such as a type alias used for each of them
		$this->accepting = self::shape('key', $keys, new UnionType([new IntegerType(), new StringType(), new NullType()]));
		$this->accepted = self::shape('key', $keys, new UnionType([new IntegerType(), new StringType()]));
	}

	#[DataProvider(method: 'keys')]
	#[ExpectedFor(dataSet: 'keys-16', instructions: '2.879M', cycles: '4.157M', memory: '18.22KiB')]
	#[ExpectedFor(dataSet: 'keys-128', instructions: '22.85M', cycles: '30.77M', memory: '123.5KiB')]
	public function benchAcceptsSharedValueType(int $keys): AcceptsResult
	{
		if ($this->accepting === null || $this->accepted === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->accepting->accepts($this->accepted, true);
	}

	private static function shape(string $prefix, int $keys, Type $valueType): ConstantArrayType
	{
		$keyTypes = [];
		for ($i = 0; $i < $keys; $i++) {
			$keyTypes[] = new ConstantStringType($prefix . $i);
		}

		return new ConstantArrayType($keyTypes, array_fill(0, $keys, $valueType));
	}

}
