<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ClosureReturnTypeRule>
 */
class ClosureReturnTypeParameterClosureExtensionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ClosureReturnTypeRule(new FunctionReturnTypeCheck(
			new RuleLevelHelper(
				self::createReflectionProvider(),
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: true,
				checkExplicitMixed: false,
				checkImplicitMixed: false,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
		));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/closure-return-type-parameter-closure-extension.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/closure-return-type-parameter-closure-extension.neon',
		];
	}

}
