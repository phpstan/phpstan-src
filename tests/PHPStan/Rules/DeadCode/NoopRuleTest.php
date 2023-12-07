<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoopRule>
 */
class NoopRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NoopRule(new ExprPrinter(new Printer()), true);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/noop.php'], [
			[
				'Expression "$arr" on a separate line does not do anything.',
				9,
			],
			[
				'Expression "$arr[\'test\']" on a separate line does not do anything.',
				10,
			],
			[
				'Expression "$foo::$test" on a separate line does not do anything.',
				11,
			],
			[
				'Expression "$foo->test" on a separate line does not do anything.',
				12,
			],
			[
				'Expression "\'foo\'" on a separate line does not do anything.',
				14,
			],
			[
				'Expression "1" on a separate line does not do anything.',
				15,
			],
			[
				'Expression "@\'foo\'" on a separate line does not do anything.',
				17,
			],
			[
				'Expression "+1" on a separate line does not do anything.',
				18,
			],
			[
				'Expression "-1" on a separate line does not do anything.',
				19,
			],
			[
				'Expression "isset($test)" on a separate line does not do anything.',
				25,
			],
			[
				'Expression "empty($test)" on a separate line does not do anything.',
				26,
			],
			[
				'Expression "true" on a separate line does not do anything.',
				27,
			],
			[
				'Expression "\DeadCodeNoop\Foo::TEST" on a separate line does not do anything.',
				28,
			],
			[
				'Expression "(string) 1" on a separate line does not do anything.',
				30,
			],
			[
				'Unused result of "xor" operator.',
				32,
				'This operator has unexpected precedence, try disambiguating the logic with parentheses ().',
			],
		]);
	}

	public function testNullsafe(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-property-fetch-noop.php'], [
			[
				'Expression "$ref?->name" on a separate line does not do anything.',
				10,
			],
		]);
	}

}
