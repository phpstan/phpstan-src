<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnsetCastRule>
 */
class UnsetCastRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnsetCastRule();
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID >= 80000) {
			$errors[] = [
				'The (unset) cast is no longer supported in PHP 8.0 and later.',
				6,
			];
		}

		$this->analyse([__DIR__ . '/data/unset-cast.php'], $errors);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$errors = [
			[
				'The (unset) cast is no longer supported in PHP 8.0 and later.',
				11,
			],
		];
		if (PHP_VERSION_ID >= 80000) {
			$errors[] = [
				'The (unset) cast is no longer supported in PHP 8.0 and later.',
				14,
			];
		}

		$this->analyse([__DIR__ . '/data/unset-cast-php-versions.php'], $errors);
	}

}
