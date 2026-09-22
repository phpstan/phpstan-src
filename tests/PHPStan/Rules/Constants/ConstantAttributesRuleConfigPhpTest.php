<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
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
class ConstantAttributesRuleConfigPhpTest extends RuleTestCase
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

	#[RequiresPhp('< 8.5.0')]
	public function testRulePhpVersionFromConfig(): void
	{
		$this->analyse([__DIR__ . '/data/constant-attributes.php'], [
			[
				'ConstantAttributesRule requires PHP 8.5 runtime to check the code.',
				25,
			],
			[
				'ConstantAttributesRule requires PHP 8.5 runtime to check the code.',
				28,
			],
			[
				'ConstantAttributesRule requires PHP 8.5 runtime to check the code.',
				31,
			],
			[
				'ConstantAttributesRule requires PHP 8.5 runtime to check the code.',
				34,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/constant-attributes-php-version.neon',
		];
	}

}
