<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use function array_map;
use function array_unique;
use function array_values;
use function sprintf;

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
 * Reasons are computed lazily: `isSuperTypeOf()` runs on hot analysis paths, but the reason
 * strings are only ever read when a rule renders an error tip. Building them eagerly - and
 * propagating them through the type algebra via and()/or()/extremeIdentity() - made large
 * codebases spend their time constructing strings that were then thrown away. A reason-bearing
 * result therefore stores a factory closure; the public `$reasons` property materializes it on
 * first access through __get(), and the composition methods compose factories without forcing
 * them.
 *
 * @property-read list<string> $reasons Human-readable explanations of the type relationship
 *
 * @api
 */
final class IsSuperTypeOfResult
{

	private static self $YES;

	private static self $MAYBE;

	private static self $NO;

	/** @var list<string>|null */
	private ?array $materializedReasons;

	/** @var (Closure(): list<string>)|null */
	private ?Closure $reasonsFactory;

	/**
	 * @api
	 * @param list<string>|(Closure(): list<string>) $reasons Human-readable explanations of the type relationship
	 */
	public function __construct(
		public readonly TrinaryLogic $result,
		array|Closure $reasons,
	)
	{
		if ($reasons instanceof Closure) {
			$this->materializedReasons = null;
			$this->reasonsFactory = $reasons;
		} else {
			$this->materializedReasons = $reasons;
			$this->reasonsFactory = null;
		}
	}

	public function __get(string $name): mixed
	{
		if ($name === 'reasons') {
			return $this->getReasons();
		}

		throw new ShouldNotHappenException(sprintf('Access to an undefined property %s::$%s.', self::class, $name));
	}

	public function __isset(string $name): bool
	{
		return $name === 'reasons';
	}

	/** @return list<string> */
	public function getReasons(): array
	{
		if ($this->materializedReasons === null) {
			$factory = $this->reasonsFactory;
			$this->materializedReasons = $factory === null ? [] : $factory();
			$this->reasonsFactory = null;
		}

		return $this->materializedReasons;
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

	/** @param list<string>|(Closure(): list<string>) $reasons */
	public static function createNo(array|Closure $reasons = []): self
	{
		if ($reasons === []) {
			return self::$NO ??= new self(TrinaryLogic::createNo(), []);
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

	public function toAcceptsResult(): AcceptsResult
	{
		return new AcceptsResult($this->result, fn (): array => $this->getReasons());
	}

	public function and(self ...$others): self
	{
		$results = [];
		foreach ($others as $other) {
			$results[] = $other->result;
		}

		$operands = [$this, ...$others];

		return new self(
			$this->result->and(...$results),
			static fn (): array => self::mergeReasons($operands),
		);
	}

	public function or(self ...$others): self
	{
		$results = [];
		foreach ($others as $other) {
			$results[] = $other->result;
		}

		$operands = [$this, ...$others];

		return new self(
			$this->result->or(...$results),
			static fn (): array => self::mergeReasons($operands),
		);
	}

	/** @param callable(string): string $cb */
	public function decorateReasons(callable $cb): self
	{
		return new self(
			$this->result,
			fn (): array => array_map($cb, $this->getReasons()),
		);
	}

	/** @see TrinaryLogic::extremeIdentity() */
	public static function extremeIdentity(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$results = [];
		foreach ($operands as $operand) {
			$results[] = $operand->result;
		}

		return new self(
			TrinaryLogic::extremeIdentity(...$results),
			static fn (): array => self::mergeReasons($operands),
		);
	}

	/** @see TrinaryLogic::maxMin() */
	public static function maxMin(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$results = [];
		foreach ($operands as $operand) {
			$results[] = $operand->result;
		}

		return new self(
			TrinaryLogic::maxMin(...$results),
			static fn (): array => self::mergeReasons($operands),
		);
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
		$operands = [];
		$hasNo = false;
		foreach ($objects as $object) {
			$isSuperTypeOf = $callback($object);
			if ($isSuperTypeOf->result->yes()) {
				return $isSuperTypeOf;
			} elseif ($isSuperTypeOf->result->no()) {
				$hasNo = true;
			}

			$operands[] = $isSuperTypeOf;
		}

		return new self(
			$hasNo ? TrinaryLogic::createNo() : TrinaryLogic::createMaybe(),
			static fn (): array => self::mergeReasons($operands),
		);
	}

	public function negate(): self
	{
		return new self($this->result->negate(), fn (): array => $this->getReasons());
	}

	public function describe(): string
	{
		return $this->result->describe();
	}

	/**
	 * @param array<self> $operands
	 * @return list<string>
	 */
	private static function mergeReasons(array $operands): array
	{
		$reasons = [];
		foreach ($operands as $operand) {
			foreach ($operand->getReasons() as $reason) {
				$reasons[] = $reason;
			}
		}

		return array_values(array_unique($reasons));
	}

}
