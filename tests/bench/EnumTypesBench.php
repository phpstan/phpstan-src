<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\ScratchDirectory;

/**
 * The types of the 256 cases of BenchEnum.
 */
final class EnumTypesBench
{

	private const CASES = 256;

	/** @var list<Type> */
	private array $unionMembers = [];

	private ?UnionType $union = null;

	private ?IntersectionType $intersection = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);

		// reflects the enum and its cases; the types memoize their results, so the counted calls get new ones
		$warmupCases = self::cases(4);
		TypeCombinator::union(...self::unionMembers());
		(new UnionType($warmupCases))->getFiniteTypes();
		self::withoutFirstCase()->getFiniteTypes();

		$this->unionMembers = self::unionMembers();
		$this->union = new UnionType(self::cases(self::CASES));
		$this->intersection = self::withoutFirstCase();
	}

	/**
	 * A member that is one enum case is set aside; whether the intersection with the enum is one
	 * does not need all of its cases.
	 */
	#[Expected(instructions: '156.3K', cycles: '516.7K', memory: '4.328KiB')]
	public function benchUnionWithIntersection(): Type
	{
		if ($this->unionMembers === []) {
			throw new LogicException('boot() did not run.');
		}

		return TypeCombinator::union(...$this->unionMembers);
	}

	/**
	 * @return list<Type>
	 */
	#[Expected(instructions: '804.4K', cycles: '1.086M', memory: '40.11KiB')]
	public function benchUnionTypeGetFiniteTypes(): array
	{
		if ($this->union === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->union->getFiniteTypes();
	}

	/**
	 * The cases of the enum are matched with the cases of the enum without C0.
	 *
	 * @return list<Type>
	 */
	#[Expected(instructions: '1.121M', cycles: '1.668M', memory: '72.42KiB')]
	public function benchIntersectionTypeGetFiniteTypes(): array
	{
		if ($this->intersection === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->intersection->getFiniteTypes();
	}

	/**
	 * @return list<EnumCaseObjectType>
	 */
	private static function cases(int $count): array
	{
		$cases = [];
		for ($i = 0; $i < $count; $i++) {
			$cases[] = new EnumCaseObjectType(BenchEnum::class, 'C' . $i);
		}

		return $cases;
	}

	/**
	 * @return list<Type>
	 */
	private static function unionMembers(): array
	{
		return [
			new IntersectionType([new ObjectType(BenchEnum::class), new HasPropertyType('name')]),
			new IntegerType(),
			new StringType(),
		];
	}

	private static function withoutFirstCase(): IntersectionType
	{
		return new IntersectionType([
			new ObjectType(BenchEnum::class),
			new ObjectType(BenchEnum::class, new EnumCaseObjectType(BenchEnum::class, 'C0')),
		]);
	}

}
