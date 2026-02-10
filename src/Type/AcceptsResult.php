<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;

/**
 * Result of a Type::accepts() check — whether one type accepts another.
 *
 * Wraps a TrinaryLogic result together with human-readable reasons explaining
 * why the acceptance failed. These reasons are surfaced in PHPStan error messages
 * to help developers understand type mismatches.
 *
 * For example, when checking if `int` accepts `string`, the result would be No
 * with a reason like "string is not a subtype of int".
 *
 * The `accepts()` method is used to check assignability — whether a value of one
 * type can be assigned to a variable/parameter of another type. This is stricter
 * than `isSuperTypeOf()` because it accounts for PHPStan's rule level and
 * generics variance.
 *
 * @api
 */
final class AcceptsResult
{

	/**
	 * @api
	 * @param list<string> $reasons Human-readable explanations of why acceptance failed
	 */
	public function __construct(
		public readonly TrinaryLogic $result,
		public readonly array $reasons,
	)
	{
	}

	/**
	 * Returns true if the type is definitely accepted.
	 *
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->maybe()
	 */
	public function yes(): bool
	{
		return $this->result->yes();
	}

	/**
	 * Returns true if acceptance is uncertain.
	 *
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function maybe(): bool
	{
		return $this->result->maybe();
	}

	/**
	 * Returns true if the type is definitely not accepted.
	 *
	 * @phpstan-assert-if-true =false $this->maybe()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function no(): bool
	{
		return $this->result->no();
	}

	/** Creates a definite acceptance result with no reasons. */
	public static function createYes(): self
	{
		return new self(TrinaryLogic::createYes(), []);
	}

	/**
	 * Creates a definite rejection result with optional reasons.
	 *
	 * @param list<string> $reasons
	 */
	public static function createNo(array $reasons = []): self
	{
		return new self(TrinaryLogic::createNo(), $reasons);
	}

	/** Creates an uncertain acceptance result with no reasons. */
	public static function createMaybe(): self
	{
		return new self(TrinaryLogic::createMaybe(), []);
	}

	/** Converts a boolean to an AcceptsResult (true → Yes, false → No). */
	public static function createFromBoolean(bool $value): self
	{
		return new self(TrinaryLogic::createFromBoolean($value), []);
	}

	/**
	 * Logical AND — combines this result with another, merging reasons.
	 */
	public function and(self $other): self
	{
		return new self(
			$this->result->and($other->result),
			array_values(array_unique(array_merge($this->reasons, $other->reasons))),
		);
	}

	/**
	 * Logical OR — combines this result with another, merging reasons.
	 */
	public function or(self $other): self
	{
		return new self(
			$this->result->or($other->result),
			array_values(array_unique(array_merge($this->reasons, $other->reasons))),
		);
	}

	/**
	 * Transforms all reason strings using the given callback.
	 *
	 * Useful for adding context to reasons, e.g. wrapping them in
	 * "Parameter $foo: ..." format.
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
		$reasons = [];
		foreach ($operands as $operand) {
			foreach ($operand->reasons as $reason) {
				$reasons[] = $reason;
			}
		}

		return new self($result, array_values(array_unique($reasons)));
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
		$reasons = [];
		foreach ($operands as $operand) {
			foreach ($operand->reasons as $reason) {
				$reasons[] = $reason;
			}
		}

		return new self($result, array_values(array_unique($reasons)));
	}

}
