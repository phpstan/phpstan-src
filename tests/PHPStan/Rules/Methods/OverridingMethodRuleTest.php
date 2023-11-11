<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_filter;
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
		$phpVersion = new PhpVersion($this->phpVersionId);

		return new OverridingMethodRule(
			$phpVersion,
			new MethodSignatureRule(true, true),
			false,
			new MethodParameterComparisonHelper($phpVersion, true),
			true,
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
				'Parameter #1 $size (int) of method OverridingFinalMethod\FixedArray::setSize() is not ' . $contravariantMessage . ' with parameter #1 $size (mixed) of method SplFixedArray<mixed>::setSize().',
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
				'Method OverridingFinalMethod\ExtendsFinalWithAnnotation::doFoo() overrides @final method OverridingFinalMethod\FinalWithAnnotation::doFoo().',
				303,
			],
			[
				'Parameter #1 $index (int) of method OverridingFinalMethod\FixedArrayOffsetExists::offsetExists() is not ' . $contravariantMessage . ' with parameter #1 $index (mixed) of method SplFixedArray<mixed>::offsetExists().',
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
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testParameterContravariance(
		string $file,
		int $phpVersion,
		array $expectedErrors,
	): void
	{
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
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testReturnTypeCovariance(
		int $phpVersion,
		array $expectedErrors,
	): void
	{
		$this->phpVersionId = $phpVersion;
		$this->analyse([__DIR__ . '/data/return-type-covariance.php'], $expectedErrors);
	}

	/**
	 * @dataProvider dataOverridingFinalMethod
	 */
	public function testParle(int $phpVersion, string $contravariantMessage, string $covariantMessage): void
	{
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
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-3629.php'], []);
	}

	public function testVariadics(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$errors = [
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
		];

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
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testLessOverridenParametersWithVariadic(int $phpVersionId, array $errors): void
	{
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
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testParameterTypeWidening(int $phpVersionId, array $errors): void
	{
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
						'Return type mixed of method TentativeReturnTypes\Foo::getIterator() is not covariant with tentative return type Traversable of method IteratorAggregate<mixed,mixed>::getIterator().',
						8,
						'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
					],
					[
						'Return type string of method TentativeReturnTypes\Lorem::getIterator() is not covariant with tentative return type Traversable of method IteratorAggregate<mixed,mixed>::getIterator().',
						40,
						'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
					],
					[
						'Return type mixed of method TentativeReturnTypes\UntypedIterator::current() is not covariant with tentative return type mixed of method Iterator<mixed,mixed>::current().',
						75,
						'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
					],
					[
						'Return type mixed of method TentativeReturnTypes\UntypedIterator::next() is not covariant with tentative return type void of method Iterator<mixed,mixed>::next().',
						79,
						'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
					],
					[
						'Return type mixed of method TentativeReturnTypes\UntypedIterator::key() is not covariant with tentative return type mixed of method Iterator<mixed,mixed>::key().',
						83,
						'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
					],
					[
						'Return type mixed of method TentativeReturnTypes\UntypedIterator::valid() is not covariant with tentative return type bool of method Iterator<mixed,mixed>::valid().',
						87,
						'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
					],
					[
						'Return type mixed of method TentativeReturnTypes\UntypedIterator::rewind() is not covariant with tentative return type void of method Iterator<mixed,mixed>::rewind().',
						91,
						'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataTentativeReturnTypes
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testTentativeReturnTypes(int $phpVersionId, array $errors): void
	{
		if (PHP_VERSION_ID < 80100) {
			$errors = [];
		}
		if ($phpVersionId > PHP_VERSION_ID) {
			$this->markTestSkipped();
		}

		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/tentative-return-types.php'], $errors);
	}

	public function testCountableBug(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/countable-bug.php'], []);
	}

	public function testBug6264(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-6264.php'], []);
	}

	public function testBug7717(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-7717.php'], []);
	}

	public function testBug6104(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-6104.php'], []);
	}

	public function testBug9391(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-9391.php'], []);
	}

	public function testBugWithIndirectPrototype(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/overriding-indirect-prototype.php'], [
			[
				'Return type mixed of method OverridingIndirectPrototype\Baz::doFoo() is not covariant with return type string of method OverridingIndirectPrototype\Bar::doFoo().',
				28,
			],
		]);
	}

	public function testBug10043(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-10043.php'], [
			[
				'Method Bug10043\C::foo() overrides final method Bug10043\B::foo().',
				17,
			],
		]);
	}

	public function testBug7859(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-7859.php'], [
			[
				'Method Bug7859\ExtendingClassImplementingSomeInterface::getList() overrides method Bug7859\ImplementingSomeInterface::getList() but misses parameter #2 $b.',
				21,
			],
			[
				'Method Bug7859\ExtendingClassNotImplementingSomeInterface::getList() overrides method Bug7859\NotImplementingSomeInterface::getList() but misses parameter #2 $b.',
				37,
			],
		]);
	}

	public function testBug8081(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-8081.php'], [
			[
				'Return type mixed of method Bug8081\three::foo() is not covariant with return type array of method Bug8081\two::foo().',
				21,
			],
		]);
	}

	public function testBug8500(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-8500.php'], [
			[
				'Return type mixed of method Bug8500\DBOHB::test() is not covariant with return type Bug8500\DBOA of method Bug8500\DBOHA::test().',
				30,
			],
		]);
	}

	public function testBug9014(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-9014.php'], [
			[
				'Method Bug9014\Bar::test() overrides method Bug9014\Foo::test() but misses parameter #2 $test.',
				16,
			],
			[
				'Return type mixed of method Bug9014\extended::renderForUser() is not covariant with return type string of method Bug9014\middle::renderForUser().',
				42,
			],
		]);
	}

	public function testBug9135(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-9135.php'], [
			[
				'Method Bug9135\Sub::sayHello() overrides @final method Bug9135\HelloWorld::sayHello().',
				15,
			],
		]);
	}

	public function testBug10101(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-10101.php'], [
			[
				'Return type mixed of method Bug10101\B::next() is not covariant with return type void of method Bug10101\A::next().',
				10,
			],
		]);
	}

	public function testBug9615(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$tipText = 'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.';

		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/bug-9615.php'], [
			[
				'Return type mixed of method Bug9615\ExpectComplaintsHere::accept() is not covariant with tentative return type bool of method FilterIterator<mixed,mixed,Traversable<mixed, mixed>>::accept().',
				19,
				$tipText,
			],
			[
				'Return type mixed of method Bug9615\ExpectComplaintsHere::getChildren() is not covariant with tentative return type RecursiveIterator|null of method RecursiveIterator<mixed,mixed>::getChildren().',
				20,
				$tipText,
			],
		]);
	}

}
