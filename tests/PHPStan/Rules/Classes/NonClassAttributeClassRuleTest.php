<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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
			/* [ reported by a separate rule
				'Trait cannot be an Attribute class.',
				11,
			], */
			[
				'Abstract class NonClassAttributeClass\Lorem cannot be an Attribute class.',
				23,
			],
			[
				'Attribute class NonClassAttributeClass\Ipsum constructor must be public.',
				29,
			],
		]);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/enum-cannot-be-attribute.php'], [
			[
				'Enum cannot be an Attribute class.',
				5,
			],
		]);
	}

}
