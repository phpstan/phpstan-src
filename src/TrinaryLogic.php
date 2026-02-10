<?php declare(strict_types = 1);

namespace PHPStan;

use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use function array_column;
use function max;
use function min;

/**
 * Three-valued logic used throughout PHPStan's type system.
 *
 * Unlike boolean logic, TrinaryLogic has three states: Yes, No, and Maybe.
 * This is essential for static analysis because type relationships aren't always
 * certain. For example, a `mixed` type *might* be a string — that's `Maybe`.
 *
 * Many Type methods return TrinaryLogic instead of bool because the answer may
 * depend on runtime values that can't be known statically. Extension developers
 * encounter TrinaryLogic extensively when querying type properties:
 *
 *     if ($type->isString()->yes()) {
 *         // Definitely a string
 *     }
 *     if ($type->isString()->maybe()) {
 *         // Could be a string (e.g. mixed)
 *     }
 *     if ($type->isString()->no()) {
 *         // Definitely not a string
 *     }
 *
 * TrinaryLogic supports logical operations (and, or, negate) that propagate
 * uncertainty correctly. It is used as a flyweight — instances are cached and
 * compared by identity.
 *
 * @api
 * @see https://phpstan.org/developing-extensions/trinary-logic
 */
final class TrinaryLogic
{

	private const YES = 1;
	private const MAYBE = 0;
	private const NO = -1;

	/** @var self[] */
	private static array $registry = [];

	private function __construct(private int $value)
	{
	}

	/**
	 * Creates a TrinaryLogic representing definite truth.
	 *
	 * Use when the answer is unconditionally true — e.g. `StringType::isString()`
	 * returns `TrinaryLogic::createYes()`.
	 */
	public static function createYes(): self
	{
		return self::$registry[self::YES] ??= new self(self::YES);
	}

	/**
	 * Creates a TrinaryLogic representing definite falsehood.
	 *
	 * Use when the answer is unconditionally false — e.g. `IntegerType::isString()`
	 * returns `TrinaryLogic::createNo()`.
	 */
	public static function createNo(): self
	{
		return self::$registry[self::NO] ??= new self(self::NO);
	}

	/**
	 * Creates a TrinaryLogic representing uncertainty.
	 *
	 * Use when the answer cannot be determined statically — e.g. `MixedType::isString()`
	 * returns `TrinaryLogic::createMaybe()` because mixed could be a string at runtime.
	 */
	public static function createMaybe(): self
	{
		return self::$registry[self::MAYBE] ??= new self(self::MAYBE);
	}

	/**
	 * Converts a boolean to TrinaryLogic (true → Yes, false → No).
	 *
	 * Useful when the answer is definitively known but comes from a boolean expression.
	 */
	public static function createFromBoolean(bool $value): self
	{
		$yesNo = $value ? self::YES : self::NO;
		return self::$registry[$yesNo] ??= new self($yesNo);
	}

	private static function create(int $value): self
	{
		self::$registry[$value] ??= new self($value);
		return self::$registry[$value];
	}

	/**
	 * Returns true if this represents definite truth (Yes).
	 *
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->maybe()
	 */
	public function yes(): bool
	{
		return $this->value === self::YES;
	}

	/**
	 * Returns true if this represents uncertainty (Maybe).
	 *
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function maybe(): bool
	{
		return $this->value === self::MAYBE;
	}

	/**
	 * Returns true if this represents definite falsehood (No).
	 *
	 * @phpstan-assert-if-true =false $this->maybe()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function no(): bool
	{
		return $this->value === self::NO;
	}

	/**
	 * Converts this TrinaryLogic to a BooleanType.
	 *
	 * Yes → ConstantBooleanType(true), No → ConstantBooleanType(false),
	 * Maybe → BooleanType (either true or false).
	 */
	public function toBooleanType(): BooleanType
	{
		if ($this->value === self::MAYBE) {
			return new BooleanType();
		}

		return new ConstantBooleanType($this->value === self::YES);
	}

	/**
	 * Logical AND — returns the minimum of all operands.
	 *
	 * Truth table: Yes ∧ Yes = Yes, Yes ∧ Maybe = Maybe, anything ∧ No = No.
	 */
	public function and(self ...$operands): self
	{
		$min = $this->value;
		foreach ($operands as $operand) {
			if ($operand->value >= $min) {
				continue;
			}

			$min = $operand->value;
		}
		return self::create($min);
	}

