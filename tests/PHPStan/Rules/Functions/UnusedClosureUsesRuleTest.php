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
		return new UnusedClosureUsesRule(new UnusedFunctionParametersCheck(self::createReflectionProvider(), true));
	}

	public function testUnusedClosureUses(): void
	{
		$this->analyse([__DIR__ . '/data/unused-closure-uses.php'], [
			[
				'Anonymous function has an unused use $unused.',
				6,
			],
			[
				'Anonymous function has an unused use $anotherUnused.',
				7,
			],
			[
				'Anonymous function has an unused use $usedInClosureUse.',
				10,
			],
		]);
	}

}
