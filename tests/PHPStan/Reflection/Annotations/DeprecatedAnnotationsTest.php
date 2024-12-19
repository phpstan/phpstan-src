<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use DeprecatedAnnotations\Baz;
use DeprecatedAnnotations\BazInterface;
use DeprecatedAnnotations\DeprecatedBar;
use DeprecatedAnnotations\DeprecatedFoo;
use DeprecatedAnnotations\DeprecatedWithMultipleTags;
use DeprecatedAnnotations\Foo;
use DeprecatedAnnotations\FooInterface;
use DeprecatedAnnotations\SubBazInterface;
use DeprecatedAttributeConstants\FooWithConstants;
use DeprecatedAttributeMethods\FooWithMethods;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use const PHP_VERSION_ID;

class DeprecatedAnnotationsTest extends PHPStanTestCase
{

	public function dataDeprecatedAnnotations(): array
	{
		return [
			[
				false,
				Foo::class,
				null,
				[
					'constant' => [
						'FOO' => null,
					],
					'method' => [
						'foo' => null,
						'staticFoo' => null,
					],
					'property' => [
						'foo' => null,
						'staticFoo' => null,
					],
				],
			],
			[
				true,
				DeprecatedFoo::class,
				'in 1.0.0.',
				[
					'constant' => [
						'DEPRECATED_FOO' => 'Deprecated constant.',
					],
					'method' => [
						'deprecatedFoo' => 'method.',
						'deprecatedStaticFoo' => 'static method.',
					],
					'property' => [
						'deprecatedFoo' => null,
						'deprecatedStaticFoo' => null,
					],
				],
			],
			[
				false,
				FooInterface::class,
				null,
				[
					'constant' => [
						'FOO' => null,
					],
					'method' => [
						'foo' => null,
						'staticFoo' => null,
					],
				],
			],
			[
				true,
				DeprecatedWithMultipleTags::class,
				"in Foo 1.1.0 and will be removed in 1.5.0, use\n  \\Foo\\Bar\\NotDeprecated instead.",
				[
					'method' => [
						'deprecatedFoo' => "in Foo 1.1.0, will be removed in Foo 1.5.0, use\n  \\Foo\\Bar\\NotDeprecated::replacementFoo() instead.",
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataDeprecatedAnnotations
	 * @param array<string, mixed> $deprecatedAnnotations
	 */
	public function testDeprecatedAnnotations(bool $deprecated, string $className, ?string $classDeprecation, array $deprecatedAnnotations): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createMock(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canAccessProperty')->willReturn(true);

		$this->assertSame($deprecated, $class->isDeprecated());
		$this->assertSame($classDeprecation, $class->getDeprecatedDescription());

		foreach ($deprecatedAnnotations['method'] ?? [] as $methodName => $deprecatedMessage) {
			$methodAnnotation = $class->getMethod($methodName, $scope);
			$this->assertSame($deprecated, $methodAnnotation->isDeprecated()->yes());
			$this->assertSame($deprecatedMessage, $methodAnnotation->getDeprecatedDescription());
		}

		foreach ($deprecatedAnnotations['property'] ?? [] as $propertyName => $deprecatedMessage) {
			$propertyAnnotation = $class->getProperty($propertyName, $scope);
			$this->assertSame($deprecated, $propertyAnnotation->isDeprecated()->yes());
			$this->assertSame($deprecatedMessage, $propertyAnnotation->getDeprecatedDescription());
		}

		foreach ($deprecatedAnnotations['constant'] ?? [] as $constantName => $deprecatedMessage) {
			$constantAnnotation = $class->getConstant($constantName);
			$this->assertSame($deprecated, $constantAnnotation->isDeprecated()->yes());
			$this->assertSame($deprecatedMessage, $constantAnnotation->getDeprecatedDescription());
		}
	}

	public function testDeprecatedUserFunctions(): void
	{
		require_once __DIR__ . '/data/annotations-deprecated.php';

		$reflectionProvider = $this->createReflectionProvider();

		$this->assertFalse($reflectionProvider->getFunction(new Name\FullyQualified('DeprecatedAnnotations\foo'), null)->isDeprecated()->yes());
		$this->assertTrue($reflectionProvider->getFunction(new Name\FullyQualified('DeprecatedAnnotations\deprecatedFoo'), null)->isDeprecated()->yes());
	}

	public function testNonDeprecatedNativeFunctions(): void
	{
		$reflectionProvider = $this->createReflectionProvider();

		$this->assertFalse($reflectionProvider->getFunction(new Name('str_replace'), null)->isDeprecated()->yes());
		$this->assertFalse($reflectionProvider->getFunction(new Name('get_class'), null)->isDeprecated()->yes());
		$this->assertFalse($reflectionProvider->getFunction(new Name('function_exists'), null)->isDeprecated()->yes());
	}

	public function testDeprecatedMethodsFromInterface(): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass(DeprecatedBar::class);
		$this->assertTrue($class->getNativeMethod('superDeprecated')->isDeprecated()->yes());
	}

	public function testNotDeprecatedChildMethods(): void
	{
		$reflectionProvider = $this->createReflectionProvider();

		$this->assertTrue($reflectionProvider->getClass(BazInterface::class)->getNativeMethod('superDeprecated')->isDeprecated()->yes());
		$this->assertTrue($reflectionProvider->getClass(SubBazInterface::class)->getNativeMethod('superDeprecated')->isDeprecated()->no());
		$this->assertTrue($reflectionProvider->getClass(Baz::class)->getNativeMethod('superDeprecated')->isDeprecated()->no());
	}

	public function dataDeprecatedAttributeAboveFunction(): iterable
	{
		yield [
			'DeprecatedAttributeFunctions\\notDeprecated',
			TrinaryLogic::createNo(),
			null,
		];
		yield [
			'DeprecatedAttributeFunctions\\foo',
			TrinaryLogic::createYes(),
			null,
		];
		yield [
			'DeprecatedAttributeFunctions\\fooWithMessage',
			TrinaryLogic::createYes(),
			'msg',
		];
		yield [
			'DeprecatedAttributeFunctions\\fooWithMessage2',
			TrinaryLogic::createYes(),
			'msg2',
		];
		yield [
			'DeprecatedAttributeFunctions\\fooWithConstantMessage',
			TrinaryLogic::createYes(),
			'DeprecatedAttributeFunctions\\fooWithConstantMessage',
		];
	}

	/**
	 * @dataProvider dataDeprecatedAttributeAboveFunction
	 *
	 * @param non-empty-string $functionName
	 */
	public function testDeprecatedAttributeAboveFunction(string $functionName, TrinaryLogic $isDeprecated, ?string $deprecatedDescription): void
	{
		require_once __DIR__ . '/data/deprecated-attribute-functions.php';

		$reflectionProvider = $this->createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name($functionName), null);
		$this->assertSame($isDeprecated->describe(), $function->isDeprecated()->describe());
		$this->assertSame($deprecatedDescription, $function->getDeprecatedDescription());
	}

	public function dataDeprecatedAttributeAboveMethod(): iterable
	{
		yield [
			FooWithMethods::class,
			'notDeprecated',
			TrinaryLogic::createNo(),
			null,
		];
		yield [
			FooWithMethods::class,
			'foo',
			TrinaryLogic::createYes(),
			null,
		];
		yield [
			FooWithMethods::class,
			'fooWithMessage',
			TrinaryLogic::createYes(),
			'msg',
		];
		yield [
			FooWithMethods::class,
			'fooWithMessage2',
			TrinaryLogic::createYes(),
			'msg2',
		];
	}

	/**
	 * @dataProvider dataDeprecatedAttributeAboveMethod
	 */
	public function testDeprecatedAttributeAboveMethod(string $className, string $methodName, TrinaryLogic $isDeprecated, ?string $deprecatedDescription): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$method = $class->getNativeMethod($methodName);
		$this->assertSame($isDeprecated->describe(), $method->isDeprecated()->describe());
		$this->assertSame($deprecatedDescription, $method->getDeprecatedDescription());
	}

	public function dataDeprecatedAttributeAboveClassConstant(): iterable
	{
		yield [
			FooWithConstants::class,
			'notDeprecated',
			TrinaryLogic::createNo(),
			null,
		];
		yield [
			FooWithConstants::class,
			'foo',
			TrinaryLogic::createYes(),
			null,
		];
		yield [
			FooWithConstants::class,
			'fooWithMessage',
			TrinaryLogic::createYes(),
			'msg',
		];
		yield [
			FooWithConstants::class,
			'fooWithMessage2',
			TrinaryLogic::createYes(),
			'msg2',
		];

		if (PHP_VERSION_ID < 80100) {
			return;
		}

		yield [
			'DeprecatedAttributeEnum\\EnumWithDeprecatedCases',
			'foo',
			TrinaryLogic::createYes(),
			null,
		];
		yield [
			'DeprecatedAttributeEnum\\EnumWithDeprecatedCases',
			'fooWithMessage',
			TrinaryLogic::createYes(),
			'msg',
		];
		yield [
			'DeprecatedAttributeEnum\\EnumWithDeprecatedCases',
			'fooWithMessage2',
			TrinaryLogic::createYes(),
			'msg2',
		];
	}

	/**
	 * @dataProvider dataDeprecatedAttributeAboveClassConstant
	 */
	public function testDeprecatedAttributeAboveClassConstant(string $className, string $constantName, TrinaryLogic $isDeprecated, ?string $deprecatedDescription): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$constant = $class->getConstant($constantName);
		$this->assertSame($isDeprecated->describe(), $constant->isDeprecated()->describe());
		$this->assertSame($deprecatedDescription, $constant->getDeprecatedDescription());
	}

	public function dataDeprecatedAttributeAboveEnumCase(): iterable
	{
		yield [
			'DeprecatedAttributeEnum\\EnumWithDeprecatedCases',
			'foo',
			TrinaryLogic::createYes(),
			null,
		];
		yield [
			'DeprecatedAttributeEnum\\EnumWithDeprecatedCases',
			'fooWithMessage',
			TrinaryLogic::createYes(),
			'msg',
		];
		yield [
			'DeprecatedAttributeEnum\\EnumWithDeprecatedCases',
			'fooWithMessage2',
			TrinaryLogic::createYes(),
			'msg2',
		];
	}

	/**
	 * @dataProvider dataDeprecatedAttributeAboveEnumCase
	 */
	public function testDeprecatedAttributeAboveEnumCase(string $className, string $caseName, TrinaryLogic $isDeprecated, ?string $deprecatedDescription): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$case = $class->getEnumCase($caseName);
		$this->assertSame($isDeprecated->describe(), $case->isDeprecated()->describe());
		$this->assertSame($deprecatedDescription, $case->getDeprecatedDescription());
	}

