<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;

/**
 * @api
 */
final class IsSuperTypeOfResult
{

	/**
	 * @api
	 * @param list<string> $reasons
	 */
	public function __construct(
		public readonly TrinaryLogic $result,
		public readonly array $reasons,
	)
	{
	}

	public function yes(): bool
	{
		return $this->result->yes();
	}

	public function maybe(): bool
	{
		return $this->result->maybe();
	}

	public function no(): bool
	{
		return $this->result->no();
	}

	public static function createYes(): self
	{
		return new self(TrinaryLogic::createYes(), []);
	}

	/**
	 * @param list<string> $reasons
	 */
	public static function createNo(array $reasons = []): self
	{
		return new self(TrinaryLogic::createNo(), $reasons);
	}

	public static function createMaybe(): self
	{
		return new self(TrinaryLogic::createMaybe(), []);
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

	/**
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

	public static function extremeIdentity(self ...$operands): self
	{
		if ($operands === []) {
			throw new ShouldNotHappenException();
		}

		$result = TrinaryLogic::extremeIdentity(...array_map(static fn (self $result) => $result->result, $operands));

		return new self($result, self::mergeReasons($operands));
	}

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

		return $reasons;
	}

}
