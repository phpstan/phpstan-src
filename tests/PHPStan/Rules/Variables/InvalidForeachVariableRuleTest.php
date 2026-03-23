<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidForeachVariableRule>
 */
class InvalidForeachVariableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidForeachVariableRule();
	}

	public function testBug14349(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14349.php'], [
			[
				'Cannot re-assign $this.',
				7,
			],
			[
				'Cannot re-assign $this.',
				11,
			],
			[
				'Cannot re-assign $this.',
				15,
			],
		]);
	}

}
