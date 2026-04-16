<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<BacktickRule>
 */
class BacktickRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new BacktickRule(new PhpVersion(PHP_VERSION_ID));
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID >= 80500) {
			$errors = [
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
			];
		}
		$this->analyse([__DIR__ . '/data/backtick.php'], $errors);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/backtick.php', __DIR__ . '/data/backtick.php.fixed');
	}

}
