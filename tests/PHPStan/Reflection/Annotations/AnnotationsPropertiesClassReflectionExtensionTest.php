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
use function sprintf;

class AnnotationsPropertiesClassReflectionExtensionTest extends PHPStanTestCase
{

	public function dataProperties(): array
	{
		return [
			[
				Foo::class,
				[
					'otherTest' => [
						'class' => Foo::class,
						'type' => 'OtherNamespace\Test',
						'writable' => true,
						'readable' => true,
					],
					'otherTestReadOnly' => [
						'class' => Foo::class,
						'type' => 'OtherNamespace\Ipsum',
						'writable' => false,
						'readable' => true,
					],
					'fooOrBar' => [
						'class' => Foo::class,
						'type' => 'AnnotationsProperties\Foo',
						'writable' => true,
						'readable' => true,
					],
					'conflictingProperty' => [
						'class' => Foo::class,
						'type' => 'OtherNamespace\Ipsum',
						'writable' => true,
						'readable' => true,
					],
					'interfaceProperty' => [
						'class' => FooInterface::class,
						'type' => FooInterface::class,
						'writable' => true,
						'readable' => true,
					],
					'overridenProperty' => [
						'class' => Foo::class,
						'type' => Foo::class,
						'writable' => true,
						'readable' => true,
					],
					'overridenPropertyWithAnnotation' => [
						'class' => Foo::class,
						'type' => Foo::class,
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
						'type' => 'OtherNamespace\Test',
						'writable' => true,
						'readable' => true,
					],
					'otherTestReadOnly' => [
						'class' => Foo::class,
						'type' => 'OtherNamespace\Ipsum',
						'writable' => false,
						'readable' => true,
					],
					'fooOrBar' => [
						'class' => Foo::class,
						'type' => 'AnnotationsProperties\Foo',
						'writable' => true,
						'readable' => true,
					],
					'conflictingProperty' => [
						'class' => Foo::class,
						'type' => 'OtherNamespace\Ipsum',
						'writable' => true,
						'readable' => true,
					],
					'overridenProperty' => [
						'class' => Bar::class,
						'type' => Bar::class,
						'writable' => true,
						'readable' => true,
					],
					'overridenPropertyWithAnnotation' => [
						'class' => Bar::class,
						'type' => Bar::class,
						'writable' => true,
						'readable' => true,
					],
					'conflictingAnnotationProperty' => [
						'class' => Bar::class,
						'type' => Bar::class,
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
						'type' => 'OtherNamespace\Test',
						'writable' => true,
						'readable' => true,
					],
					'otherTestReadOnly' => [
						'class' => Foo::class,
						'type' => 'OtherNamespace\Ipsum',
						'writable' => false,
						'readable' => true,
					],
					'fooOrBar' => [
						'class' => Foo::class,
						'type' => 'AnnotationsProperties\Foo',
						'writable' => true,
						'readable' => true,
					],
					'conflictingProperty' => [
						'class' => Baz::class,
						'type' => 'AnnotationsProperties\Dolor',
						'writable' => true,
						'readable' => true,
					],
					'bazProperty' => [
						'class' => Baz::class,
						'type' => 'AnnotationsProperties\Lorem',
						'writable' => true,
						'readable' => true,
					],
					'traitProperty' => [
						'class' => Baz::class,
						'type' => 'AnnotationsProperties\BazBaz',
						'writable' => true,
						'readable' => true,
					],
					'writeOnlyProperty' => [
						'class' => Baz::class,
						'type' => 'void',
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
						'type' => 'OtherNamespace\Test',
						'writable' => true,
						'readable' => true,
					],
					'otherTestReadOnly' => [
						'class' => Foo::class,
						'type' => 'OtherNamespace\Ipsum',
						'writable' => false,
						'readable' => true,
					],
					'fooOrBar' => [
						'class' => Foo::class,
						'type' => 'AnnotationsProperties\Foo',
						'writable' => true,
						'readable' => true,
					],
					'conflictingProperty' => [
						'class' => Baz::class,
						'type' => 'AnnotationsProperties\Dolor',
						'writable' => true,
						'readable' => true,
					],
					'bazProperty' => [
						'class' => Baz::class,
						'type' => 'AnnotationsProperties\Lorem',
						'writable' => true,
						'readable' => true,
					],
					'traitProperty' => [
						'class' => Baz::class,
						'type' => 'AnnotationsProperties\BazBaz',
						'writable' => true,
						'readable' => true,
					],
					'writeOnlyProperty' => [
						'class' => Baz::class,
						'type' => 'void',
						'writable' => true,
						'readable' => false,
					],
					'numericBazBazProperty' => [
						'class' => BazBaz::class,
						'type' => 'float|int',
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
						'type' => 'int',
						'writable' => true,
						'readable' => true,
					],
					'asymmetricPropertyXw' => [
						'class' => Asymmetric::class,
						'type' => 'int',
						'writable' => true,
						'readable' => true,
					],
					'asymmetricPropertyRx' => [
						'class' => Asymmetric::class,
						'type' => 'int',
						'writable' => true,
						'readable' => true,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataProperties
	 * @param array<string, mixed> $properties
	 */
	public function testProperties(string $className, array $properties): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createMock(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canAccessProperty')->willReturn(true);
		foreach ($properties as $propertyName => $expectedPropertyData) {
			$this->assertTrue(
				$class->hasProperty($propertyName),
				sprintf('Class %s does not define property %s.', $className, $propertyName),
			);

			$property = $class->getProperty($propertyName, $scope);
			$this->assertSame(
				$expectedPropertyData['class'],
				$property->getDeclaringClass()->getName(),
				sprintf('Declaring class of property $%s does not match.', $propertyName),
			);
			$this->assertSame(
				$expectedPropertyData['type'],
				$property->getReadableType()->describe(VerbosityLevel::precise()),
				sprintf('Type of property %s::$%s does not match.', $property->getDeclaringClass()->getName(), $propertyName),
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
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass(Bar::class);
		$this->assertTrue($class->hasNativeProperty('overridenPropertyWithAnnotation'));
		$this->assertSame('AnnotationsProperties\Foo', $class->getNativeProperty('overridenPropertyWithAnnotation')->getReadableType()->describe(VerbosityLevel::precise()));
	}

}
