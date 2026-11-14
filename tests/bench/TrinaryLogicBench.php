<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\TrinaryLogic;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;

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
	#[ExpectedFor(dataSet: 'operands-1', instructions: '1.111M', cycles: '1.135M', memory: '0B')]
	#[ExpectedFor(dataSet: 'operands-4', instructions: '2.356M', cycles: '2.387M', memory: '216B')]
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
	#[ExpectedFor(dataSet: 'operands-1', instructions: '1.111M', cycles: '1.136M', memory: '0B')]
	#[ExpectedFor(dataSet: 'operands-4', instructions: '2.356M', cycles: '2.388M', memory: '216B')]
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
	#[ExpectedFor(dataSet: 'operands-4', instructions: '2.739M', cycles: '2.771M', memory: '216B')]
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
	#[ExpectedFor(dataSet: 'objects-8', instructions: '11.62M', cycles: '11.92M', memory: '832B')]
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
	#[ExpectedFor(dataSet: 'objects-8', instructions: '11.62M', cycles: '11.98M', memory: '832B')]
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
	#[ExpectedFor(dataSet: 'objects-8', instructions: '8.283M', cycles: '8.323M', memory: '400B')]
	public function benchLazyMaxMin(int $objects): TrinaryLogic
	{
		$result = TrinaryLogic::createNo();
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = TrinaryLogic::lazyMaxMin($this->objects, static fn (int $object): TrinaryLogic => TrinaryLogic::createMaybe());
		}

		return $result;
	}

}
