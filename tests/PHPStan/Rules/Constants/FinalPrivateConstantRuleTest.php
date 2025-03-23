<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<FinalPrivateConstantRule> */
class FinalPrivateConstantRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FinalPrivateConstantRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/final-private-const.php'], [
			[
				'Private constant FinalPrivateConstants\User::FINAL_PRIVATE() cannot be final as it is never overridden by other classes.',
				8,
			],
		]);
	}

}
