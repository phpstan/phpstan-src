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
	 * Returns true if this type is definitely a supertype.
	 *
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->maybe()
	 */
	public function yes(): bool
	{
		return $this->result->yes();
	}

	/**
	 * Returns true if the supertype relationship is uncertain.
	 *
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function maybe(): bool
	{
		return $this->result->maybe();
	}

	/**
	 * Returns true if this type is definitely not a supertype.
	 *
	 * @phpstan-assert-if-true =false $this->maybe()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function no(): bool
	{
		return $this->result->no();
	}

	/** Creates a definite supertype result with no reasons. */
	public static function createYes(): self
	{
		return new self(TrinaryLogic::createYes(), []);
	}

	/**
	 * Creates a definite non-supertype result with optional reasons.
	 *
	 * @param list<string> $reasons
	 */
	public static function createNo(array $reasons = []): self
	{
		return new self(TrinaryLogic::createNo(), $reasons);
	}

	/** Creates an uncertain supertype result with no reasons. */
	public static function createMaybe(): self
	{
		return new self(TrinaryLogic::createMaybe(), []);
	}

	/** Converts a boolean to an IsSuperTypeOfResult (true → Yes, false → No). */
	public static function createFromBoolean(bool $value): self
	{
		return new self(TrinaryLogic::createFromBoolean($value), []);
	}

	/**
	 * Converts this to an AcceptsResult, preserving the result and reasons.
	 *
	 * Used when an isSuperTypeOf() check is sufficient for an accepts() implementation.
	 */
	public function toAcceptsResult(): AcceptsResult
	{
		return new AcceptsResult($this->result, $this->reasons);
	}

	/**
	 * Logical AND — combines with other results, merging reasons.
	 */
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

	/**
	 * Logical OR — combines with other results, merging reasons.
	 */
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

	/**
	 * Transforms all reason strings using the given callback.
	 *
	 * @param callable(string): string $cb
	 */
	public function decorateReasons(callable $cb): self
	{
		$reasons = [];
		foreach ($this->reasons as $reason) {
			$reasons[] = $cb($reason);
		}

		return new self($this->result, $reasons);
	}

	/**
	 * Returns Yes if all operands agree, Maybe if any disagree.
	 *
	 * @see TrinaryLogic::extremeIdentity()
	 */
	public static function extremeIdentity(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$result = TrinaryLogic::extremeIdentity(...array_map(static fn (self $result) => $result->result, $operands));

		return new self($result, self::mergeReasons($operands));
	}

	/**
	 * Returns Yes if any operand is Yes, otherwise the minimum.
	 *
	 * @see TrinaryLogic::maxMin()
	 */
	public static function maxMin(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$result = TrinaryLogic::maxMin(...array_map(static fn (self $result) => $result->result, $operands));

		return new self($result, self::mergeReasons($operands));
	}

	/**
	 * Logical negation — Yes becomes No and vice versa, Maybe stays Maybe.
	 * Reasons are preserved.
	 */
	public function negate(): self
	{
		return new self($this->result->negate(), $this->reasons);
	}

	/**
	 * Returns a human-readable label: "Yes", "No", or "Maybe".
	 */
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
