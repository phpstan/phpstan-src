<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ParamAttributesRule>
 */
class ParamAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ParamAttributesRule(
			new AttributesCheck(
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
					reportMixedTernaryAndCoalesce: true,
				),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, checkInternalClassCaseSensitivity: false),
					new ClassForbiddenNameCheck($container),
					$reflectionProvider,
					$container,
				),
				deprecationRulesInstalled: true,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/param-attributes.php'], [
			[
				'Attribute class ParamAttributes\Foo does not have the parameter target.',
				33,
			],
			[
				'Attribute class ParamAttributes\Foo does not have the parameter or property target.',
				72,
			],
			[
				'Attribute class ParamAttributes\Qux does not have the parameter target.',
				82,
			],
		]);
	}

	public function testSensitiveParameterAttribute(): void
	{
		$this->analyse([__DIR__ . '/data/sensitive-parameter.php'], []);
	}

	public function testBug10298(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10298.php'], []);
	}

}
