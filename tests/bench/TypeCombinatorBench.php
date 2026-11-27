<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use stdClass;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\Attribute\Linear;
use Tally\Attribute\Linearithmic;
use Tally\ScratchDirectory;

final class TypeCombinatorBench
{

	/** @var list<ConstantStringType> */
	private array $strings = [];

	/** @var list<ConstantIntegerType> */
	private array $integers = [];

	/** @var list<ConstantArrayType> */
	private array $arrays = [];

	/** @var list<Type> */
	private array $unions = [];

	/** @var list<ConstantArrayType> */
	private array $taggedArrays = [];

	/** @var list<Type> */
	private array $benevolentUnions = [];

	/** @var list<Type> */
	private array $generalScalars = [];

	/** @var list<Type> */
	private array $numericStringAndStrings = [];

	/** @var list<Type> */
	private array $arraysWithSameKeys = [];

	/** @var list<Type> */
	private array $arraysWithOwnKeys = [];

	/** @var list<Type> */
	private array $intersections = [];

	/** @var list<Type> */
	private array $stringsAndNevers = [];

	/** @var list<Type> */
	private array $shapesDifferingAtOneKey = [];

	/** @var list<Type> */
	private array $listsOfGrowingLength = [];

	/** @var list<Type> */
	private array $offsetValueIntersections = [];

	/** @var list<Type> */
	private array $nonEmptyStringAndStrings = [];

	/** @var list<Type> */
	private array $disjointRanges = [];

	/** @var list<Type> */
	private array $touchingRangesInReverse = [];

	/** @var list<Type> */
	private array $integersAndRanges = [];

	/** @var list<Type> */
	private array $rangeUnions = [];

	/** @var list<Type> */
	private array $rangesAndConstants = [];

	private ?Type $enumOrNull = null;

