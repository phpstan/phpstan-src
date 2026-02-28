<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
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

	private static self $YES;

	private static self $MAYBE;

	private static self $NO;

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
		if ($value === true) {
			return self::createYes();
		}
		return self::createNo();
	}

	public function and(self $other): self
	{
		return new self(
			$this->result->and($other->result),
			array_values(array_unique(array_merge($this->reasons, $other->reasons))),
		);
	}

	public function or(self $other): self
	{
		return new self(
			$this->result->or($other->result),
			array_values(array_unique(array_merge($this->reasons, $other->reasons))),
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

		$results = [];
		$reasons = [];
		foreach ($operands as $operand) {
			$results[] = $operand->result;
			foreach ($operand->reasons as $reason) {
				$reasons[] = $reason;
			}
		}

		return new self(TrinaryLogic::extremeIdentity(...$results), array_values(array_unique($reasons)));
	}

	/** @see TrinaryLogic::maxMin() */
	public static function maxMin(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$results = [];
		$reasons = [];
		foreach ($operands as $operand) {
			$results[] = $operand->result;
			foreach ($operand->reasons as $reason) {
				$reasons[] = $reason;
			}
		}

		return new self(TrinaryLogic::maxMin(...$results), array_values(array_unique($reasons)));
	}

	/**
	 * @template T
	 * @param T[] $objects
	 * @param callable(T): self $callback
	 */
	public static function lazyMaxMin(
		array $objects,
		callable $callback,
	): self
	{
		$reasons = [];
		$hasNo = false;
		foreach ($objects as $object) {
			$isAcceptedBy = $callback($object);
			if ($isAcceptedBy->result->yes()) {
				return $isAcceptedBy;
			} elseif ($isAcceptedBy->result->no()) {
				$hasNo = true;
			}

			foreach ($isAcceptedBy->reasons as $reason) {
				$reasons[] = $reason;
			}
		}

		return new self(
			$hasNo ? TrinaryLogic::createNo() : TrinaryLogic::createMaybe(),
			array_values(array_unique($reasons)),
		);
	}

}
