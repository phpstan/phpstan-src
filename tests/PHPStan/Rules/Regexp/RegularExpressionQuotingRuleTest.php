<?php declare(strict_types = 1);

namespace PHPStan\Rules\Regexp;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\Regex\RegexExpressionHelper;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<RegularExpressionQuotingRule>
 */
class RegularExpressionQuotingRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RegularExpressionQuotingRule(
			$this->createReflectionProvider(),
			self::getContainer()->getByType(RegexExpressionHelper::class),
		);
	}

	public function testRule(): void
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
					'Call to preg_quote() is missing delimiter parameter to be effective.',
					77,
				],
			],
		);
	}

	public function testRulePhp8(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse(
			[__DIR__ . '/data/preg-quote-php8.php'],
			[
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					6,
				],
				[
					'Call to preg_quote() uses invalid delimiter / while pattern uses &.',
					7,
				],
			],
		);
	}

}
