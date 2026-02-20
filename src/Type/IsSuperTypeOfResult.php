<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;

/**
 * Result of a Type::isSuperTypeOf() check — whether one type is a supertype of another.
 *
 * Wraps a TrinaryLogic result together with human-readable reasons explaining the
 * relationship. This is the primary mechanism for comparing types in PHPStan's type system.
 *
 * `isSuperTypeOf()` answers: "Can all values of type B also be values of type A?"
 * For example:
 * - `(new StringType())->isSuperTypeOf(new ConstantStringType('hello'))` → Yes
 * - `(new IntegerType())->isSuperTypeOf(new StringType())` → No
 * - `(new StringType())->isSuperTypeOf(new MixedType())` → Maybe
 *
 * This is distinct from `accepts()` which also considers rule levels and PHPDoc context.
 * Use `isSuperTypeOf()` for type-theoretic comparisons and `accepts()` for assignability checks.
 *
 * Can be converted to AcceptsResult via toAcceptsResult().
 *
 * @api
 */
final class IsSuperTypeOfResult
{

	private static self $YES;

	private static self $MAYBE;

	private static self $NO;

	/**
	 * @api
	 * @param list<string> $reasons Human-readable explanations of the type relationship
	 */
	public function __construct(
		public readonly TrinaryLogic $result,
		public readonly array $reasons,
	)
	{
	}

	/**
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->maybe()
	 */
	public function yes(): bool
	{
		return $this->result->yes();
	}

	/**
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function maybe(): bool
	{
		return $this->result->maybe();
	}

	/**
	 * @phpstan-assert-if-true =false $this->maybe()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function no(): bool
	{
		return $this->result->no();
	}

	public static function createYes(): self
	{
		return self::$YES ??= new self(TrinaryLogic::createYes(), []);
	}

	/** @param list<string> $reasons */
	public static function createNo(array $reasons = []): self
	{
		if ($reasons === []) {
			return self::$NO ??= new self(TrinaryLogic::createNo(), $reasons);
		}
		return new self(TrinaryLogic::createNo(), $reasons);
	}

	public static function createMaybe(): self
	{
		return self::$MAYBE ??= new self(TrinaryLogic::createMaybe(), []);
	}

	public static function createFromBoolean(bool $value): self
	{
		return new self(TrinaryLogic::createFromBoolean($value), []);
	}

	public function toAcceptsResult(): AcceptsResult
	{
		return new AcceptsResult($this->result, $this->reasons);
	}

	public function and(self ...$others): self
	{
		$results = [];
		$reasons = [];
		foreach ($others as $other) {
			$results[] = $other->result;
			$reasons[] = $other->reasons;
		}

		return new self(
			$this->result->and(...$results),
			array_values(array_unique(array_merge($this->reasons, ...$reasons))),
		);
	}

	public function or(self ...$others): self
	{
		$results = [];
		$reasons = [];
		foreach ($others as $other) {
			$results[] = $other->result;
			$reasons[] = $other->reasons;
		}

		return new self(
			$this->result->or(...$results),
			array_values(array_unique(array_merge($this->reasons, ...$reasons))),
		);
	}

	/** @param callable(string): string $cb */
	public function decorateReasons(callable $cb): self
	{
		$reasons = [];
		foreach ($this->reasons as $reason) {
			$reasons[] = $cb($reason);
		}

		return new self($this->result, $reasons);
	}

	/** @see TrinaryLogic::extremeIdentity() */
	public static function extremeIdentity(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$result = TrinaryLogic::extremeIdentity(...array_map(static fn (self $result) => $result->result, $operands));

		return new self($result, self::mergeReasons($operands));
	}

	/** @see TrinaryLogic::maxMin() */
	public static function maxMin(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$result = TrinaryLogic::maxMin(...array_map(static fn (self $result) => $result->result, $operands));

		return new self($result, self::mergeReasons($operands));
	}

	public function negate(): self
	{
		return new self($this->result->negate(), $this->reasons);
	}

	public function describe(): string
	{
		return $this->result->describe();
	}

	/**
	 * @param array<self> $operands
	 *
	 * @return list<string>
	 */
	private static function mergeReasons(array $operands): array
	{
		$reasons = [];
		foreach ($operands as $operand) {
			foreach ($operand->reasons as $reason) {
				$reasons[] = $reason;
			}
		}

		return array_values(array_unique($reasons));
	}

}
