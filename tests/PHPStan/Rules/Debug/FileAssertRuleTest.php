<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FileAssertRule>
 */
class FileAssertRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FileAssertRule(
			self::createReflectionProvider(),
			self::getContainer()->getByType(TypeStringResolver::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/file-asserts.php'], [
			[
				'Expected type array<string>, actual: array<int>',
				20,
			],
			[
				'Expected subtype of array<string>, actual: array<int>',
				23,
			],
			[
				'Expected native type false, actual: bool',
				41,
			],
			[
				'Expected native type true, actual: bool',
				42,
			],
			[
				'Expected subtype of string, actual: false',
				47,
			],
			[
				'Expected subtype of never, actual: false',
				48,
			],
			[
				'Expected variable $b certainty Yes, actual: No',
				56,
			],
			[
				'Expected variable $b certainty Maybe, actual: No',
				57,
			],
			[
				"Expected offset 'firstName' certainty No, actual: Yes",
				76,
			],
		]);
	}

}
