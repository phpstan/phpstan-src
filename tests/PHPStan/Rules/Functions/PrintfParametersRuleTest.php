<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

/**
 * @extends \PHPStan\Testing\RuleTestCase<PrintfParametersRule>
 */
class PrintfParametersRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new PrintfParametersRule(true);
	}

	public function testFile(): void
	{
		$this->analyse([__DIR__ . '/data/printf.php'], [
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				6,
			],
			[
				'Call to sprintf contains 0 placeholders, 1 value given.',
				7,
			],
			[
				'Call to sprintf contains 1 placeholder, 2 values given.',
				8,
			],
			[
				'Call to sprintf with invalid format string, `%2$s %1$s %% %1$s %%%`, contains invalid placeholder, `%`.',
				9,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				9,
			],
			[
				'Call to sprintf contains 2 placeholders, 0 values given.',
				10,
			],
			[
				'Call to sprintf contains 4 placeholders, 0 values given.',
				11,
			],
			[
				'Call to sprintf contains 5 placeholders, 2 values given.',
				13,
			],
			[
				'Call to sprintf contains 1 placeholder, 2 values given.',
				15,
			],
			[
				'Call to sprintf contains 6 placeholders, 0 values given.',
				16,
			],
			[
				'Call to sprintf contains 2 placeholders, 0 values given.',
				17,
			],
			[
				'Call to sprintf contains 1 placeholder, 0 values given.',
				18,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				27,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				29,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				45,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				52,
			],
			[
				'Call to sprintf contains 2 placeholders, 3 values given.',
				54,
			],
			[
				'Call to sprintf with invalid format string, `%`, contains invalid placeholder, `%`.',
				57,
			],
			[
				'Call to sprintf contains 1 placeholder, 0 values given.',
				57,
			],
			[
				'Call to sprintf contains 4 placeholders, 2 values given.',
				59,
			],
			[
				'Call to sprintf with invalid format string, `%2$s %1$s %% %s %s %s %s %%% %%%%`, contains invalid placeholder, `% %`.',
				60,
			],
			[
				'Call to sprintf with invalid format string, `%2$s %1$s %% %s %s %s %s %%% %%%%`, contains invalid placeholder, `%`.',
				60,
			],
			[
				'Call to sprintf contains 6 placeholders, 4 values given.',
				60,
			],
			[
				'Call to sprintf with invalid format string, `%s foo %%%`, contains invalid placeholder, `%`.',
				65,
			],
			[
				'Call to sprintf with invalid format string, `how %s many %?`, contains invalid placeholder, `%?`.',
				65,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				65,
			],
		]);
	}

}
