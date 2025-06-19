<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedFunctionCallableUsageRule>
 */
class RestrictedFunctionCallableUsageRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RestrictedFunctionCallableUsageRule(
			self::getContainer(),
			self::createReflectionProvider(),
		);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-function-callable.php'], [
			[
				'Cannot call doFoo',
				7,
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
