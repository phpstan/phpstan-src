<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedPropertyUsageRule>
 */
class RestrictedPropertyUsageRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new RestrictedPropertyUsageRule(
			self::getContainer(),
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-property.php'], [
			[
				'Cannot access $foo',
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
