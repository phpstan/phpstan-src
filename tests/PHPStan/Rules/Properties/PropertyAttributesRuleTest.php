<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\DependencyInjection\LazyExtensionsCollection;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PropertyAttributesRule>
 */
class PropertyAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new PropertyAttributesRule(
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
				),
				new ClassNameCheck(
					new ClassCaseSensitivityCheck($reflectionProvider, checkInternalClassCaseSensitivity: false),
					new ClassForbiddenNameCheck(new LazyExtensionsCollection($container, ForbiddenClassNameExtension::class)),
					$reflectionProvider,
					new LazyExtensionsCollection($container, RestrictedClassNameUsageExtension::class),
				),
				deprecationRulesInstalled: true,
			),
			new PhpVersion(PHP_VERSION_ID),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/property-attributes.php'], [
			[
				'Attribute class PropertyAttributes\Foo does not have the property target.',
				26,
			],
		]);
	}

	public function testDeprecatedAttribute(): void
	{
		$this->analyse([__DIR__ . '/data/property-attributes-deprecated.php'], [
			[
				'Attribute class DeprecatedPropertyAttribute\DoSomethingTheOldWay is deprecated.',
				16,
			],
			[
				'Attribute class DeprecatedPropertyAttribute\DoSomethingTheOldWayWithDescription is deprecated: Use something else please',
				19,
			],
		]);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testOverrideAttributeAllowed(): void
	{
		$this->analyse([__DIR__ . '/data/override-attr-on-property.php'], []);
	}

	#[RequiresPhp('< 8.5.0')]
	public function testOverrideAttributeNotAllowed(): void
	{
		$this->analyse([__DIR__ . '/data/override-attr-on-property.php'], [
			[
				'Attribute class Override can be used with properties only on PHP 8.5 and later.',
				11,
			],
			[
				'Attribute class Override can be used with properties only on PHP 8.5 and later.',
				14,
			],
		]);
	}

}
