<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PartialFunctionApplicationRule>
 */
class PartialFunctionApplicationRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PartialFunctionApplicationRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/partial-function-application-php-versions.php'], [
			[
				'Partial function application is supported only on PHP 8.6 and later.',
				17,
			],
			[
				'Partial function application is supported only on PHP 8.6 and later.',
				20,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/partial-function-application-php-version-range.neon',
		];
	}

}
