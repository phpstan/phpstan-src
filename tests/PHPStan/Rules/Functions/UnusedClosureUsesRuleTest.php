<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedFunctionParametersCheck;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnusedClosureUsesRule>
 */
class UnusedClosureUsesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedClosureUsesRule(new UnusedFunctionParametersCheck());
	}

	public function testUnusedClosureUses(): void
	{
		$this->analyse([__DIR__ . '/data/unused-closure-uses.php'], [
			[
				'Anonymous function has an unused use $unused.',
				3,
			],
			[
				'Anonymous function has an unused use $anotherUnused.',
				3,
			],
			[
				'Anonymous function has an unused use $usedInClosureUse.',
				10,
			],
		]);
	}

}
