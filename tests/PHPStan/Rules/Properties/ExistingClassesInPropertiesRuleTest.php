<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ExistingClassesInPropertiesRule>
 */
class ExistingClassesInPropertiesRuleTest extends RuleTestCase
{

	private int $phpVersion = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ExistingClassesInPropertiesRule(
			$reflectionProvider,
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			new UnresolvableTypeHelper(),
			new PhpVersion($this->phpVersion),
			true,
			false,
			true,
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
					'Property PropertiesTypes\Foo::$dolors has unknown class PropertiesTypes\Ipsum as its type.',
					21,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Property PropertiesTypes\Foo::$dolors has unknown class PropertiesTypes\Dolor as its type.',
					21,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Property PropertiesTypes\Foo::$fooWithWrongCase has unknown class PropertiesTypes\Fooo as its type.',
					24,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Property PropertiesTypes\Foo::$fooWithWrongCase has unknown class PropertiesTypes\BAR as its type.',
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
			],
		);
	}

	public function testNativeTypes(): void
	{
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

	public static function dataIntersectionTypes(): array
	{
		return [
			[80000, []],
			[
				80100,
				[
					[
						'Property PropertyIntersectionTypes\Test::$prop2 has unresolvable native type.',
						30,
					],
					[
						'Property PropertyIntersectionTypes\Test::$prop3 has unresolvable native type.',
						32,
					],
				],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataIntersectionTypes')]
	public function testIntersectionTypes(int $phpVersion, array $errors): void
	{
		$this->phpVersion = $phpVersion;

		$this->analyse([__DIR__ . '/data/intersection-types.php'], $errors);
	}

}
