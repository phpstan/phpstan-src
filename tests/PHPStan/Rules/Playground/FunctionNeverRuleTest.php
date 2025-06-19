<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<FunctionNeverRule>
 */
class FunctionNeverRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FunctionNeverRule(new NeverRuleHelper());
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-never.php'], [
			[
				'Function FunctionNever\doBar() always throws an exception, it should have return type "never".',
				18,
			],
			[
				'Function FunctionNever\callsNever() always terminates script execution, it should have return type "never".',
				23,
			],
			[
				'Function FunctionNever\doBaz() always terminates script execution, it should have return type "never".',
				28,
			],
		]);
	}

}
