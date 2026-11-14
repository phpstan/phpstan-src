<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\TrinaryLogic;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;

/**
 * TrinaryLogic is combined everywhere types are compared, so each case combines it a thousand times.
 */
final class TrinaryLogicBench
{

	private const CALLS = 1000;

	/** @var list<TrinaryLogic> */
	private array $operands = [];

	/** @var list<int> */
	private array $objects = [];

	/**
	 * One operand is the common case, which and() and or() take without an array.
	 *
	 * @return iterable<string, array{int}>
	 */
	public static function operands(): iterable
	{
		yield 'operands-1' => [1];
		yield 'operands-4' => [4];
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function fourOperands(): iterable
	{
		yield 'operands-4' => [4];
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function objects(): iterable
	{
		yield 'objects-8' => [8];
	}

	#[Before]
	public function boot(int $count): void
	{
		$values = [TrinaryLogic::createMaybe(), TrinaryLogic::createYes(), TrinaryLogic::createMaybe(), TrinaryLogic::createNo()];
		$this->operands = [];
		$this->objects = [];
		for ($i = 0; $i < $count; $i++) {
			$this->operands[] = $values[$i % 4];
			$this->objects[] = $i;
		}
	}

	#[DataProvider(method: 'operands')]
	public function benchAnd(int $operands): TrinaryLogic
	{
		$result = TrinaryLogic::createYes();
		$operandList = $this->operands;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = TrinaryLogic::createYes()->and(...$operandList);
		}

		return $result;
	}

	#[DataProvider(method: 'operands')]
	public function benchOr(int $operands): TrinaryLogic
	{
		$result = TrinaryLogic::createNo();
		$operandList = $this->operands;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = TrinaryLogic::createNo()->or(...$operandList);
		}

		return $result;
	}

	#[DataProvider(method: 'fourOperands')]
	public function benchMaxMin(int $operands): TrinaryLogic
	{
		$result = TrinaryLogic::createNo();
		$operandList = $this->operands;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = TrinaryLogic::maxMin(...$operandList);
		}

		return $result;
	}

	/**
	 * The lazy methods stop at the first no or yes, so every callback answers maybe.
	 */
	#[DataProvider(method: 'objects')]
	public function benchLazyAnd(int $objects): TrinaryLogic
	{
		$result = TrinaryLogic::createYes();
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = TrinaryLogic::createYes()->lazyAnd($this->objects, static fn (int $object): TrinaryLogic => TrinaryLogic::createMaybe());
		}

		return $result;
	}

	/**
	 * The lazy methods stop at the first no or yes, so every callback answers maybe.
	 */
	#[DataProvider(method: 'objects')]
	public function benchLazyOr(int $objects): TrinaryLogic
	{
		$result = TrinaryLogic::createNo();
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = TrinaryLogic::createNo()->lazyOr($this->objects, static fn (int $object): TrinaryLogic => TrinaryLogic::createMaybe());
		}

		return $result;
	}

	/**
	 * The lazy methods stop at the first no or yes, so every callback answers maybe.
	 */
	#[DataProvider(method: 'objects')]
	public function benchLazyMaxMin(int $objects): TrinaryLogic
	{
		$result = TrinaryLogic::createNo();
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = TrinaryLogic::lazyMaxMin($this->objects, static fn (int $object): TrinaryLogic => TrinaryLogic::createMaybe());
		}

		return $result;
	}

}
