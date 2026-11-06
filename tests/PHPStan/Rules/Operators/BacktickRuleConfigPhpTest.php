<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<BacktickRule>
 */
class BacktickRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(BacktickRule::class);
	}

	public function testRuleInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/backtick.php'], [
			[
				'Backtick operator is deprecated in PHP 8.5. Use shell_exec() function call instead.',
				4,
			],
			[
				'Backtick operator is deprecated in PHP 8.5. Use shell_exec() function call instead.',
				5,
			],
			[
				'Backtick operator is deprecated in PHP 8.5. Use shell_exec() function call instead.',
				6,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range.neon',
		];
	}

}
