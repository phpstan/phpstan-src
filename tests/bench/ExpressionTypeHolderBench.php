<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\ExpressionTypeHolder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\UnionType;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\ScratchDirectory;

/**
 * Merging scopes compares the holders of the expressions both scopes track, which are mostly
 * the same holder, or hold the same type, when a branch did not change the expression.
 * The type is a union of 64 constant strings, which is slow to compare.
 */
final class ExpressionTypeHolderBench
{

	private const MEMBERS = 64;

	private ?ExpressionTypeHolder $holder = null;

	private ?ExpressionTypeHolder $sameType = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);

		$warmup = self::union('warmup');
		$holder = ExpressionTypeHolder::createYes(new Variable('a'), $warmup);
		$holder->equals(ExpressionTypeHolder::createYes(new Variable('a'), $warmup));
		$holder->and(ExpressionTypeHolder::createMaybe(new Variable('a'), $warmup));

		$type = self::union('member');
		$this->holder = ExpressionTypeHolder::createYes(new Variable('a'), $type);
		$this->sameType = ExpressionTypeHolder::createMaybe(new Variable('a'), $type);
	}

	/**
	 * The same holder (#4777).
	 */
	#[Expected(instructions: '1.101K', cycles: '14.52K', memory: '0B')]
	public function benchEqualsSameHolder(): bool
	{
		$holder = $this->holder();

		return $holder->equals($holder);
	}

	/**
	 * Another holder of the same type object (#4800).
	 */
	#[Expected(instructions: '2.659K', cycles: '28.84K', memory: '0B')]
	public function benchAndSameType(): ExpressionTypeHolder
	{
		if ($this->sameType === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->holder()->and($this->sameType);
	}

	private function holder(): ExpressionTypeHolder
	{
		if ($this->holder === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->holder;
	}

	private static function union(string $prefix): UnionType
	{
		$types = [];
		for ($i = 0; $i < self::MEMBERS; $i++) {
			$types[] = new ConstantStringType($prefix . $i);
		}

		return new UnionType($types);
	}

}