	public function dataDeprecatedAttributeAbovePropertyHook(): iterable
	{
		yield [
			'DeprecatedAttributePropertyHooks\\Foo',
			'i',
			'get',
			TrinaryLogic::createNo(),
			null,
		];
		yield [
			'DeprecatedAttributePropertyHooks\\Foo',
			'j',
			'get',
			TrinaryLogic::createYes(),
			null,
		];
		yield [
			'DeprecatedAttributePropertyHooks\\Foo',
			'k',
			'get',
			TrinaryLogic::createYes(),
			'msg',
		];
		yield [
			'DeprecatedAttributePropertyHooks\\Foo',
			'l',
			'get',
			TrinaryLogic::createYes(),
			'msg2',
		];
	}

	/**
	 * @dataProvider dataDeprecatedAttributeAbovePropertyHook
	 * @param 'get'|'set' $hookName
	 */
	public function testDeprecatedAttributeAbovePropertyHook(string $className, string $propertyName, string $hookName, TrinaryLogic $isDeprecated, ?string $deprecatedDescription): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$property = $class->getNativeProperty($propertyName);
		$hook = $property->getHook($hookName);
		$this->assertSame($isDeprecated->describe(), $hook->isDeprecated()->describe());
		$this->assertSame($deprecatedDescription, $hook->getDeprecatedDescription());
	}

}
