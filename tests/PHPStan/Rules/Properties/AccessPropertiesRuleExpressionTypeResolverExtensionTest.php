<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\NonStringableDynamicAccessCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<AccessPropertiesRule>
 */
class AccessPropertiesRuleExpressionTypeResolverExtensionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$ruleLevelHelper = new RuleLevelHelper(
			$reflectionProvider,
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: true,
			checkExplicitMixed: false,
			checkImplicitMixed: false,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		);
		return new AccessPropertiesRule(
			new AccessPropertiesCheck(
				$reflectionProvider,
				$ruleLevelHelper,
				new PhpVersion(PHP_VERSION_ID),
				new NonStringableDynamicAccessCheck($ruleLevelHelper, true),
				reportMagicProperties: true,
				checkDynamicProperties: false,
			),
		);
	}

	public function testBug15421(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-15421.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../Analyser/expression-type-resolver-extension.neon',
		];
	}

}
