<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PrintfParametersRule>
 */
class PrintfParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PrintfParametersRule(
			new PrintfHelper(new PhpVersion(PHP_VERSION_ID)),
			$this->createReflectionProvider(),
		);
	}

	public function testFile(): void
	{
		$this->analyse([__DIR__ . '/data/printf.php'], [
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				6,
			],
			[
				'Call to sprintf contains 0 placeholders, 1 value given.',
				7,
			],
			[
				'Call to sprintf contains 1 placeholder, 2 values given.',
				8,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				9,
			],
			[
				'Call to sprintf contains 2 placeholders, 0 values given.',
				10,
			],
			[
				'Call to sprintf contains 4 placeholders, 0 values given.',
				11,
			],
			[
				'Call to sprintf contains 5 placeholders, 2 values given.',
				13,
			],
			[
				'Call to sprintf contains 1 placeholder, 2 values given.',
				15,
			],
			[
				'Call to sprintf contains 6 placeholders, 0 values given.',
				16,
			],
			[
				'Call to sprintf contains 2 placeholders, 0 values given.',
				17,
			],
			[
				'Call to sprintf contains 1 placeholder, 0 values given.',
				18,
			],
			[
				'Call to sscanf contains 2 placeholders, 1 value given.',
				21,
			],
			[
				'Call to fscanf contains 2 placeholders, 1 value given.',
				25,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				27,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				29,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				45,
			],
			[
				'Call to sprintf contains 2 placeholders, 1 value given.',
				52,
			],
			[
				'Call to sprintf contains 2 placeholders, 3 values given.',
				54,
			],
		]);
	}

	public function testBug4717(): void
	{
		$errors = [
			[
				'Call to sprintf contains 1 placeholder, 2 values given.',
				5,
			],
		];
		if (PHP_VERSION_ID >= 80000) {
			$errors = [];
		}
		$this->analyse([__DIR__ . '/data/bug-4717.php'], $errors);
	}

	public function testBug2342(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2342.php'], [
			[
				'Call to sprintf contains 1 placeholder, 0 values given.',
				5,
			],
		]);
	}

}
