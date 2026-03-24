<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ThisInGlobalStatementRule>
 */
class ThisInGlobalStatementRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ThisInGlobalStatementRule();
	}

	public function testBug14351(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14351.php'], [
			[
				'Cannot use $this as global variable.',
				15,
			],
		]);
	}

}
