<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NumberComparisonOperatorsConstantConditionRule>
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
		$this->analyse([__DIR__ . '/data/bug-5161.php'], []);
	}

	public function testBug3310(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3310.php'], []);
	}

	public function testBug3264(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3264.php'], []);
	}

	public function testBug5656(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5656.php'], []);
	}

	public function testBug3867(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3867.php'], []);
	}

	public function testIntegerRangeGeneralization(): void
	{
		$this->analyse([__DIR__ . '/data/integer-range-generalization.php'], []);
	}

	public function testBug3153(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3153.php'], []);
	}

	public function testBug5707(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5707.php'], []);
	}

	public function testBug5969(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5969.php'], []);
	}

	public function testBug5295(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5295.php'], []);
	}

	public function testBug7052(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}
		$this->analyse([__DIR__ . '/data/bug-7052.php'], [
			[
				'Comparison operation ">" between Bug7052\Foo::A and Bug7052\Foo::B is always false.',
				16,
			],
			[
				'Comparison operation "<" between Bug7052\Foo::A and Bug7052\Foo::B is always false.',
				17,
			],
			[
				'Comparison operation ">=" between Bug7052\Foo::A and Bug7052\Foo::B is always false.',
				18,
			],
			[
				'Comparison operation "<=" between Bug7052\Foo::A and Bug7052\Foo::B is always false.',
				19,
			],
		]);
	}

	public function testBug7044(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7044.php'], [
			[
				'Comparison operation "<" between 0 and 0 is always false.',
				15,
			],
		]);
	}

	public function testBug3277(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3277.php'], [
			[
				'Comparison operation "<" between 5 and 4 is always false.',
				6,
			],
		]);
	}

	public function testBug6013(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6013.php'], []);
	}

	public function testBug2851(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2851.php'], []);
	}

}
