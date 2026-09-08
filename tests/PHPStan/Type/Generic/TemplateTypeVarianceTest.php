<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class TemplateTypeVarianceTest extends PHPStanTestCase
{

	public static function dataIsValidVariance(): iterable
	{
		$benevolent = static fn (): BenevolentUnionType => new BenevolentUnionType([new IntegerType(), new StringType()]);
		$yes = TrinaryLogic::createYes();
		$maybe = TrinaryLogic::createMaybe();

		foreach ([TemplateTypeVariance::createInvariant(), TemplateTypeVariance::createCovariant()] as $variance) {
			yield [$variance, $benevolent(), $benevolent(), $yes, $yes, $yes, $yes];
			yield [$variance, new IntegerType(), $benevolent(), $yes, $yes, $maybe, $yes];
			yield [$variance, $benevolent(), new IntegerType(), $yes, $yes, $yes, $maybe];
			yield [$variance, new StringType(), $benevolent(), $yes, $yes, $maybe, $yes];
			yield [$variance, $benevolent(), new StringType(), $yes, $yes, $yes, $maybe];
			yield [$variance, new MixedType(), new IntegerType(), $yes, $yes, $yes, $maybe];
		}

		yield [TemplateTypeVariance::createInvariant(), $benevolent(), new UnionType([new IntegerType(), new StringType()]), $yes, $yes, $yes, $maybe];
		yield [TemplateTypeVariance::createInvariant(), new UnionType([new IntegerType(), new StringType()]), $benevolent(), $yes, $yes, $maybe, $yes];

		yield [TemplateTypeVariance::createCovariant(), $benevolent(), new UnionType([new IntegerType(), new StringType()]), $yes, $yes, $yes, $yes];
		yield [TemplateTypeVariance::createCovariant(), new UnionType([new IntegerType(), new StringType()]), $benevolent(), $yes, $yes, $yes, $yes];

		yield [TemplateTypeVariance::createContravariant(), new MixedType(), new IntegerType(), $yes, $yes, $maybe, $yes];
		yield [TemplateTypeVariance::createContravariant(), new IntegerType(), $benevolent(), $yes, $yes, $yes, $maybe];
	}

	#[DataProvider('dataIsValidVariance')]
	public function testIsValidVariance(
		TemplateTypeVariance $variance,
		Type $a,
		Type $b,
		TrinaryLogic $expected,
		TrinaryLogic $expectedInversed,
		TrinaryLogic $expectedStrict,
		TrinaryLogic $expectedStrictInversed,
	): void
	{
		$templateType = TemplateTypeFactory::create(TemplateTypeScope::createWithFunction('foo'), 'T', null, $variance);
		$this->assertSame(
			$expected->describe(),
			$variance->isValidVariance($templateType, $a, $b)->result->describe(),
			sprintf('%s->isValidVariance(%s, %s)', $variance->describe(), $a->describe(VerbosityLevel::precise()), $b->describe(VerbosityLevel::precise())),
		);
		$this->assertSame(
			$expectedInversed->describe(),
			$variance->isValidVariance($templateType, $b, $a)->result->describe(),
			sprintf('%s->isValidVariance(%s, %s)', $variance->describe(), $b->describe(VerbosityLevel::precise()), $a->describe(VerbosityLevel::precise())),
		);
		$this->assertSame(
			$expectedStrict->describe(),
			$variance->isValidVariance($templateType, $a, $b, true)->result->describe(),
			sprintf('%s->isValidVariance(%s, %s, true)', $variance->describe(), $a->describe(VerbosityLevel::precise()), $b->describe(VerbosityLevel::precise())),
		);
		$this->assertSame(
			$expectedStrictInversed->describe(),
			$variance->isValidVariance($templateType, $b, $a, true)->result->describe(),
			sprintf('%s->isValidVariance(%s, %s, true)', $variance->describe(), $b->describe(VerbosityLevel::precise()), $a->describe(VerbosityLevel::precise())),
		);
	}

}
