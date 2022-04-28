<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TraitAttributeClassRule>
 */
class TraitAttributeClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TraitAttributeClassRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/non-class-attribute-class.php'], [
			[
				'Trait cannot be an Attribute class.',
				11,
			],
		]);
	}

}
