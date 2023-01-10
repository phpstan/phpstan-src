<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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

	public function testBug8485(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->checkAlwaysTrueStrictComparison = true;
		$this->analyse([__DIR__ . '/data/bug-8485.php'], [
			[
				'Loose comparison using == between Bug8485\E::c and Bug8485\E::c will always evaluate to true.',
				21,
			],
			[
				'Loose comparison using == between Bug8485\F::c and Bug8485\E::c will always evaluate to false.',
				26,
			],
			[
				'Loose comparison using == between Bug8485\F::c and Bug8485\E::c will always evaluate to false.',
				31,
			],
			[
				'Loose comparison using == between Bug8485\F and Bug8485\E will always evaluate to false.',
				38,
			],
			[
				'Loose comparison using == between Bug8485\F and Bug8485\E::c will always evaluate to false.',
				43,
			],
		]);
	}

}
