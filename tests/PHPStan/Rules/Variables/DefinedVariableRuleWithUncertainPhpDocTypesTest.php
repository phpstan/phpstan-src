<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\NonStringableDynamicAccessCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<DefinedVariableRule>
 */
class DefinedVariableRuleWithUncertainPhpDocTypesTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DefinedVariableRule(
			new NonStringableDynamicAccessCheck(
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
				false,
			),
			cliArgumentsVariablesRegistered: true,
			checkMaybeUndefinedVariables: true,
		);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/../uncertain-phpdoc-types.neon',
			],
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return false;
	}

	public function testBug15728(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15728.php'], [
			[
				'Variable $type might not be defined.',
				42,
			],
		]);
	}

}
