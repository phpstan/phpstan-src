<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use Override;
use PHPStan\Classes\ForbiddenClassNameExtension;
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

/**
 * @extends RuleTestCase<PropertyAttributesRule>
 */
class PropertyAttributesRuleTest extends RuleTestCase
{

	private static ?int $analysedPhpVersionId = null;

	#[Override]
	protected function setUp(): void
	{
		self::$analysedPhpVersionId = null;
		parent::setUp();
	}

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
					new ClassCaseSensitivityCheck($reflectionProvider, checkInternalClassCaseSensitivity: false, checkImportedClassNameCase: true),
					new ClassForbiddenNameCheck($container->getExtensionsCollection(ForbiddenClassNameExtension::class)),
					$reflectionProvider,
					$container->getExtensionsCollection(RestrictedClassNameUsageExtension::class),
				),
				deprecationRulesInstalled: true,
			),
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

	public function testConditionallyDeclaredClass(): void
	{
		self::$analysedPhpVersionId = 80400;
		$this->analyse([__DIR__ . '/data/override-attr-on-property-php-versions.php'], [
			[
				'Attribute class Override can be used with properties only on PHP 8.5 and later.',
				22,
			],
			[
				'Attribute class Override can be used with properties only on PHP 8.5 and later.',
				31,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		if (self::$analysedPhpVersionId === null) {
			return [];
		}

		return [__DIR__ . '/../php-version-' . self::$analysedPhpVersionId . '.neon'];
	}

}
