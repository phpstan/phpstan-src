<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeVariance;

class VerbosityLevelTest extends PHPStanTestCase
{

	public static function dataGetRecommendedLevelByType(): iterable
	{
		yield [
			new BooleanType(),
			new IntersectionType([new StringType(), new AccessoryLowercaseStringType()]),
			VerbosityLevel::typeOnly(),
		];
		yield [
			new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()]),
			new IntersectionType([new StringType(), new AccessoryLowercaseStringType()]),
			VerbosityLevel::value(),
		];
		yield [
			new GenericObjectType(
				'ArrayAccess',
				[
					new IntegerType(),
					new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()]),
				],
				variances: [TemplateTypeVariance::createInvariant(), TemplateTypeVariance::createInvariant()],
			),
			new GenericObjectType(
				'ArrayAccess',
				[
					new IntegerType(),
					new IntersectionType([new StringType(), new AccessoryLowercaseStringType()]),
				],
				variances: [TemplateTypeVariance::createInvariant(), TemplateTypeVariance::createInvariant()],
			),
			VerbosityLevel::precise(),
		];
	}

	/**
	 * @dataProvider dataGetRecommendedLevelByType
	 */
	public function testGetRecommendedLevelByType(Type $acceptingType, ?Type $acceptedType, VerbosityLevel $expected): void
	{
		$level = VerbosityLevel::getRecommendedLevelByType($acceptingType, $acceptedType);

		$this->assertSame($expected->getLevelValue(), $level->getLevelValue());
	}

}
