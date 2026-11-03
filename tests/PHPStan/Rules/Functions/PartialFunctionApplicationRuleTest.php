<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PartialFunctionApplicationRule>
 */
class PartialFunctionApplicationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PartialFunctionApplicationRule();
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80600) {
			foreach ([11, 30, 31, 32, 33, 34] as $line) {
				$errors[] = [
					'Partial function application is supported only on PHP 8.6 and later.',
					$line,
				];
			}
		}

		$errors[] = [
			'Cannot combine nullsafe operator with callable creation.',
			36,
		];
		$errors[] = [
			'Cannot combine nullsafe operator with callable creation.',
			37,
		];
		$errors[] = [
			'Cannot create callable from the new operator.',
			38,
		];

		$this->analyse([__DIR__ . '/data/partial-function-application.php'], $errors);
	}

}