	/**
	 * Lazy logical AND that short-circuits on No.
	 *
	 * Evaluates callbacks only until a No result is found, then stops.
	 * More efficient than computing all results when early termination is likely.
	 *
	 * @template T
	 * @param T[] $objects
	 * @param callable(T): self $callback
	 */
	public function lazyAnd(
		array $objects,
		callable $callback,
	): self
	{
		if ($this->value === self::NO) {
			return $this;
		}

		$results = [];
		foreach ($objects as $object) {
			$result = $callback($object);
			if ($result->value === self::NO) {
				return $result;
			}

			$results[] = $result;
		}

		return $this->and(...$results);
	}

	/**
	 * Logical OR — returns the maximum of all operands.
	 *
	 * Truth table: No ∨ No = No, No ∨ Maybe = Maybe, anything ∨ Yes = Yes.
	 */
	public function or(self ...$operands): self
	{
		$max = $this->value;
		foreach ($operands as $operand) {
			if ($operand->value < $max) {
				continue;
			}

			$max = $operand->value;
		}
		return self::create($max);
	}

	/**
	 * Lazy logical OR that short-circuits on Yes.
	 *
	 * Evaluates callbacks only until a Yes result is found, then stops.
	 *
	 * @template T
	 * @param T[] $objects
	 * @param callable(T): self $callback
	 */
	public function lazyOr(
		array $objects,
		callable $callback,
	): self
	{
		if ($this->value === self::YES) {
			return $this;
		}

		$results = [];
		foreach ($objects as $object) {
			$result = $callback($object);
			if ($result->value === self::YES) {
				return $result;
			}

			$results[] = $result;
		}

		return $this->or(...$results);
	}

	/**
	 * Returns Yes if all operands are identical, No if all are identical and No, Maybe otherwise.
	 *
	 * Used when combining results from multiple sources where they must all agree.
	 * If any two operands differ, the result is Maybe.
	 */
	public static function extremeIdentity(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}
		$operandValues = array_column($operands, 'value');
		$min = min($operandValues);
		$max = max($operandValues);
		return self::create($min === $max ? $min : self::MAYBE);
	}

	/**
	 * Lazy version of extremeIdentity() that short-circuits when operands disagree.
	 *
	 * @template T
	 * @param T[] $objects
	 * @param callable(T): self $callback
	 */
	public static function lazyExtremeIdentity(
		array $objects,
		callable $callback,
	): self
	{
		if ($objects === []) {
			throw new ShouldNotHappenException();
		}

		$lastResult = null;
		foreach ($objects as $object) {
			$result = $callback($object);
			if ($lastResult === null) {
				$lastResult = $result;
				continue;
			}
			if ($lastResult->equals($result)) {
				continue;
			}

			return self::createMaybe();
		}

		return $lastResult;
	}

	/**
	 * Returns Yes if any operand is Yes, otherwise the minimum.
	 *
	 * Useful for combining results where a single Yes is sufficient to
	 * confirm, but No requires all operands to be No.
	 */
	public static function maxMin(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}
		$operandValues = array_column($operands, 'value');
		return self::create(max($operandValues) > 0 ? 1 : min($operandValues));
	}

	/**
	 * Lazy version of maxMin() that short-circuits on Yes.
	 *
	 * @template T
	 * @param T[] $objects
	 * @param callable(T): self $callback
	 */
	public static function lazyMaxMin(
		array $objects,
		callable $callback,
	): self
	{
		$results = [];
		foreach ($objects as $object) {
			$result = $callback($object);
			if ($result->value === self::YES) {
				return $result;
			}

			$results[] = $result;
		}

		return self::maxMin(...$results);
	}

	/**
	 * Logical negation — Yes becomes No, No becomes Yes, Maybe stays Maybe.
	 */
	public function negate(): self
	{
		return self::create(-$this->value);
	}

	/**
	 * Returns true if both TrinaryLogic values are the same state.
	 *
	 * Uses identity comparison since TrinaryLogic is a flyweight.
	 */
	public function equals(self $other): bool
	{
		return $this === $other;
	}

	/**
	 * Returns the stronger of the two values, or null if they are equal.
	 *
	 * Yes > Maybe > No. Used when determining which branch provides
	 * more information about a type.
	 */
	public function compareTo(self $other): ?self
	{
		if ($this->value > $other->value) {
			return $this;
		} elseif ($other->value > $this->value) {
			return $other;
		}

		return null;
	}

	/**
	 * Returns a human-readable label: "Yes", "No", or "Maybe".
	 */
	public function describe(): string
	{
		static $labels = [
			self::NO => 'No',
			self::MAYBE => 'Maybe',
			self::YES => 'Yes',
		];

		return $labels[$this->value];
	}

}
