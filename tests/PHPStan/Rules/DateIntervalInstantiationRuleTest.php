<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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
		if (PHP_VERSION_ID < 80100) {
			$prefix = 'DateInterval::__construct(): ';
		} else {
			$prefix = '';
		}

		$this->analyse(
			[__DIR__ . '/data/date-interval-instantiation.php'],
			[
				[
					'Instantiating DateInterval with 1M produces an error: ' . $prefix . 'Unknown or bad format (1M)',
					5,
				],
				[
					'Instantiating DateInterval with asdfasdf produces an error: ' . $prefix . 'Unknown or bad format (asdfasdf)',
					18,
				],
				[
					'Instantiating DateInterval with  produces an error: ' . $prefix . 'Unknown or bad format ()',
					21,
				],
				[
					'Instantiating DateInterval with 1M produces an error: ' . $prefix . 'Unknown or bad format (1M)',
					30,
				],
				[
					'Instantiating DateInterval with invalid produces an error: ' . $prefix . 'Unknown or bad format (invalid)',
					37,
				],
			],
		);
	}

}
