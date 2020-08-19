<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<OverridingMethodRule>
 */
class OverridingMethodRuleTest extends RuleTestCase
{

	/** @var int */
	private $phpVersionId;

	protected function getRule(): Rule
	{
		return new OverridingMethodRule(
			new PhpVersion($this->phpVersionId),
			new MethodSignatureRule(true, true),
			false
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
	 * @param int $phpVersion
	 * @param string $contravariantMessage
	 */
	public function testOverridingFinalMethod(int $phpVersion, string $contravariantMessage): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->phpVersionId = $phpVersion;
		$this->analyse([__DIR__ . '/data/overriding-method.php'], [
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
		]);
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
	 * @param string $file
	 * @param int $phpVersion
	 * @param mixed[] $expectedErrors
	 */
	public function testParameterContravariance(
		string $file,
		int $phpVersion,
		array $expectedErrors
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
	 * @param int $phpVersion
	 * @param mixed[] $expectedErrors
	 */
	public function testReturnTypeCovariance(
		int $phpVersion,
		array $expectedErrors
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
	 * @param int $phpVersion
	 * @param string $contravariantMessage
	 * @param string $covariantMessage
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
	 * @param int $phpVersion
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

}
