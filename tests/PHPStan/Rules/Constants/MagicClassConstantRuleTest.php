<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MagicClassConstantRule>
 */
class MagicClassConstantRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MagicClassConstantRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/magic-class-constant.php'], [
			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				5,
			],

			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				14,
			],
		]);
	}

}
