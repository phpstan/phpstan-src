<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedClassConstantUsageRule>
 */
class RestrictedClassConstantUsageRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new RestrictedClassConstantUsageRule(
			self::getContainer(),
			$reflectionProvider,
			new RuleLevelHelper($reflectionProvider, true, false, true, true, true, false, true),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-class-constant.php'], [
			[
				'Cannot access FOO',
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
