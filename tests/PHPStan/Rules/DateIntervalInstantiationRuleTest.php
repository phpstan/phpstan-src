<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DateIntervalInstantiationRule>
 */
class DateIntervalInstantiationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DateIntervalInstantiationRule();
	}

	public function test(): void
	{
		$this->analyse(
			[__DIR__ . '/data/date-interval-instantiation.php'],
			[
				[
					'Instantiating DateInterval with 1M produces an error: Unknown or bad format (1M)',
					5,
				],
				[
					'Instantiating DateInterval with asdfasdf produces an error: Unknown or bad format (asdfasdf)',
					18,
				],
				[
					'Instantiating DateInterval with  produces an error: Unknown or bad format ()',
					21,
				],
				[
					'Instantiating DateInterval with 1M produces an error: Unknown or bad format (1M)',
					30,
				],
				[
					'Instantiating DateInterval with invalid produces an error: Unknown or bad format (invalid)',
					37,
				],
			],
		);
	}

}
