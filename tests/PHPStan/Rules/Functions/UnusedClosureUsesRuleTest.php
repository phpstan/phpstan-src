<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnusedClosureUsesRule>
 */
class UnusedClosureUsesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedClosureUsesRule(true);
	}

	public function testCapturesAssignedThroughVariableVariables(): void
	{
		$this->analyse([__DIR__ . '/data/unused-closure-uses-variable-variables.php'], []);
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
			[
				'Anonymous function has an unused use $container.',
				43,
			],
		]);
	}

	public function testResolvedDynamicUsages(): void
	{
		$this->analyse([__DIR__ . '/data/unused-closure-uses-resolved-dynamic.php'], [
			[
				'Anonymous function has an unused use $other.',
				6,
			],
			[
				'Anonymous function has an unused use $overwritten.',
				22,
			],
		]);
	}

	public function testReferenceCapturedInSkippedCatch(): void
	{
		$this->analyse([__DIR__ . '/data/unused-closure-uses-skipped-catch.php'], [
			['Anonymous function has an unused use $unused.', 15],
		]);
	}

}
