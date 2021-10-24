<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;

/**
 * @extends \PHPStan\Testing\RuleTestCase<ExistingClassesInPropertiesRule>
 */
class ExistingClassesInPropertiesRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingClassesInPropertiesRule(
			$broker,
			new ClassCaseSensitivityCheck($broker, true),
			true,
			false
		);
	}

	public function testNonexistentClass(): void
	{
		$this->analyse(
			[
				__DIR__ . '/data/properties-types.php',
			],
			[
				[
					'Property PropertiesTypes\Foo::$bar has unknown class PropertiesTypes\Bar as its type.',
					12,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Property PropertiesTypes\Foo::$bars has unknown class PropertiesTypes\Bar as its type.',
					18,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Property PropertiesTypes\Foo::$dolors has unknown class PropertiesTypes\Dolor as its type.',
					21,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Property PropertiesTypes\Foo::$dolors has unknown class PropertiesTypes\Ipsum as its type.',
					21,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Property PropertiesTypes\Foo::$fooWithWrongCase has unknown class PropertiesTypes\BAR as its type.',
					24,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Property PropertiesTypes\Foo::$fooWithWrongCase has unknown class PropertiesTypes\Fooo as its type.',
					24,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Class PropertiesTypes\Foo referenced with incorrect case: PropertiesTypes\FOO.',
					24,
				],
				[
					'Property PropertiesTypes\Foo::$withTrait has invalid type PropertiesTypes\SomeTrait.',
					27,
				],
				[
					'Class DateTime referenced with incorrect case: Datetime.',
					30,
				],
				[
					'Property PropertiesTypes\Foo::$nonexistentClassInGenericObjectType has unknown class PropertiesTypes\Foooo as its type.',
					33,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Property PropertiesTypes\Foo::$nonexistentClassInGenericObjectType has unknown class PropertiesTypes\Barrrr as its type.',
					33,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
			]
		);
	}

	public function testNativeTypes(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/properties-native-types.php'], [
			[
				'Property PropertiesNativeTypes\Foo::$bar has unknown class PropertiesNativeTypes\Bar as its type.',
				10,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Property PropertiesNativeTypes\Foo::$baz has unknown class PropertiesNativeTypes\Baz as its type.',
				13,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Property PropertiesNativeTypes\Foo::$baz has unknown class PropertiesNativeTypes\Baz as its type.',
				13,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testPromotedProperties(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/properties-promoted-types.php'], [
			[
				'Property PromotedPropertiesExistingClasses\Foo::$baz has invalid type PromotedPropertiesExistingClasses\SomeTrait.',
				11,
			],
			[
				'Property PromotedPropertiesExistingClasses\Foo::$lorem has invalid type PromotedPropertiesExistingClasses\SomeTrait.',
				12,
			],
			[
				'Property PromotedPropertiesExistingClasses\Foo::$ipsum has unknown class PromotedPropertiesExistingClasses\Bar as its type.',
				13,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Property PromotedPropertiesExistingClasses\Foo::$dolor has unknown class PromotedPropertiesExistingClasses\Bar as its type.',
				14,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}
