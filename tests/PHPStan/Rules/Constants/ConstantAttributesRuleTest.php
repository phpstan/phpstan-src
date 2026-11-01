<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\ParameterClosureTypeHelper;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ConstantAttributesRule>
 */
class ConstantAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ConstantAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
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
					new NullsafeCheck(),
					new UnresolvableTypeHelper(),
					new PropertyReflectionFinder(),
					$reflectionProvider,
					self::getContainer()->getByType(ParameterClosureTypeHelper::class),
					checkArgumentTypes: true,
					checkArgumentsPassedByReference: true,
					checkExtraArguments: true,
					checkMissingTypehints: true,
				),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck(
						$reflectionProvider,
						checkInternalClassCaseSensitivity: false,
						checkImportedClassNameCase: true,
					),
					new ClassForbiddenNameCheck($container->getExtensionsCollection(ForbiddenClassNameExtension::class)),
					$reflectionProvider,
					$container->getExtensionsCollection(RestrictedClassNameUsageExtension::class),
				),
				deprecationRulesInstalled: true,
			),
		);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/constant-attributes.php'], [
			[
				'Attribute class ConstantAttributes\IncompatibleAttr does not have the constant target.',
				31,
			],
			[
				'Attribute class ConstantAttributes\MyAttr does not have a constructor and must be instantiated without any parameters.',
				34,
			],
		]);
	}

	#[RequiresPhp('< 8.5.0')]
	public function testRuleBefore85Runtime(): void
	{
		$this->analyse([__DIR__ . '/data/constant-attributes.php'], [
			[
				'Attributes on global constants are supported only on PHP 8.5 and later.',
				25,
			],
			[
				'Attributes on global constants are supported only on PHP 8.5 and later.',
				28,
			],
			[
				'Attributes on global constants are supported only on PHP 8.5 and later.',
				31,
			],
			[
				'Attributes on global constants are supported only on PHP 8.5 and later.',
				34,
			],
		]);
	}

}
