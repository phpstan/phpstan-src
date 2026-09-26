<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<DeprecatedCastRule>
 */
class DeprecatedCastRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DeprecatedCastRule();
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID >= 80500) {
			$errors = [
				[
					'Non-standard (integer) cast is deprecated in PHP 8.5. Use (int) instead.',
					7,
				],
				[
					'Non-standard (boolean) cast is deprecated in PHP 8.5. Use (bool) instead.',
					10,
				],
				[
					'Non-standard (double) cast is deprecated in PHP 8.5. Use (float) instead.',
					13,
				],
				[
					'Non-standard (binary) cast is deprecated in PHP 8.5. Use (string) instead.',
					16,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/deprecated-cast.php'], $errors);
	}

	public function testConditionallyExecutedCode(): void
	{
		$errors = [
			[
				'Non-standard (integer) cast is deprecated in PHP 8.5. Use (int) instead.',
				8,
			],
		];
		if (PHP_VERSION_ID >= 80500) {
			$errors[] = [
				'Non-standard (integer) cast is deprecated in PHP 8.5. Use (int) instead.',
				15,
			];
		}

		$this->analyse([__DIR__ . '/data/deprecated-cast-php-versions.php'], $errors);
	}

}
