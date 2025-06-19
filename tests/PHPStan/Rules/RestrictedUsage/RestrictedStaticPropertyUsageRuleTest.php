<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedStaticPropertyUsageRule>
 */
class RestrictedStaticPropertyUsageRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new RestrictedStaticPropertyUsageRule(
			self::getContainer(),
			$reflectionProvider,
			new RuleLevelHelper($reflectionProvider, true, false, true, true, true, false, true),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-property.php'], [
			[
				'Cannot access $foo',
				34,
			],
		]);
	}

	public function testBug12951(): void
	{
		require_once __DIR__ . '/../InternalTag/data/bug-12951-define.php';
		$this->analyse([__DIR__ . '/../InternalTag/data/bug-12951-static-property.php'], [
			[
				'Access to static property $prop of internal class Bug12951Polyfill\NumberFormatter from outside its root namespace Bug12951Polyfill.',
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
