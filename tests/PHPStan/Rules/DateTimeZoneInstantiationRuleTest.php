<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DateTimeZoneInstantiationRule>
 */
class DateTimeZoneInstantiationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DateTimeZoneInstantiationRule();
	}

	public function test(): void
	{
		$this->analyse(
			[__DIR__ . '/data/date-time-zone-instantiation.php'],
			[
				[
					'Instantiating DateTimeZone with invalid produces an error: DateTimeZone::__construct(): Unknown or bad timezone (invalid)',
					5,
				],
				[
					'Instantiating DateTimeZone with  produces an error: DateTimeZone::__construct(): Unknown or bad timezone ()',
					14,
				],
				[
					'Instantiating DateTimeZone with invalid produces an error: DateTimeZone::__construct(): Unknown or bad timezone (invalid)',
					23,
				],
				[
					'Instantiating DateTimeZone with Not/ATimezone produces an error: DateTimeZone::__construct(): Unknown or bad timezone (Not/ATimezone)',
					30,
				],
			],
		);
	}

}
