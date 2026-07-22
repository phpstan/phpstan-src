<?php declare(strict_types = 1);

namespace PHPStan;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Turbo\ShadowedByTurboExtension;
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
#[ShadowedByTurboExtension(turboClass: 'PHPStanTurbo\TrinaryLogic', implementation: __DIR__ . '/../turbo-ext/src/TrinaryLogic.cpp')]
#[ReferencedByTurboExtension(key: 'trinaryLogicImpl')]
final class TrinaryLogic
{

	private const YES = 3;
	private const MAYBE = 1;
	private const NO = 0;

	/** @var self[] */
	private static array $registry = [];

	private static self $YES;

	private static self $MAYBE;

	private static self $NO;

	private function __construct(private int $value)
	{
	}

	public static function createYes(): self
	{
		return self::$YES ??= (self::$registry[self::YES] ??= new self(self::YES));
	}

	public static function createNo(): self
	{
		return self::$NO ??= (self::$registry[self::NO] ??= new self(self::NO));
	}

	public static function createMaybe(): self
	{
		return self::$MAYBE ??= (self::$registry[self::MAYBE] ??= new self(self::MAYBE));
	}

	public static function createFromBoolean(bool $value): self
	{
		$yesNo = $value ? self::YES : self::NO;
		return self::$registry[$yesNo] ??= new self($yesNo);
	}

	private static function create(int $value): self
	{
		return self::$registry[$value] ??= new self($value);
	}

	/**
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->maybe()
	 */
	public function yes(): bool
	{
		return $this->value === self::YES;
	}

	/**
	 * @phpstan-assert-if-true =false $this->no()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function maybe(): bool
	{
		return $this->value === self::MAYBE;
	}

	/**
	 * @phpstan-assert-if-true =false $this->maybe()
	 * @phpstan-assert-if-true =false $this->yes()
	 */
	public function no(): bool
	{
		return $this->value === self::NO;
	}

	public function toBooleanType(): BooleanType
	{
		if ($this->value === self::MAYBE) {
			return new BooleanType();
		}

		return new ConstantBooleanType($this->value === self::YES);
	}

	public function and(?self $operand = null, self ...$rest): self
	{
		$min = $this->value & ($operand !== null ? $operand->value : self::YES);
		foreach ($rest as $restOperand) {
			$min &= $restOperand->value;
		}
		return self::$registry[$min] ??= new self($min);
	}

	/**
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

	public function or(?self $operand = null, self ...$rest): self
	{
		$max = $this->value | ($operand !== null ? $operand->value : self::NO);
		foreach ($rest as $restOperand) {
			$max |= $restOperand->value;
		}
		return self::$registry[$max] ??= new self($max);
	}

	/**
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
	 * Returns the operands' value if they all agree, Maybe if any differ.
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
	 */
	public static function maxMin(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$max = self::NO;
		$min = self::YES;
		foreach ($operands as $operand) {
			$max |= $operand->value;
			$min &= $operand->value;
		}
		$maxMin = $max === self::YES ? self::YES : $min;

		return self::$registry[$maxMin] ??= new self($maxMin);
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
		$min = self::YES;
		foreach ($objects as $object) {
			$result = $callback($object);
			if ($result->value === self::YES) {
				return $result;
			}

			$min &= $result->value;
		}

		return self::$registry[$min] ??= new self($min);
	}

	public function negate(): self
	{
		// 0b11 >> 0 == 0b11 (3)
		// 0b11 >> 1 == 0b01 (1)
		// 0b11 >> 3 == 0b00 (0)
		return self::create(3 >> $this->value);
	}

	public function equals(self $other): bool
	{
		return $this === $other;
	}

	/**
	 * Returns the stronger of the two values, or null if they are equal (Yes > Maybe > No).
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
