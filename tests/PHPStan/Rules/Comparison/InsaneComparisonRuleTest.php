<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InsaneComparisonRule>
 */
class InsaneComparisonRuleTest extends RuleTestCase
{

	/** @var bool */
	private $treatMixedAsPossibleString;

	protected function getRule(): Rule
	{
		return new InsaneComparisonRule($this->treatMixedAsPossibleString);
	}

	public function testRule(): void
	{
		$this->treatMixedAsPossibleString = false;
		$this->analyse([__DIR__ . '/data/insane-comparison.php'], [
			[
				'Possible Insane Comparison between string and 0',
				4,
			],
			[
				'Possible Insane Comparison between 0 and \'\'',
				6,
			],
			[
				'Possible Insane Comparison between 0 and \'0foo\'',
				8,
			],
		]);
	}

	public function testRuleMaybe(): void
	{
		$this->treatMixedAsPossibleString = true;
		$this->analyse([__DIR__ . '/data/insane-comparison-maybe.php'], [
			[
				'Possible Insane Comparison between mixed and 0',
				7,
			],
		]);
	}

}
