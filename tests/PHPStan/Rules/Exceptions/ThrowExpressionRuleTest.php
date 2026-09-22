<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ThrowExpressionRule>
 */
class ThrowExpressionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ThrowExpressionRule();
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80000) {
			$errors = [
				[
					'Throw expression is supported only on PHP 8.0 and later.',
					10,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/throw-expr.php'], $errors);
	}

	public function testConditionallyExecutedCode(): void
	{
		$errors = [
			[
				'Throw expression is supported only on PHP 8.0 and later.',
				18,
			],
		];
		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				'Throw expression is supported only on PHP 8.0 and later.',
				24,
			];
		}

		$this->analyse([__DIR__ . '/data/throw-expr-php-versions.php'], $errors);
	}

}
