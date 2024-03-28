<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidComparisonOperationRule>
 */
class InvalidComparisonOperationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidComparisonOperationRule(
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-comparison.php'], [
			[
				'Comparison operation "==" between stdClass and int results in an error.',
				15,
			],
			[
				'Comparison operation "!=" between stdClass and int results in an error.',
				16,
			],
			[
				'Comparison operation "<" between stdClass and int results in an error.',
				17,
			],
			[
				'Comparison operation ">" between stdClass and int results in an error.',
				18,
			],
			[
				'Comparison operation "<=" between stdClass and int results in an error.',
				19,
			],
			[
				'Comparison operation ">=" between stdClass and int results in an error.',
				20,
			],
			[
				'Comparison operation "<=>" between stdClass and int results in an error.',
				21,
			],
			[
				'Comparison operation "==" between stdClass and float|null results in an error.',
				25,
			],
			[
				'Comparison operation "<" between stdClass and float|null results in an error.',
				26,
			],
			[
				'Comparison operation "==" between stdClass and float|int|null results in an error.',
				43,
			],
			[
				'Comparison operation "<" between stdClass and float|int|null results in an error.',
				44,
			],
			[
				'Comparison operation "==" between stdClass and 1 results in an error.',
				48,
			],
			[
				'Comparison operation "<" between stdClass and 1 results in an error.',
				49,
			],
			[
				'Comparison operation "==" between stdClass and int|stdClass results in an error.',
				56,
			],
			[
				'Comparison operation "<" between stdClass and int|stdClass results in an error.',
				57,
			],
			[
				'Comparison operation "==" between array and int results in an error.',
				61,
			],
			[
				'Comparison operation "!=" between array and int results in an error.',
				62,
			],
			[
				'Comparison operation "<" between array and int results in an error.',
				63,
			],
			[
				'Comparison operation ">" between array and int results in an error.',
				64,
			],
			[
				'Comparison operation "<=" between array and int results in an error.',
				65,
			],
			[
				'Comparison operation ">=" between array and int results in an error.',
				66,
			],
			[
				'Comparison operation "<=>" between array and int results in an error.',
				67,
			],
			[
				'Comparison operation "==" between array and float|null results in an error.',
				71,
			],
			[
				'Comparison operation "<" between array and float|null results in an error.',
				72,
			],
			[
				'Comparison operation "==" between array and float|int|null results in an error.',
				84,
			],
			[
				'Comparison operation "<" between array and float|int|null results in an error.',
				85,
			],
			[
				'Comparison operation "==" between array and 1 results in an error.',
				89,
			],
			[
				'Comparison operation "<" between array and 1 results in an error.',
				90,
			],
			[
				'Comparison operation "==" between array and array|int results in an error.',
				97,
			],
			[
				'Comparison operation "<" between array and array|int results in an error.',
				98,
			],
			[
				'Comparison operation ">" between array{1} and 2147483647|9223372036854775807 results in an error.',
				115,
			],
			[
				'Comparison operation "<" between numeric-string and DateTimeImmutable results in an error.',
				119,
			],
		]);
	}

	public function testRuleWithNullsafeVariant(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/invalid-comparison-nullsafe.php'], [
			[
				'Comparison operation "==" between stdClass|null and int results in an error.',
				12,
			],
		]);
	}


	public function testRuleWithSimpleXml(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9456-xml.php'], []);
	}

}
