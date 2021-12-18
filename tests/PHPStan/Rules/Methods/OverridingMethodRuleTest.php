<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_filter;
use function array_merge;
use function array_values;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<OverridingMethodRule>
 */
class OverridingMethodRuleTest extends RuleTestCase
{

	private int $phpVersionId;

	protected function getRule(): Rule
	{
		return new OverridingMethodRule(
			new PhpVersion($this->phpVersionId),
			new MethodSignatureRule(true, true),
			false,
		);
	}

	public function dataOverridingFinalMethod(): array
	{
		return [
			[
				70300,
				'compatible',
				'compatible',
			],
			[
				70400,
				'contravariant',
				'covariant',
			],
		];
	}

	/**
	 * @dataProvider dataOverridingFinalMethod
	 */
	public function testOverridingFinalMethod(int $phpVersion, string $contravariantMessage): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$errors = [
			[
				'Method OverridingFinalMethod\Bar::doFoo() overrides final method OverridingFinalMethod\Foo::doFoo().',
				43,
			],
			[
				'Private method OverridingFinalMethod\Bar::doBar() overriding public method OverridingFinalMethod\Foo::doBar() should also be public.',
				48,
			],
			[
				'Protected method OverridingFinalMethod\Bar::doBaz() overriding public method OverridingFinalMethod\Foo::doBaz() should also be public.',
				53,
			],
			[
				'Private method OverridingFinalMethod\Bar::doLorem() overriding protected method OverridingFinalMethod\Foo::doLorem() should be protected or public.',
				58,
			],
			[
				'Non-static method OverridingFinalMethod\Bar::doIpsum() overrides static method OverridingFinalMethod\Foo::doIpsum().',
				63,
			],
			[
				'Static method OverridingFinalMethod\Bar::doDolor() overrides non-static method OverridingFinalMethod\Foo::doDolor().',
				68,
			],
			[
				'Parameter #1 $s (string) of method OverridingFinalMethod\Dolor::__construct() is not ' . $contravariantMessage . ' with parameter #1 $i (int) of method OverridingFinalMethod\Ipsum::__construct().',
				110,
			],
			[
				'Method OverridingFinalMethod\Dolor::doFoo() overrides method OverridingFinalMethod\Ipsum::doFoo() but misses parameter #1 $i.',
				115,
			],
			[
				'Parameter #1 $size (int) of method OverridingFinalMethod\FixedArray::setSize() is not ' . $contravariantMessage . ' with parameter #1 $size (mixed) of method SplFixedArray::setSize().',
				125,
			],
			[
				'Parameter #1 $j of method OverridingFinalMethod\Amet::doBar() is not variadic but parameter #1 $j of method OverridingFinalMethod\Sit::doBar() is variadic.',
				160,
			],
			[
				'Parameter #1 $j of method OverridingFinalMethod\Amet::doBaz() is variadic but parameter #1 $j of method OverridingFinalMethod\Sit::doBaz() is not variadic.',
				165,
			],
			[
				'Parameter #2 $j of method OverridingFinalMethod\Consecteur::doFoo() is required but parameter #2 $j of method OverridingFinalMethod\Sit::doFoo() is optional.',
				175,
			],
			[
				'Parameter #1 $i of method OverridingFinalMethod\Lacus::doFoo() is not passed by reference but parameter #1 $i of method OverridingFinalMethod\Etiam::doFoo() is passed by reference.',
				195,
			],
			[
				'Parameter #2 $j of method OverridingFinalMethod\Lacus::doFoo() is passed by reference but parameter #2 $j of method OverridingFinalMethod\Etiam::doFoo() is not passed by reference.',
				195,
			],
			[
				'Parameter #1 $i of method OverridingFinalMethod\BazBaz::doBar() is not optional.',
				205,
			],
			[
				'Parameter #2 $j of method OverridingFinalMethod\FooFoo::doFoo() is not optional.',
				225,
			],
			[
				'Method OverridingFinalMethod\SomeOtherException::__construct() overrides final method OverridingFinalMethod\OtherException::__construct().',
				280,
			],
			[
				'Parameter #1 $index (int) of method OverridingFinalMethod\FixedArrayOffsetExists::offsetExists() is not ' . $contravariantMessage . ' with parameter #1 $offset (mixed) of method ArrayAccess::offsetExists().',
				313,
			],
		];

