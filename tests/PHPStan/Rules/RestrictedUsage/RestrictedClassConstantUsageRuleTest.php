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
	public function testNullable(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-nullable.php'], [
			[
				'Cannot access FOO',
				53,
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
