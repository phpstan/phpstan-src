<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use DateTime;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

class TemplateTypeHelperTest extends PHPStanTestCase
{

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

		$this->assertEquals(
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

		$this->assertEquals(
			'DateTime&T (function a(), parameter)',
			$type->describe(VerbosityLevel::precise()),
		);
	}

}
