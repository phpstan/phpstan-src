<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<FinalConstantRule>
 */
class FinalConstantRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FinalConstantRule();
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80100) {
			$errors = [
				[
					'Final class constants are supported only on PHP 8.1 and later.',
					9,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/final-constant.php'], $errors);
	}

	public function testConditionallyDeclaredClass(): void
	{
		$errors = [
			[
				'Final class constants are supported only on PHP 8.1 and later.',
				18,
			],
		];
		if (PHP_VERSION_ID < 80100) {
			$errors[] = [
				'Final class constants are supported only on PHP 8.1 and later.',
				26,
			];
		}

		$this->analyse([__DIR__ . '/data/final-constant-php-versions.php'], $errors);
	}

}
