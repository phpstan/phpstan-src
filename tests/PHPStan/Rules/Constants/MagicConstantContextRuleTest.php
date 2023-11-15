<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MagicConstantContextRule>
 */
class MagicConstantContextRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MagicConstantContextRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/magic-constant.php'], [
			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				5,
			],
			[
				'Magic constant __FUNCTION__ is always empty when used outside a class.',
				6,
			],
			[
				'Magic constant __METHOD__ is always empty when used outside a class.',
				7,
			],
			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				20,
			],
			[
				'Magic constant __METHOD__ is always empty when used outside a class.',
				22,
			],
		]);
	}

}
