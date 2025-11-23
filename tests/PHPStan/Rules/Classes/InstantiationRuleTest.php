<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

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
 * @extends RuleTestCase<InstantiationRule>
 */
class InstantiationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new InstantiationRule(
			$container,
			$reflectionProvider,
			new FunctionCallParametersCheck(new RuleLevelHelper($reflectionProvider, true, false, true, false, false, false, true), new NullsafeCheck(), new UnresolvableTypeHelper(), new PropertyReflectionFinder(), true, true, true, true),
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			new ConsistentConstructorHelper(),
			true,
		);
	}

	public function testInstantiation(): void
	{
		$this->analyse(
			[__DIR__ . '/data/instantiation.php'],
			[
				[
					'Class TestInstantiation\InstantiatingClass constructor invoked with 0 parameters, 1 required.',
					15,
				],
				[
					'TestInstantiation\InstantiatingClass::doFoo() calls new parent but TestInstantiation\InstantiatingClass does not extend any class.',
					18,
				],
				[
					'Class TestInstantiation\FooInstantiation does not have a constructor and must be instantiated without any parameters.',
					26,
				],
				[
					'Instantiated class TestInstantiation\FooBarInstantiation not found.',
					27,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Class TestInstantiation\BarInstantiation constructor invoked with 0 parameters, 1 required.',
					28,
				],
				[
					'Instantiated class TestInstantiation\LoremInstantiation is abstract.',
					29,
				],
				[
					'Cannot instantiate interface TestInstantiation\IpsumInstantiation.',
					30,
				],
				[
					'Instantiated class Test not found.',
					33,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Class DatePeriod constructor invoked with 0 parameters, 1-4 required.',
					36,
				],
				[
					'Using self outside of class scope.',
					39,
				],
				[
					'Using static outside of class scope.',
					40,
				],
				[
					'Using parent outside of class scope.',
					41,
				],
				[
					'Class TestInstantiation\InstantiatingClass constructor invoked with 0 parameters, 1 required.',
					57,
				],
				[
					'Class TestInstantiation\FooInstantiation referenced with incorrect case: TestInstantiation\FOOInstantiation.',
					64,
				],
				[
					'Class TestInstantiation\FooInstantiation does not have a constructor and must be instantiated without any parameters.',
					64,
				],
				[
					'Class TestInstantiation\BarInstantiation referenced with incorrect case: TestInstantiation\BARInstantiation.',
					65,
				],
				[
					'Class TestInstantiation\BarInstantiation constructor invoked with 0 parameters, 1 required.',
					65,
				],
				[
					'Class TestInstantiation\BarInstantiation referenced with incorrect case: TestInstantiation\BARInstantiation.',
					66,
				],
				[
					'Class TestInstantiation\ClassExtendsProtectedConstructorClass constructor invoked with 0 parameters, 1 required.',
					94,
				],
				[
					'Cannot instantiate class TestInstantiation\ExtendsPrivateConstructorClass via private constructor TestInstantiation\PrivateConstructorClass::__construct().',
					104,
				],
				[
					'Class TestInstantiation\ExtendsPrivateConstructorClass constructor invoked with 0 parameters, 1 required.',
					104,
				],
				[
					'Cannot instantiate class TestInstantiation\PrivateConstructorClass via private constructor TestInstantiation\PrivateConstructorClass::__construct().',
					110,
				],
				[
					'Cannot instantiate class TestInstantiation\ProtectedConstructorClass via protected constructor TestInstantiation\ProtectedConstructorClass::__construct().',
					111,
				],
				[
					'Cannot instantiate class TestInstantiation\ClassExtendsProtectedConstructorClass via protected constructor TestInstantiation\ProtectedConstructorClass::__construct().',
					112,
				],
				[
					'Cannot instantiate class TestInstantiation\ExtendsPrivateConstructorClass via private constructor TestInstantiation\PrivateConstructorClass::__construct().',
					113,
				],
				[
					'Parameter #1 $message of class Exception constructor expects string, int given.',
					117,
				],
				[
					'Parameter #2 $code of class Exception constructor expects int, string given.',
					117,
				],
				[
					'Class TestInstantiation\NoConstructor referenced with incorrect case: TestInstantiation\NOCONSTRUCTOR.',
					127,
				],
				[
					'Class class@anonymous/tests/PHPStan/Rules/Classes/data/instantiation.php:137 constructor invoked with 3 parameters, 1 required.',
					137,
				],
				[
					'Instantiated class UndefinedClass1 not found.',
					169,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Instantiated class UndefinedClass2 not found.',
					172,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Instantiated class UndefinedClass3 not found.',
					179,
					'Learn more at https://phpstan.org/user-guide/discovering-symbols',
				],
				[
					'Class TestInstantiation\FinalClass does not have a constructor and must be instantiated without any parameters.',
					190,
				],
				[
					'Class TestInstantiation\ClassWithFinalConstructor constructor invoked with 0 parameters, 1 required.',
					206,
				],
				[
					'Class TestInstantiation\ConstructorComingFromAnInterface constructor invoked with 0 parameters, 1 required.',
					229,
				],
				[
					'Class TestInstantiation\AbstractClassWithFinalConstructor constructor invoked with 1 parameter, 0 required.',
					245,
				],
				[
					'Class TestInstantiation\AbstractConstructor constructor invoked with 0 parameters, 1 required.',
					257,
				],
				[
					'Class TestInstantiation\ClassExtendingAbstractConstructor constructor invoked with 0 parameters, 1 required.',
					273,
				],
			],
		);
	}

	public function testSoap(): void
	{
		$this->analyse(
			[__DIR__ . '/data/instantiation-soap.php'],
			[
				[
					'Parameter #2 $string of class SoapFault constructor expects string, int given.',
					6,
				],
			],
		);
	}

	public function testClassExists(): void
	{
		$this->analyse([__DIR__ . '/data/instantiation-class-exists.php'], []);
	}

	public function testBug3404(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3404.php'], [
			[
				'Class finfo constructor invoked with 3 parameters, 0-2 required.',
				7,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testOldStyleConstructorOnPhp8(): void
	{
		$this->analyse([__DIR__ . '/data/php80-constructor.php'], [
			[
				'Class OldStyleConstructorOnPhp8 does not have a constructor and must be instantiated without any parameters.',
				13,
			],
			[
				'Class OldStyleConstructorOnPhp8 does not have a constructor and must be instantiated without any parameters.',
				20,
			],
		]);
	}

	#[RequiresPhp('< 8.0')]
	public function testOldStyleConstructorOnPhp7(): void
	{
		$this->analyse([__DIR__ . '/data/php80-constructor.php'], [
			[
				'Class OldStyleConstructorOnPhp8 constructor invoked with 0 parameters, 1 required.',
				19,
			],
		]);
	}

	public function testBug4030(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4030.php'], []);
	}

	public function testPromotedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/instantiation-promoted-properties.php'], [
			[
				'Parameter #2 $bar of class InstantiationPromotedProperties\Foo constructor expects array<string>, array<int, int> given.',
				30,
			],
			[
				'Parameter #2 $bar of class InstantiationPromotedProperties\Bar constructor expects array<string>, array<int, int> given.',
				33,
			],
			[
				'Parameter #1 $intProp of class InstantiationPromotedProperties\PromotedPropertyNotNullable constructor expects int, null given.',
				46,
			],
		]);
	}

	public function testBug4056(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4056.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testNamedArguments(): void
	{
		$this->analyse([__DIR__ . '/data/instantiation-named-arguments.php'], [
			[
				'Missing parameter $j (int) in call to InstantiationNamedArguments\Foo constructor.',
				15,
			],
			[
				'Unknown parameter $z in call to InstantiationNamedArguments\Foo constructor.',
				16,
			],
		]);
	}

	public function testBug4471(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4471.php'], [
			[
				'Instantiated class Bug4471\Baz not found.',
				19,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Instantiated class Bug4471\Foo is abstract.',
				24,
			],
			[
				'Cannot instantiate interface Bug4471\Bar.',
				27,
			],
		]);
	}

	public function testBug1711(): void
	{
		$this->analyse([__DIR__ . '/data/bug-1711.php'], []);
	}

	public function testBug3425(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3425.php'], [
			[
				'Parameter #1 $iterator of class RecursiveIteratorIterator constructor expects T of IteratorAggregate|RecursiveIterator, Generator<int, int, mixed, void> given.',
				5,
			],
		]);
	}

	public function testBug5002(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5002.php'], []);
	}

	public function testBug4681(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4681.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testFirstClassCallable(): void
	{
		// handled by a different rule
		$this->analyse([__DIR__ . '/data/first-class-instantiation-callable.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testEnumInstantiation(): void
	{
		$this->analyse([__DIR__ . '/data/enum-instantiation.php'], [
			[
				'Cannot instantiate enum EnumInstantiation\Foo.',
				9,
			],
			[
				'Cannot instantiate enum EnumInstantiation\Foo.',
				14,
			],
			[
				'Cannot instantiate enum EnumInstantiation\Foo.',
				21,
			],
		]);
	}

	public function testBug6370(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6370.php'], [
			[
				'Parameter #1 $something of class Bug6370\A constructor expects string, int given.',
				45,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug5553(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5553.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug7048(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7048.php'], [
			[
				'Unknown parameter $recurrences in call to DatePeriod constructor.',
				21,
			],
			[
				'Missing parameter $end (int|TEnd of DateTimeInterface) in call to DatePeriod constructor.',
				18,
			],
			[
				'Unknown parameter $isostr in call to DatePeriod constructor.',
				25,
			],
			[
				'Missing parameter $start (string) in call to DatePeriod constructor.',
				24,
			],
			[
				'Parameter #3 $end of class DatePeriod constructor expects int|TEnd of DateTimeInterface, string given.',
				41,
			],
			[
				'Parameter $end of class DatePeriod constructor expects int|TEnd of DateTimeInterface, string given.',
				49,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug7594(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7594.php'], []);
	}

	public function testBug3311a(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3311a.php'], [
			[
				'Parameter #1 $bar of class Bug3311a\Foo constructor expects list<string>, array{1: \'baz\'} given.',
				24,
				"array{1: 'baz'} is not a list.",
			],
		]);
	}

	public function testBug9341(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-9341.php'], []);
	}

	public function testBug7574(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7574.php'], []);
	}

	public function testBug9946(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9946.php'], []);
	}

	public function testBug10324(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10324.php'], [
			[
				'Parameter #3 $flags of class RecursiveIteratorIterator constructor expects 0|16, 2 given.',
				23,
			],
		]);
	}

	public function testPhpstanInternalClass(): void
	{
		$tip = 'This is most likely unintentional. Did you mean to type \AClass?';

		$this->analyse([__DIR__ . '/data/phpstan-internal-class.php'], [
			[
				'Referencing prefixed PHPStan class: _PHPStan_156ee64ba\AClass.',
				30,
				$tip,
			],
		]);
	}

	public function testBug9659(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9659.php'], []);
	}

	public function testBug10248(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10248.php'], []);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug11815(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11815.php'], []);
	}

	public function testClassString(): void
	{
		$this->analyse([__DIR__ . '/data/class-string.php'], [
			[
				'Parameter #1 $i of class ClassString\A constructor expects int, string given.',
				65,
			],
			[
				'Parameter #1 $i of class ClassString\A constructor expects int, string given.',
				66,
			],
			[
				'Parameter #1 $i of class ClassString\A constructor expects int, string given.',
				67,
			],
			[
				'Parameter #1 $i of class ClassString\C constructor expects int, string given.',
				75,
			],
			[
				'Parameter #1 $i of class ClassString\C constructor expects int, string given.',
				76,
			],
			[
				'Parameter #1 $i of class ClassString\C constructor expects int, string given.',
				77,
			],
			[
				'Parameter #1 $i of class ClassString\A constructor expects int, string given.',
				85,
			],
			[
				'Parameter #1 $i of class ClassString\A constructor expects int, string given.',
				86,
			],
			[
				'Parameter #1 $i of class ClassString\A constructor expects int, string given.',
				87,
			],
		]);
	}

	public function testInternalConstructor(): void
	{
		$this->analyse([__DIR__ . '/data/internal-constructor.php'], [
			[
				'Call to internal method InternalConstructorDefinition\Foo::__construct() from outside its root namespace InternalConstructorDefinition.',
				21,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug12951(): void
	{
		require_once __DIR__ . '/../InternalTag/data/bug-12951-define.php';
		$this->analyse([__DIR__ . '/../InternalTag/data/bug-12951-constructor.php'], [
			[
				'Instantiation of internal class Bug12951Polyfill\NumberFormatter.',
				7,
			],
			[
				'Call to method __construct() of internal class Bug12951Polyfill\NumberFormatter from outside its root namespace Bug12951Polyfill.',
				7,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testNamedArgumentsPhpversion(): void
	{
		$this->analyse([__DIR__ . '/data/named-arguments-phpversion.php'], []);
	}

	public function testNewStaticWithConsistentConstructor(): void
	{
		$this->analyse([__DIR__ . '/data/instantiation-new-static-consistent-constructor.php'], [
			[
				'Parameter #1 $i of class InstantiationNewStaticConsistentConstructor\Foo constructor expects int, string given.',
				18,
			],
			[
				'Parameter #1 $value of class InstantiationNewStaticConsistentConstructor\ChildClass3 constructor expects string, int given.',
				38,
			],
		]);
	}

}
