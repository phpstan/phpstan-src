<?php declare(strict_types = 1);

namespace PHPStan;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TrinaryLogicTest extends PHPStanTestCase
{

	public static function dataAnd(): array
	{
		return [
			[TrinaryLogic::createNo(), TrinaryLogic::createNo()],
			[TrinaryLogic::createMaybe(), TrinaryLogic::createMaybe()],
			[TrinaryLogic::createYes(), TrinaryLogic::createYes()],

			[TrinaryLogic::createNo(), TrinaryLogic::createNo(), TrinaryLogic::createNo()],
			[TrinaryLogic::createNo(), TrinaryLogic::createNo(), TrinaryLogic::createMaybe()],
			[TrinaryLogic::createNo(), TrinaryLogic::createNo(), TrinaryLogic::createYes()],

			[TrinaryLogic::createNo(), TrinaryLogic::createMaybe(), TrinaryLogic::createNo()],
			[TrinaryLogic::createMaybe(), TrinaryLogic::createMaybe(), TrinaryLogic::createMaybe()],
			[TrinaryLogic::createMaybe(), TrinaryLogic::createMaybe(), TrinaryLogic::createYes()],

			[TrinaryLogic::createNo(), TrinaryLogic::createYes(), TrinaryLogic::createNo()],
			[TrinaryLogic::createMaybe(), TrinaryLogic::createYes(), TrinaryLogic::createMaybe()],
			[TrinaryLogic::createYes(), TrinaryLogic::createYes(), TrinaryLogic::createYes()],
		];
	}

	#[DataProvider('dataAnd')]
	public function testAnd(
		TrinaryLogic $expectedResult,
		TrinaryLogic $value,
		TrinaryLogic ...$operands,
	): void
	{
		$this->assertTrue($expectedResult->equals($value->and(...$operands)));
	}

	#[DataProvider('dataAnd')]
	public function testLazyAnd(
		TrinaryLogic $expectedResult,
		TrinaryLogic $value,
		TrinaryLogic ...$operands,
	): void
	{
		$this->assertTrue($expectedResult->equals($value->lazyAnd($operands, static fn (TrinaryLogic $result) => $result)));
	}

	public static function dataOr(): array
	{
		return [
			[TrinaryLogic::createNo(), TrinaryLogic::createNo()],
			[TrinaryLogic::createMaybe(), TrinaryLogic::createMaybe()],
			[TrinaryLogic::createYes(), TrinaryLogic::createYes()],

			[TrinaryLogic::createNo(), TrinaryLogic::createNo(), TrinaryLogic::createNo()],
			[TrinaryLogic::createMaybe(), TrinaryLogic::createNo(), TrinaryLogic::createMaybe()],
			[TrinaryLogic::createYes(), TrinaryLogic::createNo(), TrinaryLogic::createYes()],

			[TrinaryLogic::createMaybe(), TrinaryLogic::createMaybe(), TrinaryLogic::createNo()],
			[TrinaryLogic::createMaybe(), TrinaryLogic::createMaybe(), TrinaryLogic::createMaybe()],
			[TrinaryLogic::createYes(), TrinaryLogic::createMaybe(), TrinaryLogic::createYes()],

			[TrinaryLogic::createYes(), TrinaryLogic::createYes(), TrinaryLogic::createNo()],
			[TrinaryLogic::createYes(), TrinaryLogic::createYes(), TrinaryLogic::createMaybe()],
			[TrinaryLogic::createYes(), TrinaryLogic::createYes(), TrinaryLogic::createYes()],
		];
	}

	#[DataProvider('dataOr')]
	public function testOr(
		TrinaryLogic $expectedResult,
		TrinaryLogic $value,
		TrinaryLogic ...$operands,
	): void
	{
		$this->assertTrue($expectedResult->equals($value->or(...$operands)));
	}

	#[DataProvider('dataOr')]
	public function testLazyOr(
		TrinaryLogic $expectedResult,
		TrinaryLogic $value,
		TrinaryLogic ...$operands,
	): void
	{
		$this->assertTrue($expectedResult->equals($value->lazyOr($operands, static fn (TrinaryLogic $result) => $result)));
	}

	public static function dataNegate(): array
	{
		return [
			[TrinaryLogic::createNo(), TrinaryLogic::createYes()],
			[TrinaryLogic::createMaybe(), TrinaryLogic::createMaybe()],
			[TrinaryLogic::createYes(), TrinaryLogic::createNo()],
		];
	}

	#[DataProvider('dataNegate')]
	public function testNegate(TrinaryLogic $expectedResult, TrinaryLogic $operand): void
	{
		$this->assertTrue($expectedResult->equals($operand->negate()));
	}

	public static function dataCompareTo(): array
	{
		$yes = TrinaryLogic::createYes();
		$maybe = TrinaryLogic::createMaybe();
		$no = TrinaryLogic::createNo();
		return [
			[
				$yes,
				$yes,
				null,
			],
			[
				$maybe,
				$maybe,
				null,
			],
			[
				$no,
				$no,
				null,
			],
			[
				$yes,
				$maybe,
				$yes,
			],
			[
				$yes,
				$no,
				$yes,
			],
			[
				$maybe,
				$no,
				$maybe,
			],
		];
	}

	#[DataProvider('dataCompareTo')]
	public function testCompareTo(TrinaryLogic $first, TrinaryLogic $second, ?TrinaryLogic $expected): void
	{
		$this->assertSame(
			$expected,
			$first->compareTo($second),
		);
	}

	#[DataProvider('dataCompareTo')]
	public function testCompareToInversed(TrinaryLogic $first, TrinaryLogic $second, ?TrinaryLogic $expected): void
	{
		$this->assertSame(
			$expected,
			$second->compareTo($first),
		);
	}

}