		if (PHP_VERSION_ID >= 80000) {
			$errors = array_values(array_filter($errors, static fn (array $error): bool => $error[1] !== 125));
		}

		$this->phpVersionId = $phpVersion;
		$this->analyse([__DIR__ . '/data/overriding-method.php'], $errors);
	}

	public function dataParameterContravariance(): array
	{
		return [
			[
				__DIR__ . '/data/parameter-contravariance-array.php',
				70300,
				[
					[
						'Parameter #1 $a (iterable) of method ParameterContravarianceArray\Baz::doBar() is not compatible with parameter #1 $a (array|null) of method ParameterContravarianceArray\Foo::doBar().',
						43,
					],
				],
			],
			[
				__DIR__ . '/data/parameter-contravariance-array.php',
				70400,
				[
					[
						'Parameter #1 $a (iterable) of method ParameterContravarianceArray\Baz::doBar() is not contravariant with parameter #1 $a (array|null) of method ParameterContravarianceArray\Foo::doBar().',
						43,
					],
				],
			],
			[
				__DIR__ . '/data/parameter-contravariance-traversable.php',
				70300,
				[
					[
						'Parameter #1 $a (iterable) of method ParameterContravarianceTraversable\Baz::doBar() is not compatible with parameter #1 $a (Traversable|null) of method ParameterContravarianceTraversable\Foo::doBar().',
						43,
					],
				],
			],
			[
				__DIR__ . '/data/parameter-contravariance-traversable.php',
				70400,
				[
					[
						'Parameter #1 $a (iterable) of method ParameterContravarianceTraversable\Baz::doBar() is not contravariant with parameter #1 $a (Traversable|null) of method ParameterContravarianceTraversable\Foo::doBar().',
						43,
					],
				],
			],
			[
				__DIR__ . '/data/parameter-contravariance.php',
				70300,
				[
					[
						'Parameter #1 $e (Exception) of method ParameterContravariance\Bar::doBar() is not compatible with parameter #1 $e (InvalidArgumentException) of method ParameterContravariance\Foo::doBar().',
						28,
					],
					[
						'Parameter #1 $e (Exception|null) of method ParameterContravariance\Baz::doBar() is not compatible with parameter #1 $e (InvalidArgumentException) of method ParameterContravariance\Foo::doBar().',
						38,
					],
					[
						'Parameter #1 $e (InvalidArgumentException) of method ParameterContravariance\Lorem::doFoo() is not compatible with parameter #1 $e (Exception) of method ParameterContravariance\Foo::doFoo().',
						48,
					],
					[
						'Parameter #1 $e (InvalidArgumentException|null) of method ParameterContravariance\Ipsum::doFoo() is not compatible with parameter #1 $e (Exception) of method ParameterContravariance\Foo::doFoo().',
						58,
					],
				],
			],
			[
				__DIR__ . '/data/parameter-contravariance.php',
				70400,
				[
					[
						'Parameter #1 $e (InvalidArgumentException) of method ParameterContravariance\Lorem::doFoo() is not contravariant with parameter #1 $e (Exception) of method ParameterContravariance\Foo::doFoo().',
						48,
					],
					[
						'Parameter #1 $e (InvalidArgumentException|null) of method ParameterContravariance\Ipsum::doFoo() is not contravariant with parameter #1 $e (Exception) of method ParameterContravariance\Foo::doFoo().',
						58,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataParameterContravariance
	 * @param mixed[] $expectedErrors
	 */
	public function testParameterContravariance(
		string $file,
		int $phpVersion,
		array $expectedErrors,
	): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->phpVersionId = $phpVersion;
		$this->analyse([$file], $expectedErrors);
	}

	public function dataReturnTypeCovariance(): array
	{
		return [
			[
				70300,
				[
					[
						'Return type iterable of method ReturnTypeCovariance\Bar::doBar() is not compatible with return type array of method ReturnTypeCovariance\Foo::doBar().',
						38,
					],
					[
						'Return type InvalidArgumentException of method ReturnTypeCovariance\Bar::doBaz() is not compatible with return type Exception of method ReturnTypeCovariance\Foo::doBaz().',
						43,
					],
					[
						'Return type Exception of method ReturnTypeCovariance\Bar::doLorem() is not compatible with return type InvalidArgumentException of method ReturnTypeCovariance\Foo::doLorem().',
						48,
					],
					[
						'Return type mixed of method ReturnTypeCovariance\B::foo() is not compatible with return type stdClass|null of method ReturnTypeCovariance\A::foo().',
						66,
					],
				],
			],
			[
				70400,
				[
					[
						'Return type iterable of method ReturnTypeCovariance\Bar::doBar() is not covariant with return type array of method ReturnTypeCovariance\Foo::doBar().',
						38,
					],
					[
						'Return type Exception of method ReturnTypeCovariance\Bar::doLorem() is not covariant with return type InvalidArgumentException of method ReturnTypeCovariance\Foo::doLorem().',
						48,
					],
					[
						'Return type mixed of method ReturnTypeCovariance\B::foo() is not covariant with return type stdClass|null of method ReturnTypeCovariance\A::foo().',
						66,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataReturnTypeCovariance
	 * @param mixed[] $expectedErrors
	 */
	public function testReturnTypeCovariance(
		int $phpVersion,
		array $expectedErrors,
	): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->phpVersionId = $phpVersion;
		$this->analyse([__DIR__ . '/data/return-type-covariance.php'], $expectedErrors);
	}

	/**
	 * @dataProvider dataOverridingFinalMethod
	 */
	public function testParle(int $phpVersion, string $contravariantMessage, string $covariantMessage): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->phpVersionId = $phpVersion;
		$this->analyse([__DIR__ . '/data/parle.php'], [
			[
				'Parameter #1 $state (int) of method OverridingParle\Foo::pushState() is not ' . $contravariantMessage . ' with parameter #1 $state (string) of method Parle\RLexer::pushState().',
				8,
			],
			[
				'Return type string of method OverridingParle\Foo::pushState() is not ' . $covariantMessage . ' with return type int of method Parle\RLexer::pushState().',
				8,
			],
		]);
	}

	public function testVariadicParameterIsAlwaysOptional(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/variadic-always-optional.php'], []);
	}

	/**
	 * @dataProvider dataOverridingFinalMethod
	 */
	public function testBug3403(int $phpVersion): void
	{
		$this->phpVersionId = $phpVersion;
		$this->analyse([__DIR__ . '/data/bug-3403.php'], []);
	}

	public function testBug3443(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-3443.php'], []);
	}

	public function testBug3478(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-3478.php'], []);
	}

	public function testBug3629(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test require static reflection.');
		}
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-3629.php'], []);
	}

	public function testVariadics(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->phpVersionId = PHP_VERSION_ID;
		$errors = [];
		if (PHP_VERSION_ID < 70200) {
			$errors[] = [
				'Parameter #2 $lang (mixed) of method OverridingVariadics\Translator::translate() does not match parameter #2 $parameters (string) of method OverridingVariadics\ITranslator::translate().',
				24,
			];
		}

		$errors = array_merge($errors, [
			[
				'Parameter #2 $lang of method OverridingVariadics\OtherTranslator::translate() is not optional.',
				34,
			],
			[
				'Parameter #2 $lang of method OverridingVariadics\AnotherTranslator::translate() is not optional.',
				44,
			],
			[
				'Parameter #3 $parameters of method OverridingVariadics\AnotherTranslator::translate() is not variadic.',
				44,
			],
			[
				'Parameter #2 $lang of method OverridingVariadics\YetAnotherTranslator::translate() is not variadic.',
				54,
			],
		]);

		if (PHP_VERSION_ID < 70200) {
			$errors[] = [
				'Parameter #2 $lang (mixed) of method OverridingVariadics\YetAnotherTranslator::translate() does not match parameter #2 $parameters (string) of method OverridingVariadics\ITranslator::translate().',
				54,
			];
		}

		$this->analyse([__DIR__ . '/data/overriding-variadics.php'], $errors);
	}

	public function dataLessOverridenParametersWithVariadic(): array
	{
		return [
			[
				70400,
				[
					[
						'Parameter #1 $everything of method LessParametersVariadics\Bar::doFoo() is variadic but parameter #1 $many of method LessParametersVariadics\Foo::doFoo() is not variadic.',
						18,
					],
					[
						'Method LessParametersVariadics\Bar::doFoo() overrides method LessParametersVariadics\Foo::doFoo() but misses parameter #2 $parameters.',
						18,
					],
					[
						'Method LessParametersVariadics\Bar::doFoo() overrides method LessParametersVariadics\Foo::doFoo() but misses parameter #3 $here.',
						18,
					],
					[
						'Parameter #1 $everything of method LessParametersVariadics\Baz::doFoo() is variadic but parameter #1 $many of method LessParametersVariadics\Foo::doFoo() is not variadic.',
						28,
					],
					[
						'Method LessParametersVariadics\Baz::doFoo() overrides method LessParametersVariadics\Foo::doFoo() but misses parameter #2 $parameters.',
						28,
					],
					[
						'Method LessParametersVariadics\Baz::doFoo() overrides method LessParametersVariadics\Foo::doFoo() but misses parameter #3 $here.',
						28,
					],
					[
						'Parameter #2 $everything of method LessParametersVariadics\Lorem::doFoo() is variadic but parameter #2 $parameters of method LessParametersVariadics\Foo::doFoo() is not variadic.',
						38,
					],
					[
						'Method LessParametersVariadics\Lorem::doFoo() overrides method LessParametersVariadics\Foo::doFoo() but misses parameter #3 $here.',
						38,
					],
				],
			],
			[
				80000,
				[
					[
						'Parameter #1 ...$everything (int) of method LessParametersVariadics\Baz::doFoo() is not contravariant with parameter #2 $parameters (string) of method LessParametersVariadics\Foo::doFoo().',
						28,
					],
					[
						'Parameter #1 ...$everything (int) of method LessParametersVariadics\Baz::doFoo() is not contravariant with parameter #3 $here (string) of method LessParametersVariadics\Foo::doFoo().',
						28,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataLessOverridenParametersWithVariadic
	 * @param mixed[] $errors
	 */
	public function testLessOverridenParametersWithVariadic(int $phpVersionId, array $errors): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/less-parameters-variadics.php'], $errors);
	}

	public function dataParameterTypeWidening(): array
	{
		return [
			[
				70100,
				[
					[
						'Parameter #1 $foo (mixed) of method ParameterTypeWidening\Bar::doFoo() does not match parameter #1 $foo (string) of method ParameterTypeWidening\Foo::doFoo().',
						18,
					],
				],
			],
			[
				70200,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataParameterTypeWidening
	 * @param mixed[] $errors
	 */
	public function testParameterTypeWidening(int $phpVersionId, array $errors): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/parameter-type-widening.php'], $errors);
	}

	public function testBug4516(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-4516.php'], []);
	}

	public function dataTentativeReturnTypes(): array
	{
		return [
			[70400, []],
			[80000, []],
			[
				80100,
				[
					[
						'Return type mixed of method TentativeReturnTypes\Foo::getIterator() is not covariant with tentative return type Traversable of method IteratorAggregate::getIterator().',
						8,
						'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
					],
					[
						'Return type string of method TentativeReturnTypes\Lorem::getIterator() is not covariant with tentative return type Traversable of method IteratorAggregate::getIterator().',
						40,
						'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataTentativeReturnTypes
	 * @param mixed[] $errors
	 */
	public function testTentativeReturnTypes(int $phpVersionId, array $errors): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		if (PHP_VERSION_ID < 80100) {
			$errors = [];
		}

		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/tentative-return-types.php'], $errors);
	}

	public function testCountableBug(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/countable-bug.php'], []);
	}

}
