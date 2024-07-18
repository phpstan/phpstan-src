<?php declare(strict_types = 1);

namespace PHPStan\Rules\Regexp;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RegularExpressionQuotingRule>
 */
class RegularExpressionQuotingRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RegularExpressionQuotingRule();
	}

	public function testValidRegexPatternBefore73(): void
	{
		$this->analyse(
			[__DIR__ . '/data/preg-quote.php'],
			[
				[
					'Call to preg_quote() is missing delimiter parameter to be effective.',
					6,
				],
				[
					'Call to preg_quote() uses invalid delimiter /.',
					7,
				],
				[
					'Call to preg_quote() uses invalid delimiter /.',
					11,
				],
				[
					'Call to preg_quote() is missing delimiter parameter to be effective.',
					12,
				],
				[
					'Call to preg_quote() uses invalid delimiter /.',
					18,
				],
				[
					'Call to preg_quote() uses invalid delimiter /.',
					20,
				],
				[
					'Call to preg_quote() uses invalid delimiter /.',
					21,
				],
				[
					'Call to preg_quote() uses invalid delimiter /.',
					22,
				],
				[
					'Call to preg_quote() uses invalid delimiter /.',
					23,
				],
				[
					'Call to preg_quote() uses invalid delimiter /.',
					24,
				],
			],
		);
	}

}
