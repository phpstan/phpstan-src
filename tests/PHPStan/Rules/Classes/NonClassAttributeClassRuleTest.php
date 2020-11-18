<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NonClassAttributeClassRule>
 */
class NonClassAttributeClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NonClassAttributeClassRule();
	}

	public function testRule(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/non-class-attribute-class.php'], [
			[
				'Interface cannot be an Attribute class.',
				5,
			],
			[
				'Trait cannot be an Attribute class.',
				11,
			],
		]);
	}

}
