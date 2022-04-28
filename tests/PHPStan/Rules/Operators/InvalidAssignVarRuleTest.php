<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidAssignVarRule>
 */
class InvalidAssignVarRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidAssignVarRule(new NullsafeCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-assign-var.php'], [
			[
				'Nullsafe operator cannot be on left side of assignment.',
				12,
			],
			[
				'Nullsafe operator cannot be on left side of assignment.',
				13,
			],
			[
				'Nullsafe operator cannot be on left side of assignment.',
				14,
			],
			[
				'Nullsafe operator cannot be on left side of assignment.',
				16,
			],
			[
				'Nullsafe operator cannot be on left side of assignment.',
				17,
			],
			[
				'Expression on left side of assignment is not assignable.',
				31,
			],
			[
				'Expression on left side of assignment is not assignable.',
				33,
			],
			[
				'Nullsafe operator cannot be on right side of assignment by reference.',
				39,
			],
			[
				'Nullsafe operator cannot be on right side of assignment by reference.',
				40,
			],
		]);
	}

}
