<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\Rules\Api\ApiRuleHelper;
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
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ClassConstantAttributesRule>
 */
class ClassConstantAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ClassConstantAttributesRule(
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
					self::getContainer()->getByType(ParameterClosureTypeHelper::class),
					checkArgumentTypes: true,
					checkArgumentsPassedByReference: true,
					checkExtraArguments: true,
					checkMissingTypehints: true,
				),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, checkInternalClassCaseSensitivity: false, checkImportedClassNameCase: true),
					new ClassForbiddenNameCheck($container->getExtensionsCollection(ForbiddenClassNameExtension::class)),
					$reflectionProvider,
					$container->getExtensionsCollection(RestrictedClassNameUsageExtension::class),
				),
				new ApiRuleHelper(),
				deprecationRulesInstalled: true,
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/class-constant-attributes.php'], [
			[
				'Attribute class ClassConstantAttributes\Foo does not have the class constant target.',
				26,
			],
		]);
	}

	public function testOverrideAttributePhpVersionNarrowedScope(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80300) {
			$errors[] = [
				'Attribute class Override does not have the class constant target.',
				18,
			];
		}
		if (PHP_VERSION_ID < 80600) {
			$errors[] = [
				'Attribute class Override can be used with class constants only on PHP 8.6 and later.',
				27,
			];
		}

		$this->analyse([__DIR__ . '/data/class-constant-override-attr-php-versions.php'], $errors);
	}

}
