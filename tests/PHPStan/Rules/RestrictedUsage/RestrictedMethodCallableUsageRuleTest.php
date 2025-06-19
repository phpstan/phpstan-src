<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedMethodCallableUsageRule>
 */
class RestrictedMethodCallableUsageRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new RestrictedMethodCallableUsageRule(
			self::getContainer(),
			self::createReflectionProvider(),
		);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-method-callable.php'], [
			[
				'Cannot call doFoo',
				13,
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
