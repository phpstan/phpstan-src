<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ConstantLooseComparisonRule>
 */
class ConstantLooseComparisonRuleTest extends RuleTestCase
{

	private bool $checkAlwaysTrueStrictComparison;

	protected function getRule(): Rule
	{
		return new ConstantLooseComparisonRule($this->checkAlwaysTrueStrictComparison);
	}

	public function testRule(): void
	{
		$this->checkAlwaysTrueStrictComparison = false;
		$this->analyse([__DIR__ . '/data/loose-comparison.php'], [
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				20,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				27,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				33,
			],
		]);
	}

	public function testRuleAlwaysTrue(): void
	{
		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/loose-comparison.php'], [
			[
				"Loose comparison using == between 0 and '0' will always evaluate to true.",
				16,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				20,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				27,
			],
			[
				"Loose comparison using == between 0 and '1' will always evaluate to false.",
				33,
			],
			[
				"Loose comparison using == between 0 and '0' will always evaluate to true.",
				35,
			],
		]);
	}

}
