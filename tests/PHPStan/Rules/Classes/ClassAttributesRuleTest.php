<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

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
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ClassAttributesRule>
 */
class ClassAttributesRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixed = false;

	private bool $checkImplicitMixed = false;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ClassAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
					new RuleLevelHelper(
						$reflectionProvider,
						checkNullables: true,
						checkThisOnly: false,
						checkUnionTypes: true,
						checkExplicitMixed: $this->checkExplicitMixed,
						checkImplicitMixed: $this->checkImplicitMixed,
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

	#[RequiresPhp('>= 8.0.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/class-attributes.php'], [
			[
				'Attribute class ClassAttributes\Nonexistent does not exist.',
				22,
			],
			[
				'Class ClassAttributes\Foo is not an Attribute class.',
				28,
			],
			[
				'Class ClassAttributes\Bar referenced with incorrect case: ClassAttributes\baR.',
				34,
			],
			[
				'Attribute class ClassAttributes\Baz does not have the class target.',
				46,
			],
			[
				'Attribute class ClassAttributes\Bar is not repeatable but is already present above the class.',
				59,
			],
			[
				'Attribute class self does not exist.',
				65,
			],
			[
				'Attribute class ClassAttributes\AbstractAttribute is abstract.',
				77,
			],
			[
				'Attribute class ClassAttributes\Bar does not have a constructor and must be instantiated without any parameters.',
				83,
			],
			[
				'Constructor of attribute class ClassAttributes\NonPublicConstructor is not public.',
				100,
			],
			[
				'Attribute class ClassAttributes\AttributeWithConstructor constructor invoked with 0 parameters, 2 required.',
				118,
			],
			[
				'Attribute class ClassAttributes\AttributeWithConstructor constructor invoked with 1 parameter, 2 required.',
				119,
			],
			[
				'Unknown parameter $r in call to ClassAttributes\AttributeWithConstructor constructor.',
				120,
			],
			[
				'Interface ClassAttributes\InterfaceAsAttribute is not an Attribute class.',
				132,
			],
			[
				'Trait ClassAttributes\TraitAsAttribute is not an Attribute class.',
				142,
			],
			[
				'Attribute class ClassAttributes\FlagsAttributeWithPropertyTarget does not have the class target.',
				164,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRuleForEnums(): void
	{
		$this->analyse([__DIR__ . '/data/enum-attributes.php'], [
			[
				'Attribute class EnumAttributes\AttributeWithPropertyTarget does not have the class target.',
				23,
			],
			[
				'Enum EnumAttributes\EnumAsAttribute is not an Attribute class.',
				35,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug7171(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7171.php'], [
			[
				'Parameter $repositoryClass of attribute class Bug7171\Entity constructor expects class-string<Bug7171\EntityRepository<T of object>>|null, \'stdClass\' given.',
				66,
				'Type #1 from the union: Classes Bug7171\EntityRepository<T of object> and stdClass are not in an inheritance relationship and because of single inheritance no object can be an instance of both.',
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testAllowDynamicPropertiesAttribute(): void
	{
		$this->analyse([__DIR__ . '/data/allow-dynamic-properties-attribute.php'], []);
	}

	#[RequiresPhp('>= 8.3.0')]
	public function testBug12011(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12011.php'], [
			[
				'Parameter #1 $name of attribute class Bug12011\Table constructor expects string|null, int given.',
				23,
			],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testBug12281(): void
	{
		$this->checkExplicitMixed = true;
		$this->checkImplicitMixed = true;
		$this->analyse([__DIR__ . '/data/bug-12281.php'], [
			[
				'Attribute class AllowDynamicProperties cannot be used with readonly class.',
				05,
			],
			[
				'Attribute class AllowDynamicProperties cannot be used with enum.',
				12,
			],
			[
				'Attribute class AllowDynamicProperties cannot be used with interface.',
				15,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testDeprecatedAttribute(): void
	{
		$this->analyse([__DIR__ . '/data/deprecated-attr-on-class.php'], [
			[
				'Attribute class Deprecated cannot be used with class DeprecatedAttrOnClass\Foo.',
				7,
			],
			[
				'Attribute class Deprecated cannot be used with interface DeprecatedAttrOnClass\Bar.',
				13,
			],
			[
				'Attribute class Deprecated cannot be used with enum DeprecatedAttrOnClass\Baz.',
				19,
			],
		]);
	}

}
