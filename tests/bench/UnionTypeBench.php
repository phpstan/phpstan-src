<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Linear;
use Tally\Attribute\Linearithmic;
use Tally\ScratchDirectory;

final class UnionTypeBench
{

	private ?UnionType $union = null;

	private ?UnionType $other = null;

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

		[$this->union, $this->other] = self::unions('member', $members);
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

	private function union(): UnionType
	{
		if ($this->union === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->union;
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
