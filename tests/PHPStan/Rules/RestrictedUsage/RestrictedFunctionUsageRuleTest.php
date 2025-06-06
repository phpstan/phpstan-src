<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedFunctionUsageRule>
 */
class RestrictedFunctionUsageRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RestrictedFunctionUsageRule(
			self::getContainer(),
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-function.php'], [
			[
				'Cannot call doFoo',
				17,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/restricted-usage.neon',
			...parent::getAdditionalConfigFiles(),
		];
	}

}
