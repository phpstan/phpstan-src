<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/**
 * @extends \PHPStan\Testing\RuleTestCase<DateTimeInstantiationRule>
 */
class DateTimeInstantiationRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new DateTimeInstantiationRule();
	}

	public function test(): void
	{
		$this->analyse(
			[__DIR__ . '/data/datetime-instantiation.php'],
			[
				[
					'Instantiating DateTime with 2020.11.17 produces an error: Double time specification',
					3,
				],
				/*[
					'Instantiating DateTimeImmutable with asdfasdf produces a warning: Double timezone specification',
					5,
				],*/
				[
					'Instantiating DateTimeImmutable with asdfasdf produces an error: The timezone could not be found in the database',
					5,
				],
				[
					'Instantiating DateTimeImmutable with 2020.11.17 produces an error: Double time specification',
					10,
				],
				[
					'Instantiating DateTimeImmutable with 2020.11.18 produces an error: Double time specification',
					17,
				],
				/*[
					'Instantiating DateTime with 2020-04-31 produces a warning: The parsed date was invalid',
					20,
				],*/
			]
		);
	}

}
