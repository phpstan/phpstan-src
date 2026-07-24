<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedStaticMethodCallableUsageRule>
 */
class RestrictedStaticMethodCallableUsageRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new RestrictedStaticMethodCallableUsageRule(
			self::getContainer()->getExtensionsCollection(RestrictedMethodUsageExtension::class),
			$reflectionProvider,
			new RuleLevelHelper(
				$reflectionProvider,
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: true,
				checkImplicitMixed: true,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-method-callable.php'], [
			[
				'Cannot call doFoo',
				36,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug12951(): void
	{
		require_once __DIR__ . '/../InternalTag/data/bug-12951-define.php';
		$this->analyse([__DIR__ . '/../InternalTag/data/bug-12951-static-method.php'], [
			[
				'Call to static method doBar() of internal class Bug12951Polyfill\NumberFormatter from outside its root namespace Bug12951Polyfill.',
				10,
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
