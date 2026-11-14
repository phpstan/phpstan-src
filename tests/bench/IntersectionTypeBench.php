<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use stdClass;
use Tally\Attribute\Before;
use Tally\Attribute\Constant;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

/**
 * An object with many methods narrowed by method_exists(): stdClass&hasMethod(m0)&hasMethod(m1)&…
 */
final class IntersectionTypeBench
{

	private ?IntersectionType $intersection = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function members(): iterable
	{
		foreach ([16, 64] as $members) {
			yield 'members-' . $members => [$members];
		}
	}

	#[Before]
	public function boot(int $members): void
	{
		BenchContainer::create($this->scratch);

		$warmup = self::intersection('warmup', 4);
		$warmup->isVoid();
		self::askWhatItIs($warmup);
		$warmup->isSubTypeOf(new ObjectType(stdClass::class));
		$warmup->isAcceptedBy(new ObjectType(stdClass::class), true);

		$this->intersection = self::intersection('m', $members);
		// what the intersection is was asked before, the counted call asks again
		self::askWhatItIs($this->intersection);
	}

	#[DataProvider(method: 'members')]
	#[Constant(size: 'members')]
	public function benchIsVoid(int $members): TrinaryLogic
	{
		return $this->subject()->isVoid();
	}

	/**
	 * The scope asks a type what it is many times.
	 */
	#[DataProvider(method: 'members')]
	#[Constant(size: 'members')]
	public function benchAskAgainWhatItIs(int $members): TrinaryLogic
	{
		return self::askWhatItIs($this->subject());
	}

	/**
	 * stdClass, the first member, is already a subtype of stdClass.
	 */
	#[DataProvider(method: 'members')]
	public function benchIsSubTypeOf(int $members): IsSuperTypeOfResult
	{
		return $this->subject()->isSubTypeOf(new ObjectType(stdClass::class));
	}

	#[DataProvider(method: 'members')]
	public function benchIsAcceptedBy(int $members): AcceptsResult
	{
		return $this->subject()->isAcceptedBy(new ObjectType(stdClass::class), true);
	}

	private function subject(): IntersectionType
	{
		if ($this->intersection === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->intersection;
	}

	private static function askWhatItIs(IntersectionType $type): TrinaryLogic
	{
		return $type->isBoolean()
			->or($type->isFloat())
			->or($type->isInteger())
			->or($type->isString())
			->or($type->isArray())
			->or($type->isList())
			->or($type->isConstantArray())
			->or($type->isOversizedArray())
			->or($type->isIterableAtLeastOnce())
			->or($type->isConstantScalarValue());
	}

	private static function intersection(string $prefix, int $members): IntersectionType
	{
		$types = [new ObjectType(stdClass::class)];
		for ($i = 1; $i < $members; $i++) {
			$types[] = new HasMethodType($prefix . $i);
		}

		return new IntersectionType($types);
	}

}
