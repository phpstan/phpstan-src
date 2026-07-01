<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function sprintf;

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
 * Reasons are computed lazily (see IsSuperTypeOfResult for the rationale): they are only
 * read when a rule renders an error tip, so building them eagerly on the hot `accepts()`
 * path wasted most of the analysis time. The public `$reasons` property is materialized on
 * first access through __get(); the composition methods compose factories without forcing them.
 *
 * @property-read list<string> $reasons Human-readable explanations of why acceptance failed
 *
 * @api
 */
final class AcceptsResult
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
	 * @param list<string>|(Closure(): list<string>) $reasons Human-readable explanations of why acceptance failed
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

	public function and(self $other): self
	{
		return new self(
			$this->result->and($other->result),
			fn (): array => array_values(array_unique(array_merge($this->getReasons(), $other->getReasons()))),
		);
	}

	public function or(self $other): self
	{
		return new self(
			$this->result->or($other->result),
			fn (): array => array_values(array_unique(array_merge($this->getReasons(), $other->getReasons()))),
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
			$isAcceptedBy = $callback($object);
			if ($isAcceptedBy->result->yes()) {
				return $isAcceptedBy;
			} elseif ($isAcceptedBy->result->no()) {
				$hasNo = true;
			}

			$operands[] = $isAcceptedBy;
		}

		return new self(
			$hasNo ? TrinaryLogic::createNo() : TrinaryLogic::createMaybe(),
			static fn (): array => self::mergeReasons($operands),
		);
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
