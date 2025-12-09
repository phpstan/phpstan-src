<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use AnnotationsProperties\Asymmetric;
use AnnotationsProperties\Bar;
use AnnotationsProperties\Baz;
use AnnotationsProperties\BazBaz;
use AnnotationsProperties\Foo;
use AnnotationsProperties\FooInterface;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class AnnotationsPropertiesClassReflectionExtensionTest extends PHPStanTestCase
{

	public static function dataProperties(): array
	{
		return [
			[
				Foo::class,
				[
					'otherTest' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Test',
						'writableType' => 'OtherNamespace\Test',
						'writable' => true,
						'readable' => true,
					],
					'otherTestReadOnly' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Ipsum',
						'writableType' => '*NEVER*',
						'writable' => false,
						'readable' => true,
					],
					'fooOrBar' => [
						'class' => Foo::class,
						'readableType' => 'AnnotationsProperties\Foo',
						'writableType' => 'AnnotationsProperties\Foo',
						'writable' => true,
						'readable' => true,
					],
					'conflictingProperty' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Ipsum',
						'writableType' => 'OtherNamespace\Ipsum',
						'writable' => true,
						'readable' => true,
					],
					'interfaceProperty' => [
						'class' => FooInterface::class,
						'readableType' => FooInterface::class,
						'writableType' => FooInterface::class,
						'writable' => true,
						'readable' => true,
					],
					'overridenProperty' => [
						'class' => Foo::class,
						'readableType' => Foo::class,
						'writableType' => Foo::class,
						'writable' => true,
						'readable' => true,
					],
					'overridenPropertyWithAnnotation' => [
						'class' => Foo::class,
						'readableType' => Foo::class,
						'writableType' => Foo::class,
						'writable' => true,
						'readable' => true,
					],
				],
			],
			[
				Bar::class,
				[
					'otherTest' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Test',
						'writableType' => 'OtherNamespace\Test',
						'writable' => true,
						'readable' => true,
					],
					'otherTestReadOnly' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Ipsum',
						'writableType' => '*NEVER*',
						'writable' => false,
						'readable' => true,
					],
					'fooOrBar' => [
						'class' => Foo::class,
						'readableType' => 'AnnotationsProperties\Foo',
						'writableType' => 'AnnotationsProperties\Foo',
						'writable' => true,
						'readable' => true,
					],
					'conflictingProperty' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Ipsum',
						'writableType' => 'OtherNamespace\Ipsum',
						'writable' => true,
						'readable' => true,
					],
					'overridenProperty' => [
						'class' => Bar::class,
						'readableType' => Bar::class,
						'writableType' => Bar::class,
						'writable' => true,
						'readable' => true,
					],
					'overridenPropertyWithAnnotation' => [
						'class' => Bar::class,
						'readableType' => Bar::class,
						'writableType' => Bar::class,
						'writable' => true,
						'readable' => true,
					],
					'conflictingAnnotationProperty' => [
						'class' => Bar::class,
						'readableType' => Foo::class,
						'writableType' => Foo::class,
						'writable' => true,
						'readable' => true,
					],
				],
			],
			[
				Baz::class,
				[
					'otherTest' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Test',
						'writableType' => 'OtherNamespace\Test',
						'writable' => true,
						'readable' => true,
					],
					'otherTestReadOnly' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Ipsum',
						'writableType' => '*NEVER*',
						'writable' => false,
						'readable' => true,
					],
					'fooOrBar' => [
						'class' => Foo::class,
						'readableType' => 'AnnotationsProperties\Foo',
						'writableType' => 'AnnotationsProperties\Foo',
						'writable' => true,
						'readable' => true,
					],
					'conflictingProperty' => [
						'class' => Baz::class,
						'readableType' => 'AnnotationsProperties\Dolor',
						'writableType' => 'AnnotationsProperties\Dolor',
						'writable' => true,
						'readable' => true,
					],
					'bazProperty' => [
						'class' => Baz::class,
						'readableType' => 'AnnotationsProperties\Lorem',
						'writableType' => 'AnnotationsProperties\Lorem',
						'writable' => true,
						'readable' => true,
					],
					'traitProperty' => [
						'class' => Baz::class,
						'readableType' => 'AnnotationsProperties\BazBaz',
						'writableType' => 'AnnotationsProperties\BazBaz',
						'writable' => true,
						'readable' => true,
					],
					'writeOnlyProperty' => [
						'class' => Baz::class,
						'readableType' => '*NEVER*',
						'writableType' => 'AnnotationsProperties\Lorem|null',
						'writable' => true,
						'readable' => false,
					],
				],
			],
			[
				BazBaz::class,
				[
					'otherTest' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Test',
						'writableType' => 'OtherNamespace\Test',
						'writable' => true,
						'readable' => true,
					],
					'otherTestReadOnly' => [
						'class' => Foo::class,
						'readableType' => 'OtherNamespace\Ipsum',
						'writableType' => '*NEVER*',
						'writable' => false,
						'readable' => true,
					],
					'fooOrBar' => [
						'class' => Foo::class,
						'readableType' => 'AnnotationsProperties\Foo',
						'writableType' => 'AnnotationsProperties\Foo',
						'writable' => true,
						'readable' => true,
					],
					'conflictingProperty' => [
						'class' => Baz::class,
						'readableType' => 'AnnotationsProperties\Dolor',
						'writableType' => 'AnnotationsProperties\Dolor',
						'writable' => true,
						'readable' => true,
					],
					'bazProperty' => [
						'class' => Baz::class,
						'readableType' => 'AnnotationsProperties\Lorem',
						'writableType' => 'AnnotationsProperties\Lorem',
						'writable' => true,
						'readable' => true,
					],
					'traitProperty' => [
						'class' => Baz::class,
						'readableType' => 'AnnotationsProperties\BazBaz',
						'writableType' => 'AnnotationsProperties\BazBaz',
						'writable' => true,
						'readable' => true,
					],
					'writeOnlyProperty' => [
						'class' => Baz::class,
						'readableType' => '*NEVER*',
						'writableType' => 'AnnotationsProperties\Lorem|null',
						'writable' => true,
						'readable' => false,
					],
					'numericBazBazProperty' => [
						'class' => BazBaz::class,
						'readableType' => 'float|int',
						'writableType' => 'float|int',
						'writable' => true,
						'readable' => true,
					],
				],
			],
			[
				Asymmetric::class,
				[
					'asymmetricPropertyRw' => [
						'class' => Asymmetric::class,
						'readableType' => 'int',
						'writableType' => 'int|string',
						'writable' => true,
						'readable' => true,
					],
					'asymmetricPropertyXw' => [
						'class' => Asymmetric::class,
						'readableType' => 'int',
						'writableType' => 'int|string',
						'writable' => true,
						'readable' => true,
					],
					'asymmetricPropertyRx' => [
						'class' => Asymmetric::class,
						'readableType' => 'int',
						'writableType' => 'int|string',
						'writable' => true,
						'readable' => true,
					],
				],
			],
		];
	}

	/**
	 * @param array<string, mixed> $properties
	 */
	#[DataProvider('dataProperties')]
	public function testProperties(string $className, array $properties): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createStub(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canAccessProperty')->willReturn(true);
		$scope->method('canReadProperty')->willReturn(true);
		$scope->method('canWriteProperty')->willReturn(true);
		foreach ($properties as $propertyName => $expectedPropertyData) {
			$this->assertTrue(
				$class->hasInstanceProperty($propertyName),
				sprintf('Class %s does not define property %s.', $className, $propertyName),
			);

			$property = $class->getInstanceProperty($propertyName, $scope);
			$this->assertSame(
				$expectedPropertyData['class'],
				$property->getDeclaringClass()->getName(),
				sprintf('Declaring class of property $%s does not match.', $propertyName),
			);
			$this->assertSame(
				$expectedPropertyData['readableType'],
				$property->getReadableType()->describe(VerbosityLevel::precise()),
				sprintf('Readable type of property %s::$%s does not match.', $property->getDeclaringClass()->getName(), $propertyName),
			);
			$this->assertSame(
				$expectedPropertyData['writableType'],
				$property->getWritableType()->describe(VerbosityLevel::precise()),
				sprintf('Writable type of property %s::$%s does not match.', $property->getDeclaringClass()->getName(), $propertyName),
			);
			$this->assertSame(
				$expectedPropertyData['readable'],
				$property->isReadable(),
				sprintf('Property %s::$%s readability is not as expected.', $property->getDeclaringClass()->getName(), $propertyName),
			);
			$this->assertSame(
				$expectedPropertyData['writable'],
				$property->isWritable(),
				sprintf('Property %s::$%s writability is not as expected.', $property->getDeclaringClass()->getName(), $propertyName),
			);
		}
	}

	public function testOverridingNativePropertiesWithAnnotationsDoesNotBreakGetNativeProperty(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$class = $reflectionProvider->getClass(Bar::class);
		$this->assertTrue($class->hasNativeProperty('overridenPropertyWithAnnotation'));
		$this->assertSame('AnnotationsProperties\Foo', $class->getNativeProperty('overridenPropertyWithAnnotation')->getReadableType()->describe(VerbosityLevel::precise()));
	}

}
