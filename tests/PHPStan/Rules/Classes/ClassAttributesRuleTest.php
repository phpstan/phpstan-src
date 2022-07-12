<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ClassAttributesRule>
 */
class ClassAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = $this->createReflectionProvider();
		return new ClassAttributesRule(
			new AttributesCheck(
				$reflectionProvider,
				new FunctionCallParametersCheck(
					new RuleLevelHelper($reflectionProvider, true, false, true, false),
					new NullsafeCheck(),
					new PhpVersion(80000),
					new UnresolvableTypeHelper(),
					new PropertyReflectionFinder(),
					true,
					true,
					true,
					true,
					true,
				),
				new ClassCaseSensitivityCheck($reflectionProvider, false),
			),
		);
	}

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

	public function testRuleForEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

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

	public function testBug7171(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7171.php'], [
			[
				'Parameter $repositoryClass of attribute class Bug7171\Entity constructor expects class-string<Bug7171\EntityRepository<T of object>>|null, \'stdClass\' given.',
				66,
			],
		]);
	}

	public function testAllowDynamicPropertiesAttribute(): void
	{
		$this->analyse([__DIR__ . '/data/allow-dynamic-properties-attribute.php'], []);
	}

	public function testSensitiveParameterAttribute(): void
	{
		$this->analyse([__DIR__ . '/data/sensitive-parameter.php'], []);
	}

}
