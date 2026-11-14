<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use stdClass;
use Tally\Attribute\Before;
use Tally\Attribute\Constant;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\Attribute\Linear;
use Tally\Attribute\Linearithmic;
use Tally\ScratchDirectory;

final class UnionTypeBench
{

	private ?UnionType $union = null;

	private ?UnionType $other = null;

	private ?UnionType $intersections = null;

	private ?UnionType $arrays = null;

	private ?BenevolentUnionType $benevolent = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function members(): iterable
	{
		foreach ([16, 64, 256] as $members) {
			yield 'members-' . $members => [$members];
		}
	}

	#[Before]
	public function boot(int $members): void
	{
		BenchContainer::create($this->scratch);

		[$union, $other] = self::unions('warmup', 4);
		$union->describe(VerbosityLevel::precise());
		$union->isSuperTypeOf($other);
		self::intersections('warmup', 4)->describe(VerbosityLevel::precise());
		self::arrays(4)->getIterableValueType();

		[$this->union, $this->other] = self::unions('member', $members);
		$this->benevolent = new BenevolentUnionType($this->union->getTypes());

		// the queries the union memoizes
		$this->union->getFiniteTypes();
		$this->union->isNull();
		$this->union->isCallable();
		$this->intersections = self::intersections('member', $members);
		$this->arrays = self::arrays($members);
	}

	/**
	 * Each member is an intersection, described in parentheses.
	 */
	#[DataProvider(method: 'members')]
	#[ExpectedFor(dataSet: 'members-16', instructions: '1.728M', cycles: '2.738M', memory: '16.65KiB')]
	#[ExpectedFor(dataSet: 'members-64', instructions: '7.620M', cycles: '10.73M', memory: '44.40KiB')]
	#[ExpectedFor(dataSet: 'members-256', instructions: '39.92M', cycles: '63.68M', memory: '198.8KiB')]
	public function benchDescribeIntersections(int $members): string
	{
		if ($this->intersections === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->intersections->describe(VerbosityLevel::precise());
	}

	/**
	 * The value types of the member arrays are unioned.
	 */
	#[DataProvider(method: 'members')]
	#[ExpectedFor(dataSet: 'members-16', instructions: '151.6K', cycles: '286.2K', memory: '4.680KiB')]
	#[ExpectedFor(dataSet: 'members-64', instructions: '573.3K', cycles: '796.1K', memory: '12.18KiB')]
	#[ExpectedFor(dataSet: 'members-256', instructions: '2.259M', cycles: '2.771M', memory: '62.18KiB')]
	public function benchGetIterableValueType(int $members): Type
	{
		if ($this->arrays === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->arrays->getIterableValueType();
	}

	/**
	 * The members are described in a sorted order.
	 */
	#[DataProvider(method: 'members')]
	#[Linearithmic(size: 'members')]
	public function benchDescribe(int $members): string
	{
		return $this->union()->describe(VerbosityLevel::precise());
	}

	#[DataProvider(method: 'members')]
	#[Linear(size: 'members')]
	public function benchIsSuperTypeOf(int $members): bool
	{
		if ($this->other === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->union()->isSuperTypeOf($this->other)->yes();
	}

	/**
	 * Every member stays the same, so the union does too.
	 */
	#[DataProvider(method: 'members')]
	#[Linear(size: 'members')]
	public function benchUnchangedToString(int $members): Type
	{
		return $this->union()->toString();
	}

	#[DataProvider(method: 'members')]
	#[Linear(size: 'members')]
	public function benchBenevolentUnchangedToString(int $members): Type
	{
		if ($this->benevolent === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->benevolent->toString();
	}

	/**
	 * Asked again, as boot() asked it already.
	 *
	 * @return list<Type>
	 */
	#[DataProvider(method: 'members')]
	#[Constant(size: 'members')]
	public function benchGetFiniteTypesAgain(int $members): array
	{
		return $this->union()->getFiniteTypes();
	}

	#[DataProvider(method: 'members')]
	#[Constant(size: 'members')]
	public function benchIsNullAgain(int $members): TrinaryLogic
	{
		return $this->union()->isNull();
	}

	#[DataProvider(method: 'members')]
	#[Constant(size: 'members')]
	public function benchIsCallableAgain(int $members): TrinaryLogic
	{
		return $this->union()->isCallable();
	}

	private function union(): UnionType
	{
		if ($this->union === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->union;
	}

	private static function intersections(string $prefix, int $members): UnionType
	{
		$types = [];
		for ($i = 0; $i < $members; $i++) {
			$types[] = new IntersectionType([new ObjectType(stdClass::class), new HasMethodType($prefix . $i)]);
		}

		return new UnionType($types);
	}

	/**
	 * Lists with one constant integer each.
	 */
	private static function arrays(int $members): UnionType
	{
		$types = [];
		for ($i = 0; $i < $members; $i++) {
			$types[] = new ConstantArrayType([new ConstantIntegerType(0)], [new ConstantIntegerType($i)], [1], [], TrinaryLogic::createYes());
		}

		return new UnionType($types);
	}

	/**
	 * @return array{UnionType, UnionType} the other has half of the members of the first
	 */
	private static function unions(string $prefix, int $members): array
	{
		$types = [];
		$others = [];
		for ($i = 0; $i < $members; $i++) {
			$types[] = new ConstantStringType($prefix . $i);
			$others[] = new ConstantStringType($prefix . ($i * 2));
		}

		return [new UnionType($types), new UnionType($others)];
	}

}
