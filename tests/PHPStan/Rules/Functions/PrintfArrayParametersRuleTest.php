<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PrintfArrayParametersRule>
 */
class PrintfArrayParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PrintfArrayParametersRule(new PrintfHelper(new PhpVersion(PHP_VERSION_ID)));
	}

	public function testFile(): void
	{
		$this->analyse([__DIR__ . '/data/vprintf.php'], [
			[
				'Call to vsprintf contains 2 placeholders, 1 value given.',
				6,
			],
			[
				'Call to vsprintf contains 0 placeholders, 1 value given.',
				7,
			],
			[
				'Call to vsprintf contains 1 placeholder, 2 values given.',
				8,
			],
			[
				'Call to vsprintf contains 2 placeholders, 1 value given.',
				9,
			],
			[
				'Call to vsprintf contains 2 placeholders, 0 values given.',
				10,
			],
			[
				'Call to vsprintf contains 2 placeholders, 0 values given.',
				11,
			],
			[
				'Call to vsprintf contains 4 placeholders, 0 values given.',
				12,
			],
			[
				'Call to vsprintf contains 5 placeholders, 2 values given.',
				14,
			],
			[
				'Call to vsprintf contains 1 placeholder, 2 values given.',
				17,
			],
			[
				'Call to vprintf contains 2 placeholders, 1 value given.',
				30,
			],
		]);
	}

}
