<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<NumberComparisonOperatorsConstantConditionRule>
 */
class NumberComparisonOperatorsConstantConditionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NumberComparisonOperatorsConstantConditionRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/number-comparison-operators.php'], [
			[
				'Comparison operation "<=" between int<6, max> and 2 is always false.',
				7,
			],
			[
				'Comparison operation ">" between int<2, 4> and 8 is always false.',
				13,
			],
			[
				'Comparison operation "<" between int<min, 1> and 5 is always true.',
				21,
			],
		]);
	}

	public function testBug2648(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2648-rule.php'], []);
	}

	public function testBug2648Namespace(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2648-namespace-rule.php'], []);
	}

	public function testBug5161(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/bug-5161.php'], []);
	}

	public function testBug6013(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6013.php'], []);
	}

}
