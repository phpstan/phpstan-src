<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;
use function sprintf;

class TemplateTypeVarianceTest extends TestCase
{

	public function dataIsValidVariance(): iterable
	{
		foreach ([TemplateTypeVariance::createInvariant(), TemplateTypeVariance::createCovariant()] as $variance) {
			yield [
				$variance,
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			];

			yield [
				$variance,
				new IntegerType(),
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			];

			yield [
				$variance,
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				new IntegerType(),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			];

			yield [
				$variance,
				new StringType(),
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			];

			yield [
				$variance,
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				new StringType(),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			];

			yield [
				$variance,
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				new UnionType([new IntegerType(), new StringType()]),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			];

			yield [
				$variance,
				new UnionType([new IntegerType(), new StringType()]),
				new BenevolentUnionType([new IntegerType(), new StringType()]),
				TrinaryLogic::createYes(),
				TrinaryLogic::createYes(),
			];
		}
	}

	/**
	 * @dataProvider dataIsValidVariance
	 */
	public function testIsValidVariance(
		TemplateTypeVariance $variance,
		Type $a,
		Type $b,
		TrinaryLogic $expected,
		TrinaryLogic $expectedInversed,
	): void
	{
		$this->assertSame(
			$expected->describe(),
			$variance->isValidVariance($a, $b)->describe(),
			sprintf('%s->isValidVariance(%s, %s)', $variance->describe(), $a->describe(VerbosityLevel::precise()), $b->describe(VerbosityLevel::precise())),
		);
		$this->assertSame(
			$expectedInversed->describe(),
			$variance->isValidVariance($b, $a)->describe(),
			sprintf('%s->isValidVariance(%s, %s)', $variance->describe(), $b->describe(VerbosityLevel::precise()), $a->describe(VerbosityLevel::precise())),
		);
	}

}
