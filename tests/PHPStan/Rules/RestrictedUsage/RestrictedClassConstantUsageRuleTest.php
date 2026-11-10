<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedClassConstantUsageRule>
 */
class RestrictedClassConstantUsageRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new RestrictedClassConstantUsageRule(
			self::getContainer()->getExtensionsCollection(RestrictedClassConstantUsageExtension::class),
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
		$this->analyse([__DIR__ . '/data/restricted-class-constant.php'], [
			[
				'Cannot access FOO',
				17,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug15681(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15681.php'], [
			[
				'Cannot access FOO',
				73,
			],
			[
				'Cannot access FOO',
				85,
			],
			[
				'Cannot access FOO',
				90,
			],
			[
				'Cannot access FOO',
				118,
			],
			[
				'Cannot access FOO',
				123,
			],
			[
				'Cannot access FOO',
				172,
			],
			[
				'Cannot access FOO',
				177,
			],
			[
				'Cannot access FOO',
				182,
			],
			[
				'Cannot access FOO',
				193,
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