	private ?UnionType $enumCases = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function types(): iterable
	{
		foreach ([16, 64, 256] as $types) {
			yield 'types-' . $types => [$types];
		}
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function smallest(): iterable
	{
		yield 'types-16' => [16];
	}

	#[Before]
	public function boot(int $types): void
	{
		BenchContainer::create($this->scratch);

		// TypeCombinator memoizes its results, so the warm-up uses other types than the benchmarks
		$this->createTypes('warmup', 4);
		TypeCombinator::union(...$this->strings);
		TypeCombinator::union(...$this->integers);
		TypeCombinator::union(...$this->arrays);
		TypeCombinator::intersect(...$this->unions);
		TypeCombinator::remove(...$this->unions);
		TypeCombinator::union(...$this->taggedArrays);
		TypeCombinator::intersect(...$this->benevolentUnions);
		TypeCombinator::union(...$this->generalScalars);
		TypeCombinator::union(...$this->numericStringAndStrings);
		TypeCombinator::union(...$this->arraysWithSameKeys);
		TypeCombinator::union(...$this->arraysWithOwnKeys);
		TypeCombinator::intersect(...$this->intersections);
		TypeCombinator::union(...$this->stringsAndNevers);
		TypeCombinator::union(...$this->shapesDifferingAtOneKey);
		TypeCombinator::union(...$this->listsOfGrowingLength);
		TypeCombinator::union(...$this->offsetValueIntersections);
		TypeCombinator::union(...$this->nonEmptyStringAndStrings);
		TypeCombinator::union(...$this->disjointRanges);
		TypeCombinator::union(...$this->touchingRangesInReverse);
		TypeCombinator::union(...$this->integersAndRanges);
		TypeCombinator::intersect(...$this->rangeUnions);
		TypeCombinator::remove(...$this->rangesAndConstants);
		TypeCombinator::intersect(new StringType(), new AccessoryNonEmptyStringType(), new AccessoryNumericStringType(), new AccessoryLowercaseStringType());
		$warmupEnum = BenchEnumFile::create($this->scratch, 'Warmup4', 4);
		TypeCombinator::remove(new UnionType([new ObjectType($warmupEnum), new NullType()]), new UnionType(BenchEnumFile::cases($warmupEnum, 4)));

		$this->createTypes('type', $types);
		$enum = BenchEnumFile::create($this->scratch, 'Cases' . $types, $types);
		$this->enumOrNull = new UnionType([new ObjectType($enum), new NullType()]);
		$this->enumCases = new UnionType(BenchEnumFile::cases($enum, $types));
	}

	#[DataProvider(method: 'types')]
	#[Linear(size: 'types')]
	public function benchUnionConstantStrings(int $types): Type
	{
		return TypeCombinator::union(...$this->strings);
	}

	#[DataProvider(method: 'types')]
	#[Linear(size: 'types')]
	public function benchUnionConstantIntegers(int $types): Type
	{
		return TypeCombinator::union(...$this->integers);
	}

	/**
	 * Each array is compared with the arrays already in the union, to merge the ones that differ
	 * only in their values.
	 */
	#[DataProvider(method: 'types')]
	#[Linearithmic(size: 'types')]
	public function benchUnionConstantArrays(int $types): Type
	{
		return TypeCombinator::union(...$this->arrays);
	}

	#[DataProvider(method: 'types')]
	#[Linear(size: 'types')]
	public function benchIntersectUnions(int $types): Type
	{
		return TypeCombinator::intersect(...$this->unions);
	}

	#[DataProvider(method: 'types')]
	#[Linear(size: 'types')]
	public function benchRemoveFromUnion(int $types): Type
	{
		return TypeCombinator::remove(...$this->unions);
	}

	/**
	 * Arrays of the same keys that differ in two constant values cannot be keys-supersets of each other.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '1.092M', cycles: '1.607M', memory: '65.79KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '6.346M', cycles: '8.918M', memory: '276.2KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '25.51M', cycles: '34.29M', memory: '1.089MiB')]
	public function benchUnionTaggedConstantArrays(int $types): Type
	{
		return TypeCombinator::union(...$this->taggedArrays);
	}

	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '150.8K', cycles: '312.0K', memory: '6.055KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '520.0K', cycles: '759.1K', memory: '17.50KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '1.991M', cycles: '2.490M', memory: '74.38KiB')]
	public function benchIntersectBenevolentUnion(int $types): Type
	{
		return TypeCombinator::intersect(...$this->benevolentUnions);
	}

	/**
	 * Each member is a general bool, float, int or string.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '231.4K', cycles: '470.2K', memory: '6.789KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '563.3K', cycles: '854.1K', memory: '24.05KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '2.044M', cycles: '2.580M', memory: '123.5KiB')]
	public function benchUnionGeneralScalars(int $types): Type
	{
		return TypeCombinator::union(...$this->generalScalars);
	}

	/**
	 * numeric-string is compared with each constant string.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '305.3K', cycles: '536.1K', memory: '6.258KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '1.048M', cycles: '1.338M', memory: '25.20KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '4.010M', cycles: '4.612M', memory: '104.9KiB')]
	public function benchUnionNumericStringAndConstantStrings(int $types): Type
	{
		return TypeCombinator::union(...$this->numericStringAndStrings);
	}

	/**
	 * The constant arrays have the same eight string keys. The general array has int keys, so it
	 * is told apart from them and they stay a union of shapes.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '6.702M', cycles: '9.063M', memory: '226.0KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '26.52M', cycles: '33.92M', memory: '894.0KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '106.7M', cycles: '135.4M', memory: '3.558MiB')]
	public function benchUnionGeneralArrayAndConstantArrays(int $types): Type
	{
		return TypeCombinator::union(...$this->arraysWithSameKeys);
	}

	/**
	 * Each constant array has eight keys of its own. More than 256 values in all are generalized
	 * to an oversized array.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '2.286M', cycles: '3.510M', memory: '377.9KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '14.68M', cycles: '21.00M', memory: '1.508MiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '58.83M', cycles: '81.73M', memory: '6.139MiB')]
	public function benchUnionConstantArraysWithOwnKeys(int $types): Type
	{
		return TypeCombinator::union(...$this->arraysWithOwnKeys);
	}

	/**
	 * Intersections of stdClass with one method each are flattened into one.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '2.141M', cycles: '2.654M', memory: '5.844KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '25.12M', cycles: '26.52M', memory: '34.02KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '361.9M', cycles: '368.4M', memory: '111.5KiB')]
	public function benchIntersectIntersections(int $types): Type
	{
		return TypeCombinator::intersect(...$this->intersections);
	}

	/**
	 * The implicit never types an empty branch contributes are dropped from the union.
	 */
	#[DataProvider(method: 'types')]
	#[Linear(size: 'types')]
	public function benchUnionWithNevers(int $types): Type
	{
		return TypeCombinator::union(...$this->stringsAndNevers);
	}

	/**
	 * Accessory types only, which need no sorting, a hundred times.
	 */
	#[DataProvider(method: 'smallest')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '7.040M', cycles: '8.217M', memory: '2.289KiB')]
	public function benchIntersectAccessories(int $types): Type
	{
		$type = new StringType();
		for ($i = 0; $i < 100; $i++) {
			$type = TypeCombinator::intersect(new StringType(), new AccessoryNonEmptyStringType(), new AccessoryNumericStringType(), new AccessoryLowercaseStringType());
		}

		return $type;
	}

	/**
	 * Enum|null minus all the cases of the enum, as a match over all of them leaves it.
	 */
	#[DataProvider(method: 'types')]
	#[Linear(size: 'types')]
	public function benchRemoveEnumCases(int $types): Type
	{
		if ($this->enumOrNull === null || $this->enumCases === null) {
			throw new LogicException('boot() did not run.');
		}

		return TypeCombinator::remove($this->enumOrNull, $this->enumCases);
	}

	/**
	 * Shapes of the same two keys differing in the value of one, which merge into one shape.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '3.105M', cycles: '4.419M', memory: '74.80KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '13.61M', cycles: '17.74M', memory: '289.5KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '60.06M', cycles: '76.90M', memory: '1.389MiB')]
	public function benchUnionShapesDifferingAtOneKey(int $types): Type
	{
		return TypeCombinator::union(...$this->shapesDifferingAtOneKey);
	}

	/**
	 * Lists of one to eight elements of their own values, as a list is appended to in a loop.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '1.751M', cycles: '2.672M', memory: '175.0KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '10.85M', cycles: '14.40M', memory: '584.6KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '63.59M', cycles: '80.21M', memory: '2.259MiB')]
	public function benchUnionListsOfGrowingLength(int $types): Type
	{
		return TypeCombinator::union(...$this->listsOfGrowingLength);
	}

	/**
	 * Non-empty arrays with a different value at the same offset each, which merge into one.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '2.022M', cycles: '2.798M', memory: '18.00KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '7.521M', cycles: '8.972M', memory: '64.97KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '29.48M', cycles: '33.65M', memory: '318.0KiB')]
	public function benchUnionOffsetValueIntersections(int $types): Type
	{
		return TypeCombinator::union(...$this->offsetValueIntersections);
	}

	/**
	 * non-empty-string holds each constant string but the empty one, with which it is string.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '274.9K', cycles: '492.2K', memory: '7.094KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '893.6K', cycles: '1.176M', memory: '31.84KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '3.363M', cycles: '3.959M', memory: '112.8KiB')]
	public function benchUnionNonEmptyStringAndConstantStrings(int $types): Type
	{
		return TypeCombinator::union(...$this->nonEmptyStringAndStrings);
	}

	/**
	 * Integer ranges with a gap between each two, which stay apart.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '374.1K', cycles: '625.9K', memory: '22.77KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '1.677M', cycles: '2.122M', memory: '82.14KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '7.126M', cycles: '8.258M', memory: '352.1KiB')]
	public function benchUnionDisjointRanges(int $types): Type
	{
		return TypeCombinator::union(...$this->disjointRanges);
	}

	/**
	 * Integer ranges each touching the one before, in reverse order, which merge into one range.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '324.7K', cycles: '566.1K', memory: '23.00KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '1.197M', cycles: '1.604M', memory: '83.00KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '5.561M', cycles: '6.610M', memory: '357.0KiB')]
	public function benchUnionTouchingRangesInReverse(int $types): Type
	{
		return TypeCombinator::union(...$this->touchingRangesInReverse);
	}

	/**
	 * Integer ranges with a constant in the gap after each, which stay apart.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '671.3K', cycles: '1.004M', memory: '42.62KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '2.979M', cycles: '3.679M', memory: '163.1KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '13.11M', cycles: '16.02M', memory: '905.6KiB')]
	public function benchUnionIntegersAndRanges(int $types): Type
	{
		return TypeCombinator::union(...$this->integersAndRanges);
	}

	/**
	 * Two unions of ranges, each range of one overlapping one range of the other at one value.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '317.9K', cycles: '519.7K', memory: '11.86KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '1.335M', cycles: '1.653M', memory: '45.05KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '5.848M', cycles: '6.677M', memory: '186.8KiB')]
	public function benchIntersectRangeUnions(int $types): Type
	{
		return TypeCombinator::intersect(...$this->rangeUnions);
	}

	/**
	 * A constant in the middle of each range of a union removed from it, splitting each range.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '383.2K', cycles: '592.7K', memory: '17.34KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '1.606M', cycles: '2.001M', memory: '68.72KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '7.207M', cycles: '9.281M', memory: '540.2KiB')]
	public function benchRemoveConstantsFromRanges(int $types): Type
	{
		return TypeCombinator::remove(...$this->rangesAndConstants);
	}

	private function createTypes(string $prefix, int $types): void
	{
		$this->strings = [];
		$this->integers = [];
		$this->arrays = [];
		$this->stringsAndNevers = [];
		$others = [];
		for ($i = 0; $i < $types; $i++) {
			$this->stringsAndNevers[] = new ConstantStringType($prefix . $i);
			$this->stringsAndNevers[] = new NeverType();
			$this->strings[] = new ConstantStringType($prefix . $i);
			$this->integers[] = new ConstantIntegerType($i * 3);
			$this->arrays[] = new ConstantArrayType([new ConstantStringType($prefix . $i)], [new ConstantIntegerType($i)]);
			// half of them in both unions
			$others[] = new ConstantStringType($prefix . ($i * 2));
		}
		$this->unions = [new UnionType($this->strings), new UnionType($others)];
		$this->benevolentUnions = [new BenevolentUnionType($this->strings), new UnionType($others)];
		$this->taggedArrays = [];
		for ($i = 0; $i < $types; $i++) {
			$this->taggedArrays[] = new ConstantArrayType(
				[new ConstantStringType('kind'), new ConstantStringType('id')],
				[new ConstantStringType($prefix . $i), new ConstantIntegerType($i)],
			);
		}

		$this->generalScalars = [];
		$this->numericStringAndStrings = [new IntersectionType([new StringType(), new AccessoryNumericStringType()])];
		$this->arraysWithSameKeys = [new ArrayType(new IntegerType(), new StringType())];
		$this->arraysWithOwnKeys = [];
		$this->intersections = [];
		$generalScalars = [BooleanType::class, FloatType::class, IntegerType::class, StringType::class];
		for ($i = 0; $i < $types; $i++) {
			$this->generalScalars[] = new $generalScalars[$i % 4]();
			$this->numericStringAndStrings[] = new ConstantStringType($prefix . $i);
			$sameKeys = [];
			$ownKeys = [];
			$values = [];
			for ($j = 0; $j < 8; $j++) {
				$sameKeys[] = new ConstantStringType($prefix . 'key' . $j);
				$ownKeys[] = new ConstantStringType($prefix . $i . 'key' . $j);
				$values[] = new ConstantIntegerType($i * 8 + $j);
			}
			$this->arraysWithSameKeys[] = new ConstantArrayType($sameKeys, $values);
			$this->arraysWithOwnKeys[] = new ConstantArrayType($ownKeys, $values);
			$this->intersections[] = new IntersectionType([new ObjectType(stdClass::class), new HasMethodType($prefix . $i)]);
		}

		// the integers of the warm-up are other ones than those of the benchmarks
		$base = $prefix === 'warmup' ? 0 : 1_000_000;
		$this->shapesDifferingAtOneKey = [];
		$this->listsOfGrowingLength = [];
		$this->offsetValueIntersections = [];
		$this->nonEmptyStringAndStrings = [new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()])];
		$this->disjointRanges = [];
		$this->touchingRangesInReverse = [];
		$this->integersAndRanges = [];
		$ranges = [];
		$otherRanges = [];
		$constants = [];
		for ($i = 0; $i < $types; $i++) {
			$this->shapesDifferingAtOneKey[] = new ConstantArrayType(
				[new ConstantStringType($prefix . 'a'), new ConstantStringType($prefix . 'b')],
				[new ConstantIntegerType($base + $i), new ConstantIntegerType($base)],
			);
			$length = $i % 8 + 1;
			$keys = [];
			$values = [];
			for ($j = 0; $j < $length; $j++) {
				$keys[] = new ConstantIntegerType($j);
				$values[] = new ConstantStringType($prefix . 'value' . $i);
			}
			$this->listsOfGrowingLength[] = new ConstantArrayType($keys, $values, [$length], [], TrinaryLogic::createYes());
			$this->offsetValueIntersections[] = new IntersectionType([
				new ArrayType(new StringType(), new IntegerType()),
				new NonEmptyArrayType(),
				new HasOffsetValueType(new ConstantStringType($prefix . 'a'), new ConstantIntegerType($base + $i)),
			]);
			$this->nonEmptyStringAndStrings[] = new ConstantStringType($prefix . $i);
			$this->disjointRanges[] = IntegerRangeType::fromInterval($base + $i * 3, $base + $i * 3 + 1);
			$this->touchingRangesInReverse[] = IntegerRangeType::fromInterval($base + ($types - $i) * 2, $base + ($types - $i) * 2 + 1);
			$this->integersAndRanges[] = IntegerRangeType::fromInterval($base + $i * 4, $base + $i * 4 + 1);
			$this->integersAndRanges[] = new ConstantIntegerType($base + $i * 4 + 3);
			$ranges[] = IntegerRangeType::fromInterval($base + $i * 4, $base + $i * 4 + 2);
			$otherRanges[] = IntegerRangeType::fromInterval($base + $i * 4 + 2, $base + $i * 4 + 3);
			$constants[] = new ConstantIntegerType($base + $i * 4 + 1);
		}
		$this->nonEmptyStringAndStrings[] = new ConstantStringType('');
		$this->rangeUnions = [new UnionType($ranges), new UnionType($otherRanges)];
		$this->rangesAndConstants = [new UnionType($ranges), new UnionType($constants)];
	}

}
