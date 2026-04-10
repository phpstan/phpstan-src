<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<CallToFunctionParametersRule>
 */
class Bug14450Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new CallToFunctionParametersRule(
			$reflectionProvider,
			new FunctionCallParametersCheck(
				new RuleLevelHelper(
					$reflectionProvider,
					checkNullables: true,
					checkThisOnly: false,
					checkUnionTypes: true,
					checkExplicitMixed: false,
					checkImplicitMixed: false,
					checkBenevolentUnionTypes: false,
					discoveringSymbolsTip: true,
				),
				new NullsafeCheck(),
				new UnresolvableTypeHelper(),
				new PropertyReflectionFinder(),
				$reflectionProvider,
				checkArgumentTypes: true,
				checkArgumentsPassedByReference: true,
				checkExtraArguments: true,
				checkMissingTypehints: true,
			),
		);
	}

	public function testBug14450(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14450.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/bug-14450.neon'],
		);
	}

}
