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
	#[Linearithmic(size: 'types')]
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
	#[ExpectedFor(dataSet: 'types-256', instructions: '35.91M', cycles: '41.90M', memory: '566.8KiB')]
	public function benchUnionTaggedConstantArrays(int $types): Type
	{
		return TypeCombinator::union(...$this->taggedArrays);
	}

	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '220.0K', cycles: '455.0K', memory: '9.195KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '785.5K', cycles: '1.124M', memory: '27.28KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '3.035M', cycles: '3.702M', memory: '112.5KiB')]
	public function benchIntersectBenevolentUnion(int $types): Type
	{
		return TypeCombinator::intersect(...$this->benevolentUnions);
	}

	/**
	 * Each member is a general bool, float, int or string.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '190.7K', cycles: '426.4K', memory: '7.914KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '560.7K', cycles: '837.9K', memory: '20.01KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '2.648M', cycles: '3.143M', memory: '84.26KiB')]
	public function benchUnionGeneralScalars(int $types): Type
	{
		return TypeCombinator::union(...$this->generalScalars);
	}

	/**
	 * numeric-string is compared with each constant string.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '663.7K', cycles: '1.066M', memory: '10.86KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '2.544M', cycles: '3.382M', memory: '40.86KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '10.06M', cycles: '12.62M', memory: '163.1KiB')]
	public function benchUnionNumericStringAndConstantStrings(int $types): Type
	{
		return TypeCombinator::union(...$this->numericStringAndStrings);
	}

	/**
	 * The constant arrays have the same eight keys, which the general array gets once.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '3.481M', cycles: '4.284M', memory: '106.4KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '14.36M', cycles: '16.60M', memory: '345.6KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '61.45M', cycles: '69.22M', memory: '1.201MiB')]
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
	#[ExpectedFor(dataSet: 'types-64', instructions: '114.5M', cycles: '138.3M', memory: '799.2KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '312.1M', cycles: '387.8M', memory: '3.128MiB')]
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
	#[ExpectedFor(dataSet: 'types-256', instructions: '340.6M', cycles: '348.1M', memory: '115.6KiB')]
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
	#[ExpectedFor(dataSet: 'types-16', instructions: '6.316M', cycles: '7.491M', memory: '2.313KiB')]
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
	#[ExpectedFor(dataSet: 'types-16', instructions: '4.659M', cycles: '6.106M', memory: '55.38KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '46.65M', cycles: '52.04M', memory: '267.1KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '728.5M', cycles: '767.2M', memory: '2.773MiB')]
	public function benchUnionShapesDifferingAtOneKey(int $types): Type
	{
		return TypeCombinator::union(...$this->shapesDifferingAtOneKey);
	}

	/**
	 * Lists of one to eight elements of their own values, as a list is appended to in a loop.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '4.267M', cycles: '5.511M', memory: '119.6KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '22.13M', cycles: '26.27M', memory: '402.5KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '215.2M', cycles: '243.7M', memory: '1.588MiB')]
	public function benchUnionListsOfGrowingLength(int $types): Type
	{
		return TypeCombinator::union(...$this->listsOfGrowingLength);
	}

	/**
	 * Non-empty arrays with a different value at the same offset each, which merge into one.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '3.690M', cycles: '4.786M', memory: '21.20KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '35.03M', cycles: '38.25M', memory: '73.79KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '516.9M', cycles: '540.8M', memory: '342.4KiB')]
	public function benchUnionOffsetValueIntersections(int $types): Type
	{
		return TypeCombinator::union(...$this->offsetValueIntersections);
	}

	/**
	 * non-empty-string holds each constant string but the empty one, with which it is string.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '262.3K', cycles: '503.9K', memory: '12.02KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '874.5K', cycles: '1.211M', memory: '46.89KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '3.322M', cycles: '4.019M', memory: '167.6KiB')]
	public function benchUnionNonEmptyStringAndConstantStrings(int $types): Type
	{
		return TypeCombinator::union(...$this->nonEmptyStringAndStrings);
	}

	/**
	 * Integer ranges with a gap between each two, which stay apart.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '1.187M', cycles: '1.379M', memory: '9.992KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '17.35M', cycles: '17.66M', memory: '29.80KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '268.5M', cycles: '269.7M', memory: '136.6KiB')]
	public function benchUnionDisjointRanges(int $types): Type
	{
		return TypeCombinator::union(...$this->disjointRanges);
	}

	/**
	 * Integer ranges each touching the one before, in reverse order, which merge into one range.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '252.2K', cycles: '428.8K', memory: '10.23KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '931.6K', cycles: '1.176M', memory: '30.66KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '4.521M', cycles: '5.066M', memory: '141.4KiB')]
	public function benchUnionTouchingRangesInReverse(int $types): Type
	{
		return TypeCombinator::union(...$this->touchingRangesInReverse);
	}

	/**
	 * Integer ranges with a constant in the gap after each, which stay apart.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '1.554M', cycles: '1.813M', memory: '17.91KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '18.93M', cycles: '19.38M', memory: '63.05KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '275.3M', cycles: '277.0M', memory: '244.0KiB')]
	public function benchUnionIntegersAndRanges(int $types): Type
	{
		return TypeCombinator::union(...$this->integersAndRanges);
	}

	/**
	 * Two unions of ranges, each range of one overlapping one range of the other at one value.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '3.072M', cycles: '3.750M', memory: '12.50KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '43.87M', cycles: '49.60M', memory: '35.69KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '680.2M', cycles: '764.4M', memory: '166.4KiB')]
	public function benchIntersectRangeUnions(int $types): Type
	{
		return TypeCombinator::intersect(...$this->rangeUnions);
	}

	/**
	 * A constant in the middle of each range of a union removed from it, splitting each range.
	 */
	#[DataProvider(method: 'types')]
	#[ExpectedFor(dataSet: 'types-16', instructions: '2.452M', cycles: '3.167M', memory: '8.883KiB')]
	#[ExpectedFor(dataSet: 'types-64', instructions: '27.40M', cycles: '30.25M', memory: '16.01KiB')]
	#[ExpectedFor(dataSet: 'types-256', instructions: '391.2M', cycles: '411.2M', memory: '46.51KiB')]
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
