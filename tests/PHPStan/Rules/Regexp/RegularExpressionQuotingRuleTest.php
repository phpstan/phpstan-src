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
		return new RegularExpressionQuotingRule($this->createReflectionProvider());
	}

	public function testValidRegexPatternBefore73(): void
	{
		$this->analyse(
			[__DIR__ . '/data/preg-quote.php'],
			[
				[
					'Call to preg_quote() is missing delimiter & to be effective.',
					6,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					7,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					11,
				],
				[
					'Call to preg_quote() is missing delimiter & to be effective.',
					12,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					18,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					20,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					21,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					22,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					23,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					24,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					26,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					27,
				],
			],
		);
	}

}
