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

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-method.php'], [
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
				7,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug15681(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15681.php'], [
			[
				'Cannot call doFoo',
				70,
			],
			[
				'Cannot call doFoo',
				82,
			],
			[
				'Cannot call doFoo',
				87,
			],
			[
				'Cannot call doFoo',
				115,
			],
			[
				'Cannot call doFoo',
				120,
			],
			[
				'Cannot call doFoo',
				169,
			],
			[
				'Cannot call doFoo',
				174,
			],
			[
				'Cannot call doFoo',
				179,
			],
			[
				'Cannot call doFoo',
				190,
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
