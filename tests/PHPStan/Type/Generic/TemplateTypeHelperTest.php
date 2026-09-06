<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use DateTime;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\VerbosityLevel;
use stdClass;

class TemplateTypeHelperTest extends PHPStanTestCase
{

	public function testResolveDependentBoundsAndDefaults(): void
	{
		$scope = TemplateTypeScope::createWithClass('DependentBounds');
		$variance = TemplateTypeVariance::createInvariant();
		$bound = new ObjectWithoutClassType();
		$default = new ObjectType(stdClass::class);
		$t = TemplateTypeFactory::create($scope, 'T', $bound, $variance, default: $default);
		$u = TemplateTypeFactory::create($scope, 'U', $t, $variance);
		$v = TemplateTypeFactory::create($scope, 'V', $u, $variance);

		foreach ([$u, $v] as $type) {
			$this->assertTrue($bound->equals(TemplateTypeHelper::resolveToBounds($type)));
			$this->assertTrue($default->equals(TemplateTypeHelper::resolveToDefaults($type)));
		}

		$array = new ArrayType(new IntegerType(), $v);
		$this->assertTrue((new ArrayType(new IntegerType(), $bound))->equals(TemplateTypeHelper::resolveToBounds($array)));
		$this->assertTrue((new ArrayType(new IntegerType(), $default))->equals(TemplateTypeHelper::resolveToDefaults($array)));
	}

	public function testIssue2512(): void
	{
		$templateType = TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction('a'),
			'T',
			null,
			TemplateTypeVariance::createInvariant(),
		);

		$type = TemplateTypeHelper::resolveTemplateTypes(
			$templateType,
			new TemplateTypeMap([
				'T' => $templateType,
			]),
			TemplateTypeVarianceMap::createEmpty(),
			TemplateTypeVariance::createInvariant(),
		);

		$this->assertSame(
			'T (function a(), parameter)',
			$type->describe(VerbosityLevel::precise()),
		);

		$type = TemplateTypeHelper::resolveTemplateTypes(
			$templateType,
			new TemplateTypeMap([
				'T' => new IntersectionType([
					new ObjectType(DateTime::class),
					$templateType,
				]),
			]),
			TemplateTypeVarianceMap::createEmpty(),
			TemplateTypeVariance::createInvariant(),
		);

		$this->assertSame(
			'DateTime&T (function a(), parameter)',
			$type->describe(VerbosityLevel::precise()),
		);
	}

}
