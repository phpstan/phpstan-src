<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
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
 * Reasons can also be provided lazily (as `Closure(): string`) via $lazyReasons. This lets a
 * type comparison attach an expensive-to-build explanation (e.g. one that calls describe() on
 * large array shapes) without paying for it on the hot path — the closure only runs when
 * getReasons() is called, i.e. when the reason is actually rendered.
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
	 * @param list<Closure(): string> $lazyReasons Reasons built on demand, see the class docblock
	 */
	public function __construct(
		public readonly TrinaryLogic $result,
		public readonly array $reasons,
		public readonly array $lazyReasons = [],
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

	/**
	 * All reasons with the lazy ones materialized. Prefer this over reading $reasons directly
	 * when the reasons are going to be rendered.
	 *
	 * @return list<string>
	 */
	public function getReasons(): array
	{
		if ($this->lazyReasons === []) {
			return $this->reasons;
		}

		return array_values(array_unique(array_merge(
			$this->reasons,
			array_map(static fn (Closure $cb): string => $cb(), $this->lazyReasons),
		)));
	}

	public static function createYes(): self
	{
		return self::$YES ??= new self(TrinaryLogic::createYes(), []);
	}

	/**
	 * @param list<string> $reasons
	 * @param list<Closure(): string> $lazyReasons
	 */
	public static function createNo(array $reasons = [], array $lazyReasons = []): self
	{
		if ($reasons === [] && $lazyReasons === []) {
			return self::$NO ??= new self(TrinaryLogic::createNo(), $reasons);
		}
		return new self(TrinaryLogic::createNo(), $reasons, $lazyReasons);
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

	public function toAcceptsResult(): AcceptsResult
	{
		return new AcceptsResult($this->result, $this->getReasons());
	}

	public function and(self ...$others): self
	{
		$results = [];
		$reasons = [];
		$lazyReasons = [];
		foreach ($others as $other) {
			$results[] = $other->result;
			$reasons[] = $other->reasons;
			$lazyReasons[] = $other->lazyReasons;
		}

		return new self(
			$this->result->and(...$results),
			array_values(array_unique(array_merge($this->reasons, ...$reasons))),
			array_merge($this->lazyReasons, ...$lazyReasons),
		);
	}

	public function or(self ...$others): self
	{
		$results = [];
		$reasons = [];
		$lazyReasons = [];
		foreach ($others as $other) {
			$results[] = $other->result;
			$reasons[] = $other->reasons;
			$lazyReasons[] = $other->lazyReasons;
		}

		return new self(
			$this->result->or(...$results),
			array_values(array_unique(array_merge($this->reasons, ...$reasons))),
			array_merge($this->lazyReasons, ...$lazyReasons),
		);
	}

	/** @param callable(string): string $cb */
	public function decorateReasons(callable $cb): self
	{
		$reasons = [];
		foreach ($this->reasons as $reason) {
			$reasons[] = $cb($reason);
		}

		$lazyReasons = [];
		foreach ($this->lazyReasons as $lazyReason) {
			$lazyReasons[] = static fn (): string => $cb($lazyReason());
		}

		return new self($this->result, $reasons, $lazyReasons);
	}

	/** @see TrinaryLogic::extremeIdentity() */
	public static function extremeIdentity(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$results = [];
		$reasons = [];
		$lazyReasons = [];
		foreach ($operands as $operand) {
			$results[] = $operand->result;
			foreach ($operand->reasons as $reason) {
				$reasons[] = $reason;
			}
			foreach ($operand->lazyReasons as $lazyReason) {
				$lazyReasons[] = $lazyReason;
			}
		}

		return new self(TrinaryLogic::extremeIdentity(...$results), array_values(array_unique($reasons)), $lazyReasons);
	}

	/** @see TrinaryLogic::maxMin() */
	public static function maxMin(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$results = [];
		$reasons = [];
		$lazyReasons = [];
		foreach ($operands as $operand) {
			$results[] = $operand->result;
			foreach ($operand->reasons as $reason) {
				$reasons[] = $reason;
			}
			foreach ($operand->lazyReasons as $lazyReason) {
				$lazyReasons[] = $lazyReason;
			}
		}

		return new self(TrinaryLogic::maxMin(...$results), array_values(array_unique($reasons)), $lazyReasons);
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
		$lazyReasons = [];
		$hasNo = false;
		foreach ($objects as $object) {
			$isSuperTypeOf = $callback($object);
			if ($isSuperTypeOf->result->yes()) {
				return $isSuperTypeOf;
			} elseif ($isSuperTypeOf->result->no()) {
				$hasNo = true;
			}

			foreach ($isSuperTypeOf->reasons as $reason) {
				$reasons[] = $reason;
			}
			foreach ($isSuperTypeOf->lazyReasons as $lazyReason) {
				$lazyReasons[] = $lazyReason;
			}
		}

		return new self(
			$hasNo ? TrinaryLogic::createNo() : TrinaryLogic::createMaybe(),
			array_values(array_unique($reasons)),
			$lazyReasons,
		);
	}

	public function negate(): self
	{
		return new self($this->result->negate(), $this->reasons, $this->lazyReasons);
	}

	public function describe(): string
	{
		return $this->result->describe();
	}

}
