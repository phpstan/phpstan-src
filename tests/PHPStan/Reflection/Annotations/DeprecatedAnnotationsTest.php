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
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;

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

}
