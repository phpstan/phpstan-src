<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ClassConstantRule>
 */
class ClassConstantRuleTest extends RuleTestCase
{

	private int $phpVersion;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ClassConstantRule(
			$reflectionProvider,
			new RuleLevelHelper($reflectionProvider, true, false, true, true, true, false, true),
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			new PhpVersion($this->phpVersion),
			true,
		);
	}

	public function testClassConstant(): void
	{
		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse(
			[
				__DIR__ . '/data/class-constant.php',
				__DIR__ . '/data/class-constant-defined.php',
			],
			[
				[
					'Class ClassConstantNamespace\Bar not found.',
					6,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Using self outside of class scope.',
					7,
				],
				[
					'Access to undefined constant ClassConstantNamespace\Foo::DOLOR.',
					10,
				],
				[
					'Cannot access constant LOREM on mixed.',
					11,
				],
				[
					'Access to undefined constant ClassConstantNamespace\Foo::DOLOR.',
					16,
				],
				[
					'Using static outside of class scope.',
					18,
				],
				[
					'Using parent outside of class scope.',
					19,
				],
				[
					'Access to constant FOO on an unknown class ClassConstantNamespace\UnknownClass.',
					21,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Class ClassConstantNamespace\Foo referenced with incorrect case: ClassConstantNamespace\FOO.',
					26,
				],
				[
					'Class ClassConstantNamespace\Foo referenced with incorrect case: ClassConstantNamespace\FOO.',
					27,
				],
				[
					'Access to undefined constant ClassConstantNamespace\Foo::DOLOR.',
					27,
				],
				[
					'Class ClassConstantNamespace\Foo referenced with incorrect case: ClassConstantNamespace\FOO.',
					28,
				],
				[
					'Access to undefined constant ClassConstantNamespace\Foo|string::DOLOR.',
					33,
				],
			],
		);
	}

	public function testClassConstantVisibility(): void
	{
		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/class-constant-visibility.php'], [
			[
				'Access to private constant PRIVATE_BAR of class ClassConstantVisibility\Bar.',
				25,
			],
			[
				'Access to parent::BAZ but ClassConstantVisibility\Foo does not extend any class.',
				27,
			],
			[
				'Access to undefined constant ClassConstantVisibility\Bar::PRIVATE_FOO.',
				45,
			],
			[
				'Access to private constant PRIVATE_FOO of class ClassConstantVisibility\Foo.',
				46,
			],
			[
				'Access to private constant PRIVATE_FOO of class ClassConstantVisibility\Foo.',
				47,
			],
			[
				'Access to undefined constant ClassConstantVisibility\Bar::PRIVATE_FOO.',
				60,
			],
			[
				'Access to protected constant PROTECTED_FOO of class ClassConstantVisibility\Foo.',
				71,
			],
			[
				'Access to undefined constant ClassConstantVisibility\WithFooAndBarConstant&ClassConstantVisibility\WithFooConstant::BAZ.',
				106,
			],
			[
				'Access to undefined constant ClassConstantVisibility\WithFooAndBarConstant|ClassConstantVisibility\WithFooConstant::BAR.',
				110,
			],
			[
				'Access to constant FOO on an unknown class ClassConstantVisibility\UnknownClassFirst.',
				112,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Access to constant FOO on an unknown class ClassConstantVisibility\UnknownClassSecond.',
				112,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Cannot access constant FOO on int|string.',
				116,
			],
			[
				'Access to undefined constant static(ClassConstantVisibility\AccessWithStatic)::BAR.',
				129,
			],
			[
				'Class ClassConstantVisibility\Foo referenced with incorrect case: ClassConstantVisibility\FOO.',
				135,
			],
			[
				'Access to private constant PRIVATE_FOO of class ClassConstantVisibility\Foo.',
				135,
			],
		]);
	}

	public function testClassExists(): void
	{
		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/class-exists.php'], [
			[
				'Class UnknownClass\Bar not found.',
				24,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Class UnknownClass\Foo not found.',
				26,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Class UnknownClass\Foo not found.',
				29,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public static function dataClassConstantOnExpression(): array
	{
		return [
			[
				70400,
				[
					[
						'Accessing ::class constant on an expression is supported only on PHP 8.0 and later.',
						15,
					],
					[
						'Accessing ::class constant on an expression is supported only on PHP 8.0 and later.',
						16,
					],
					[
						'Accessing ::class constant on an expression is supported only on PHP 8.0 and later.',
						17,
					],
					[
						'Accessing ::class constant on an expression is supported only on PHP 8.0 and later.',
						18,
					],
					[
						'Accessing ::class constant on an expression is supported only on PHP 8.0 and later.',
						19,
					],
				],
			],
			[
				80000,
				[
					[
						'Accessing ::class constant on a dynamic string is not supported in PHP.',
						16,
					],
					[
						'Cannot access constant class on stdClass|null.',
						17,
					],
					[
						'Cannot access constant class on string|null.',
						18,
					],
				],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataClassConstantOnExpression')]
	public function testClassConstantOnExpression(int $phpVersion, array $errors): void
	{
		$this->phpVersion = $phpVersion;
		$this->analyse([__DIR__ . '/data/class-constant-on-expr.php'], $errors);
	}

	public function testAttributes(): void
	{
		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/class-constant-attribute.php'], [
			[
				'Access to undefined constant ClassConstantAttribute\Foo::BAR.',
				5,
			],
			[
				'Access to undefined constant ClassConstantAttribute\Foo::BAR.',
				9,
			],
			[
				'Access to undefined constant ClassConstantAttribute\Foo::BAR.',
				12,
			],
			[
				'Access to undefined constant ClassConstantAttribute\Foo::BAR.',
				15,
			],
			[
				'Access to undefined constant ClassConstantAttribute\Foo::BAR.',
				17,
			],
			[
				'Access to private constant FOO of class ClassConstantAttribute\Foo.',
				26,
			],
			[
				'Access to undefined constant ClassConstantAttribute\Foo::BAR.',
				26,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testRuleWithNullsafeVariant(): void
	{
		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/class-constant-nullsafe.php'], []);
	}

	public function testBug7675(): void
	{
		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-7675.php'], []);
	}

	public function testBug8034(): void
	{
		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-8034.php'], [
			[
				'Access to undefined constant static(Bug8034\HelloWorld)::FIELDS.',
				19,
			],
		]);
	}

	public function testClassConstFetchDefined(): void
	{
		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/class-const-fetch-defined.php'], [
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				12,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				14,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				16,
			],
			[
				'Access to undefined constant Foo::TEST.',
				17,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				18,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				22,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				24,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				26,
			],
			[
				'Access to undefined constant Foo::TEST.',
				27,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				28,
			],
			[
				'Access to undefined constant Foo::TEST.',
				33,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				36,
			],
			[
				'Access to undefined constant Foo::TEST.',
				37,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				38,
			],
			[
				'Access to undefined constant Foo::TEST.',
				43,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				46,
			],
			[
				'Access to undefined constant Foo::TEST.',
				47,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				48,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				52,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				54,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				56,
			],
			[
				'Access to undefined constant Foo::TEST.',
				57,
			],
			[
				'Access to undefined constant ClassConstFetchDefined\Foo::TEST.',
				58,
			],
		]);
	}

	public function testPhpstanInternalClass(): void
	{
		$tip = 'This is most likely unintentional. Did you mean to type \AClass?';

		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/phpstan-internal-class.php'], [
			[
				'Referencing prefixed PHPStan class: _PHPStan_156ee64ba\AClass.',
				28,
				$tip,
			],
		]);
	}

	#[RequiresPhp('>= 8.2')]
	public function testClassConstantAccessedOnTrait(): void
	{
		$this->phpVersion = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/class-constant-accessed-on-trait.php'], [
			[
				'Cannot access constant TEST on trait ClassConstantAccessedOnTrait\Foo.',
				16,
			],
		]);
	}

	#[RequiresPhp('>= 8.3')]
	public function testDynamicAccess(): void
	{
		$this->phpVersion = PHP_VERSION_ID;

		$this->analyse([__DIR__ . '/data/dynamic-constant-access.php'], [
			[
				'Access to undefined constant ClassConstantDynamicAccess\Foo::FOO.',
				17,
			],
			[
				'Class constant name for ClassConstantDynamicAccess\Foo must be a string, but object was given.',
				19,
			],
			[
				'Access to undefined constant ClassConstantDynamicAccess\Foo::FOO.',
				20,
			],
			[
				'Access to undefined constant ClassConstantDynamicAccess\Foo::BUZ.',
				20,
			],
			[
				'Access to undefined constant ClassConstantDynamicAccess\Foo::FOO.',
				37,
			],
			[
				'Access to undefined constant ClassConstantDynamicAccess\Foo::BUZ.',
				39,
			],
			[
				'Access to undefined constant ClassConstantDynamicAccess\Foo::QUX.',
				41,
			],
			[
				'Access to undefined constant ClassConstantDynamicAccess\Foo::QUX.',
				44,
			],
			[
				'Access to undefined constant ClassConstantDynamicAccess\Foo::BUZ.',
				44,
			],
			[
				'Access to undefined constant ClassConstantDynamicAccess\Foo::FOO.',
				44,
			],
		]);
	}

	#[RequiresPhp('>= 8.3')]
	public function testStringableDynamicAccess(): void
	{
		$this->phpVersion = PHP_VERSION_ID;

		$this->analyse([__DIR__ . '/data/dynamic-constant-stringable-access.php'], [
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Foo must be a string, but mixed was given.',
				14,
			],
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Foo must be a string, but string|null was given.',
				15,
			],
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Foo must be a string, but Stringable|null was given.',
				16,
			],
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Foo must be a string, but int was given.',
				17,
			],
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Foo must be a string, but int|null was given.',
				18,
			],
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Foo must be a string, but DateTime|string was given.',
				19,
			],
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Foo must be a string, but 1111 was given.',
				20,
			],
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Foo must be a string, but Stringable was given.',
				22,
			],
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Foo must be a string, but mixed was given.',
				32,
			],
			[
				'Class constant name for ClassConstantDynamicStringableAccess\Bar must be a string, but mixed was given.',
				33,
			],
			[
				'Class constant name for DateTime|DateTimeImmutable must be a string, but mixed was given.',
				38,
			],
			[
				'Class constant name for object must be a string, but mixed was given.',
				39,
			],
		]);
	}

}
