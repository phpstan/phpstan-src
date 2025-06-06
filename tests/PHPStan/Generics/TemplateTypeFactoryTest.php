<?php declare(strict_types = 1);

namespace PHPStan\Generics;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class TemplateTypeFactoryTest extends PHPStanTestCase
{

	/** @return array<array{?Type, Type}> */
	public static function dataCreate(): array
	{
		return [
			[
				new ObjectType('DateTime'),
				new ObjectType('DateTime'),
			],
			[
				new MixedType(),
				new MixedType(),
			],
			[
				null,
				new MixedType(),
			],
			[
				new StringType(),
				new StringType(),
			],
			[
				new IntegerType(),
				new IntegerType(),
			],
			[
				new ErrorType(),
				new MixedType(),
			],
			[
				TemplateTypeFactory::create(
					TemplateTypeScope::createWithFunction('a'),
					'U',
					null,
					TemplateTypeVariance::createInvariant(),
				),
				new MixedType(),
			],
			[
				new UnionType([
					new StringType(),
					new IntegerType(),
				]),
				new UnionType([
					new StringType(),
					new IntegerType(),
				]),
			],
			[
				new IterableType(new IntegerType(), new StringType()),
				new IterableType(new IntegerType(), new StringType()),
			],
		];
	}

	/**
	 * @dataProvider dataCreate
	 */
	public function testCreate(?Type $bound, Type $expectedBound): void
	{
		$scope = TemplateTypeScope::createWithFunction('a');
		$templateType = TemplateTypeFactory::create(
			$scope,
			'T',
			$bound,
			TemplateTypeVariance::createInvariant(),
		);

		$this->assertTrue(
			$expectedBound->equals($templateType->getBound()),
			sprintf('%s -> equals(%s)', $expectedBound->describe(VerbosityLevel::precise()), $templateType->getBound()->describe(VerbosityLevel::precise())),
		);
	}

}
