<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToFunctionParametersRule>
 */
class CallToFunctionParametersRulePhp86Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = self::createReflectionProvider();
		return new CallToFunctionParametersRule(
			$broker,
			new FunctionCallParametersCheck(
				new RuleLevelHelper(
					$broker,
					checkNullables: true,
					checkThisOnly: false,
					checkUnionTypes: true,
					checkExplicitMixed: true,
					checkImplicitMixed: true,
					checkBenevolentUnionTypes: false,
					discoveringSymbolsTip: true,
				),
				new NullsafeCheck(),
				new UnresolvableTypeHelper(),
				new PropertyReflectionFinder(),
				$broker,
				checkArgumentTypes: true,
				checkArgumentsPassedByReference: true,
				checkExtraArguments: true,
				checkMissingTypehints: true,
			),
		);
	}

	public function testBug15372(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15372.php'], [
			[
				'Constant SORT_REGULAR is not allowed for parameter #3 $mode of function array_filter.',
				10,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/call-to-function-php86.neon',
		];
	}

}
