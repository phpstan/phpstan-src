<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\TestCase;

class PhpVersionsTest extends TestCase
{

	/**
	 * @dataProvider dataProducesWarningForFinalPrivateMethods
	 */
	public function testProducesWarningForFinalPrivateMethods(TrinaryLogic $expected, Type $versionType): void
	{
		$phpVersions = new PhpVersions($versionType);
		$this->assertSame(
			$expected->describe(),
			$phpVersions->producesWarningForFinalPrivateMethods()->describe(),
		);
	}

	public static function dataProducesWarningForFinalPrivateMethods(): iterable
	{
		yield [
			TrinaryLogic::createNo(),
			new ConstantIntegerType(70400),
		];

		yield [
			TrinaryLogic::createYes(),
			new ConstantIntegerType(80000),
		];

		yield [
			TrinaryLogic::createYes(),
			new ConstantIntegerType(80100),
		];

		yield [
			TrinaryLogic::createYes(),
			IntegerRangeType::fromInterval(80000, null),
		];

		yield [
			TrinaryLogic::createMaybe(),
			IntegerRangeType::fromInterval(null, 80000),
		];

		yield [
			TrinaryLogic::createNo(),
			IntegerRangeType::fromInterval(70200, 70400),
		];

		yield [
			TrinaryLogic::createMaybe(),
			new UnionType([
				IntegerRangeType::fromInterval(70200, 70400),
				IntegerRangeType::fromInterval(80200, 80400),
			]),
		];
	}

}
