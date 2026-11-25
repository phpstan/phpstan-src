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
	#[ExpectedFor(dataSet: 'types-16', instructions: '1.424M', cycles: '1.962M', memory: '36.67KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '5.858M', cycles: '7.110M', memory: '135.3KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '31.62M', cycles: '37.46M', memory: '566.8KiB')]
	public function benchUnionTaggedConstantArrays(int $types): Type
	{
		return TypeCombinator::union(...$this->taggedArrays);
	}

	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '160.6K', cycles: '350.5K', memory: '8.875KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '570.6K', cycles: '861.9K', memory: '26.20KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '2.199M', cycles: '2.807M', memory: '107.7KiB')]
	public function benchIntersectBenevolentUnion(int $types): Type
	{
		return TypeCombinator::intersect(...$this->benevolentUnions);
	}

	/**
	 * Each member is a general bool, float, int or string.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '238.9K', cycles: '402.4K', memory: '3.430KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '424.8K', cycles: '595.1K', memory: '8.453KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '1.647M', cycles: '1.957M', memory: '38.72KiB')]
	public function benchUnionGeneralScalars(int $types): Type
	{
		return TypeCombinator::union(...$this->generalScalars);
	}

	/**
	 * numeric-string is compared with each constant string.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '712.8K', cycles: '1.184M', memory: '9.977KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '2.673M', cycles: '3.750M', memory: '37.68KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '10.51M', cycles: '13.99M', memory: '146.4KiB')]
	public function benchUnionNumericStringAndConstantStrings(int $types): Type
	{
		return TypeCombinator::union(...$this->numericStringAndStrings);
	}

	/**
	 * The constant arrays have the same eight keys, which the general array gets once.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '2.040M', cycles: '2.700M', memory: '64.27KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '7.762M', cycles: '9.538M', memory: '211.5KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '30.76M', cycles: '37.02M', memory: '813.5KiB')]
	public function benchUnionGeneralArrayAndConstantArrays(int $types): Type
	{
		return TypeCombinator::union(...$this->arraysWithSameKeys);
	}

	/**
	 * Each constant array has eight keys of its own. More than 256 values in all are generalized
	 * to an oversized array.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '3.095M', cycles: '4.054M', memory: '214.9KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '120.2M', cycles: '146.4M', memory: '799.2KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '327.5M', cycles: '408.6M', memory: '3.128MiB')]
	public function benchUnionConstantArraysWithOwnKeys(int $types): Type
	{
		return TypeCombinator::union(...$this->arraysWithOwnKeys);
	}

	/**
	 * Intersections of stdClass with one method each are flattened into one.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '2.049M', cycles: '2.625M', memory: '6.313KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '23.20M', cycles: '25.05M', memory: '35.38KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '329.7M', cycles: '340.6M', memory: '115.6KiB')]
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
	#[ExpectedFor(dataSet: 'types-16', instructions: '6.006M', cycles: '7.173M', memory: '2.313KiB')]
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
	#[ExpectedFor(dataSet: 'types-16', instructions: '2.628M', cycles: '3.718M', memory: '48.16KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '20.85M', cycles: '24.61M', memory: '243.6KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '247.2M', cycles: '269.3M', memory: '2.674MiB')]
	public function benchUnionShapesDifferingAtOneKey(int $types): Type
	{
		return TypeCombinator::union(...$this->shapesDifferingAtOneKey);
	}

	/**
	 * Lists of one to eight elements of their own values, as a list is appended to in a loop.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '4.001M', cycles: '5.114M', memory: '119.5KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '20.29M', cycles: '23.90M', memory: '402.4KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '199.9M', cycles: '224.3M', memory: '1.588MiB')]
	public function benchUnionListsOfGrowingLength(int $types): Type
	{
		return TypeCombinator::union(...$this->listsOfGrowingLength);
	}

	/**
	 * Non-empty arrays with a different value at the same offset each, which merge into one.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '2.830M', cycles: '3.766M', memory: '13.91KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '15.23M', cycles: '17.59M', memory: '51.30KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '134.0M', cycles: '145.5M', memory: '245.7KiB')]
	public function benchUnionOffsetValueIntersections(int $types): Type
	{
		return TypeCombinator::union(...$this->offsetValueIntersections);
	}

	/**
	 * non-empty-string holds each constant string but the empty one, with which it is string.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '678.8K', cycles: '1.213M', memory: '14.52KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '2.395M', cycles: '3.495M', memory: '45.65KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '10.39M', cycles: '13.94M', memory: '166.2KiB')]
	public function benchUnionNonEmptyStringAndConstantStrings(int $types): Type
	{
		return TypeCombinator::union(...$this->nonEmptyStringAndStrings);
	}

	/**
	 * Integer ranges with a gap between each two, which stay apart.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '551.9K', cycles: '675.7K', memory: '3.992KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '7.936M', cycles: '8.124M', memory: '15.67KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '120.3M', cycles: '121.1M', memory: '77.42KiB')]
	public function benchUnionDisjointRanges(int $types): Type
	{
		return TypeCombinator::union(...$this->disjointRanges);
	}

	/**
	 * Integer ranges each touching the one before, in reverse order, which merge into one range.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '284.0K', cycles: '432.1K', memory: '3.992KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '1.316M', cycles: '1.567M', memory: '16.23KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '7.369M', cycles: '8.025M', memory: '84.73KiB')]
	public function benchUnionTouchingRangesInReverse(int $types): Type
	{
		return TypeCombinator::union(...$this->touchingRangesInReverse);
	}

	/**
	 * Integer ranges with a constant in the gap after each, which stay apart.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '1.953M', cycles: '2.188M', memory: '7.211KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '27.52M', cycles: '28.17M', memory: '31.70KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '422.3M', cycles: '427.5M', memory: '125.0KiB')]
	public function benchUnionIntegersAndRanges(int $types): Type
	{
		return TypeCombinator::union(...$this->integersAndRanges);
	}

	/**
	 * Two unions of ranges, each range of one overlapping one range of the other at one value.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '3.263M', cycles: '4.057M', memory: '6.898KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '48.68M', cycles: '57.06M', memory: '20.25KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '765.0M', cycles: '894.3M', memory: '90.88KiB')]
	public function benchIntersectRangeUnions(int $types): Type
	{
		return TypeCombinator::intersect(...$this->rangeUnions);
	}

	/**
	 * A constant in the middle of each range of a union removed from it, splitting each range.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '1.993M', cycles: '2.515M', memory: '6.047KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '25.57M', cycles: '27.78M', memory: '13.17KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '383.9M', cycles: '400.4M', memory: '43.67KiB')]
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
