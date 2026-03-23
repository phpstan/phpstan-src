<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidVariableAssignRule>
 */
class InvalidVariableAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidVariableAssignRule();
	}

	public function testBug14349(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14349.php'], [
			[
				'Cannot re-assign $this.',
				11,
			],
			[
				'Cannot re-assign $this.',
				15,
			],
			[
				'Cannot re-assign $this.',
				19,
			],
			[
				'Cannot re-assign $this.',
				27,
			],
			[
				'Cannot re-assign $this.',
				28,
			],
			[
				'Cannot re-assign $this.',
				29,
			],
			[
				'Cannot re-assign $this.',
				30,
			],
			[
				'Cannot re-assign $this.',
				35,
			],
			[
				'Cannot re-assign $this.',
				42,
			],
		]);
	}

}
