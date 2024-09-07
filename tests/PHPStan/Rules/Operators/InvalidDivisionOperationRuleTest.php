<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidDivisionOperationRule>
 */
class InvalidDivisionOperationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidDivisionOperationRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-division.php'], [
			[
				'Binary operation "/" between int and int<0, max> might result in an error.',
				12,
			],
			[
				'Binary operation "/" between int and int<0, max> might result in an error.',
				13,
			],
			[
				'Binary operation "%" between int and int<0, max> might result in an error.',
				21,
			],
			[
				'Binary operation "%" between int and int<0, max> might result in an error.',
				22,
			],
			[
				'Binary operation "/" between mixed and int<0, max> might result in an error.',
				58,
			],
		]);
	}

}
