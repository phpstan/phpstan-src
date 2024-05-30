<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function define;

/**
 * @extends RuleTestCase<DuplicateKeysInLiteralArraysRule>
 */
class DuplicateKeysInLiteralArraysRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DuplicateKeysInLiteralArraysRule(
			new ExprPrinter(new Printer()),
		);
	}

	public function testDuplicateKeys(): void
	{
		define('PHPSTAN_DUPLICATE_KEY', 0);
		$this->analyse([__DIR__ . '/data/duplicate-keys.php'], [
			[
				'Array has 2 duplicate keys with value \'\' (null, NULL).',
				15,
			],
			[
				'Array has 4 duplicate keys with value 1 (1, 1, 1.0, true).',
				17,
			],
			[
				'Array has 3 duplicate keys with value 0 (false, 0, PHPSTAN_DUPLICATE_KEY).',
				23,
			],
			[
				'Array has 2 duplicate keys with value \'=\' (self::EQ, self::IS).',
				32,
			],
			[
				'Array has 2 duplicate keys with value 2 ($idx, $idx).',
				55,
			],
			[
				'Array has 2 duplicate keys with value 0 (0, 0).',
				63,
			],
			[
				'Array has 2 duplicate keys with value 101 (101, 101).',
				67,
			],
			[
				'Array has 2 duplicate keys with value 102 (102, 102).',
				69,
			],
			[
				'Array has 2 duplicate keys with value -41 (-41, -41).',
				76,
			],
		]);
	}

}
