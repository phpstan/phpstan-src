<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedStaticMethodUsageRule>
 */
class RestrictedStaticMethodUsageRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new RestrictedStaticMethodUsageRule(
			self::getContainer(),
			$reflectionProvider,
			new RuleLevelHelper($reflectionProvider, true, false, true, true, true, false, true),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-method.php'], [
			[
				'Cannot call doFoo',
				36,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug12951(): void
	{
		require_once __DIR__ . '/../InternalTag/data/bug-12951-define.php';
		$this->analyse([__DIR__ . '/../InternalTag/data/bug-12951-static-method.php'], [
			[
				'Call to static method doBar() of internal class Bug12951Polyfill\NumberFormatter from outside its root namespace Bug12951Polyfill.',
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
