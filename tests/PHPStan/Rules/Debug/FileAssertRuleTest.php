<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FileAssertRule>
 */
class FileAssertRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FileAssertRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/file-asserts.php'], [
			[
				'Expected type array<string>, actual: array<int>',
				19,
			],
			[
				'Expected native type false, actual: bool',
				36,
			],
			[
				'Expected native type true, actual: bool',
				37,
			],
			[
				'Expected variable $b certainty Yes, actual: No',
				45,
			],
			[
				'Expected variable $b certainty Maybe, actual: No',
				46,
			],
			[
				"Expected offset 'firstName' certainty No, actual: Yes",
				65,
			],
		]);
	}

}
