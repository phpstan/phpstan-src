<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ConstantsInTraitsRule>
 */
class ConstantsInTraitsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ConstantsInTraitsRule();
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80200) {
			$errors = [
				[
					'Constant is declared inside a trait but is only supported on PHP 8.2 and later.',
					7,
				],
				[
					'Constant is declared inside a trait but is only supported on PHP 8.2 and later.',
					8,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/constants-in-traits.php'], $errors);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$errors = [
			[
				'Constant is declared inside a trait but is only supported on PHP 8.2 and later.',
				12,
			],
		];
		if (PHP_VERSION_ID < 80200) {
			$errors[] = [
				'Constant is declared inside a trait but is only supported on PHP 8.2 and later.',
				17,
			];
		}

		$this->analyse([__DIR__ . '/data/constants-in-traits-php-versions.php'], $errors);
	}

}
