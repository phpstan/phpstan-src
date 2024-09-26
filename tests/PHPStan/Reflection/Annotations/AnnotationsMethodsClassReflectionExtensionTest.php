<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use AnnotationsMethods\Bar;
use AnnotationsMethods\Baz;
use AnnotationsMethods\BazBaz;
use AnnotationsMethods\Foo;
use AnnotationsMethods\FooInterface;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;

class AnnotationsMethodsClassReflectionExtensionTest extends PHPStanTestCase
{

	public function dataMethods(): array
	{
		$fooMethods = [
			'getInteger' => [
				'class' => Foo::class,
				'returnType' => 'int',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'a',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
					[
						'name' => 'b',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
				],
			],
			'doSomething' => [
				'class' => Foo::class,
				'returnType' => 'void',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'a',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
					[
						'name' => 'b',
						'type' => 'mixed',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
				],
			],
			'getFooOrBar' => [
				'class' => Foo::class,
				'returnType' => 'AnnotationsMethods\Foo',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'methodWithNoReturnType' => [
				'class' => Foo::class,
				'returnType' => 'mixed',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getIntegerStatically' => [
				'class' => Foo::class,
				'returnType' => 'int',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'a',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
					[
						'name' => 'b',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
				],
			],
			'doSomethingStatically' => [
				'class' => Foo::class,
				'returnType' => 'void',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'a',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
					[
						'name' => 'b',
						'type' => 'mixed',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
				],
			],
			'getFooOrBarStatically' => [
				'class' => Foo::class,
				'returnType' => 'AnnotationsMethods\Foo',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'methodWithNoReturnTypeStatically' => [
				'class' => Foo::class,
				'returnType' => 'static(AnnotationsMethods\Foo)',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getIntegerWithDescription' => [
				'class' => Foo::class,
				'returnType' => 'int',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'a',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
					[
						'name' => 'b',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
				],
			],
			'doSomethingWithDescription' => [
				'class' => Foo::class,
				'returnType' => 'void',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'a',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
					[
						'name' => 'b',
						'type' => 'mixed',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
				],
			],
			'getFooOrBarWithDescription' => [
				'class' => Foo::class,
				'returnType' => 'AnnotationsMethods\Foo',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'methodWithNoReturnTypeWithDescription' => [
				'class' => Foo::class,
				'returnType' => 'mixed',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getIntegerStaticallyWithDescription' => [
				'class' => Foo::class,
				'returnType' => 'int',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'a',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
					[
						'name' => 'b',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
				],
			],
			'doSomethingStaticallyWithDescription' => [
				'class' => Foo::class,
				'returnType' => 'void',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'a',
						'type' => 'int',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
					[
						'name' => 'b',
						'type' => 'mixed',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
				],
			],
			'getFooOrBarStaticallyWithDescription' => [
				'class' => Foo::class,
				'returnType' => 'AnnotationsMethods\Foo',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'methodWithNoReturnTypeStaticallyWithDescription' => [
				'class' => Foo::class,
				'returnType' => 'static(AnnotationsMethods\Foo)',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'aStaticMethodThatHasAUniqueReturnTypeInThisClass' => [
				'class' => Foo::class,
				'returnType' => 'bool',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'aStaticMethodThatHasAUniqueReturnTypeInThisClassWithDescription' => [
				'class' => Foo::class,
				'returnType' => 'string',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getIntegerNoParams' => [
				'class' => Foo::class,
				'returnType' => 'int',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'doSomethingNoParams' => [
				'class' => Foo::class,
				'returnType' => 'void',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getFooOrBarNoParams' => [
				'class' => Foo::class,
				'returnType' => 'AnnotationsMethods\Foo',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'methodWithNoReturnTypeNoParams' => [
				'class' => Foo::class,
				'returnType' => 'mixed',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getIntegerStaticallyNoParams' => [
				'class' => Foo::class,
				'returnType' => 'int',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'doSomethingStaticallyNoParams' => [
				'class' => Foo::class,
				'returnType' => 'void',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getFooOrBarStaticallyNoParams' => [
				'class' => Foo::class,
				'returnType' => 'AnnotationsMethods\Foo',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'methodWithNoReturnTypeStaticallyNoParams' => [
				'class' => Foo::class,
				'returnType' => 'static(AnnotationsMethods\Foo)',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getIntegerWithDescriptionNoParams' => [
				'class' => Foo::class,
				'returnType' => 'int',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'doSomethingWithDescriptionNoParams' => [
				'class' => Foo::class,
				'returnType' => 'void',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getFooOrBarWithDescriptionNoParams' => [
				'class' => Foo::class,
				'returnType' => 'AnnotationsMethods\Foo',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getIntegerStaticallyWithDescriptionNoParams' => [
				'class' => Foo::class,
				'returnType' => 'int',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'doSomethingStaticallyWithDescriptionNoParams' => [
				'class' => Foo::class,
				'returnType' => 'void',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'getFooOrBarStaticallyWithDescriptionNoParams' => [
				'class' => Foo::class,
				'returnType' => 'AnnotationsMethods\Foo',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'aStaticMethodThatHasAUniqueReturnTypeInThisClassNoParams' => [
				'class' => Foo::class,
				'returnType' => 'bool|string',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'aStaticMethodThatHasAUniqueReturnTypeInThisClassWithDescriptionNoParams' => [
				'class' => Foo::class,
				'returnType' => 'float|string',
				'isStatic' => true,
				'isVariadic' => false,
				'parameters' => [],
			],
			'methodFromInterface' => [
				'class' => FooInterface::class,
				'returnType' => FooInterface::class,
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'publish' => [
				'class' => Foo::class,
				'returnType' => 'Aws\Result',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'args',
						'type' => 'array',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => true,
						'isVariadic' => false,
					],
				],
			],
			'rotate' => [
				'class' => Foo::class,
				'returnType' => 'AnnotationsMethods\Image',
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [
					[
						'name' => 'angle',
						'type' => 'float',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
					[
						'name' => 'backgroundColor',
						'type' => 'mixed',
						'passedByReference' => PassedByReference::createNo(),
						'isOptional' => false,
						'isVariadic' => false,
					],
				],
			],
			'overridenMethod' => [
				'class' => Foo::class,
				'returnType' => Foo::class,
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
			'overridenMethodWithAnnotation' => [
				'class' => Foo::class,
				'returnType' => Foo::class,
				'isStatic' => false,
				'isVariadic' => false,
				'parameters' => [],
			],
		];
		$barMethods = array_merge(
			$fooMethods,
			[
				'overridenMethod' => [
					'class' => Bar::class,
					'returnType' => Bar::class,
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [],
				],
				'overridenMethodWithAnnotation' => [
					'class' => Bar::class,
					'returnType' => Bar::class,
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [],
				],
				'conflictingMethod' => [
					'class' => Bar::class,
					'returnType' => Foo::class,
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [],
				],
			],
		);
		$bazMethods = array_merge(
			$barMethods,
			[
				'doSomething' => [
					'class' => Baz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'int',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'b',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'getIpsum' => [
					'class' => Baz::class,
					'returnType' => 'OtherNamespace\Ipsum',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'getIpsumStatically' => [
					'class' => Baz::class,
					'returnType' => 'OtherNamespace\Ipsum',
					'isStatic' => true,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'getIpsumWithDescription' => [
					'class' => Baz::class,
					'returnType' => 'OtherNamespace\Ipsum',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'getIpsumStaticallyWithDescription' => [
					'class' => Baz::class,
					'returnType' => 'OtherNamespace\Ipsum',
					'isStatic' => true,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'doSomethingStatically' => [
					'class' => Baz::class,
					'returnType' => 'void',
					'isStatic' => true,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'int',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'b',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'doSomethingWithDescription' => [
					'class' => Baz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'int',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'b',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'doSomethingStaticallyWithDescription' => [
					'class' => Baz::class,
					'returnType' => 'void',
					'isStatic' => true,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'int',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'b',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'doSomethingNoParams' => [
					'class' => Baz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [],
				],
				'doSomethingStaticallyNoParams' => [
					'class' => Baz::class,
					'returnType' => 'void',
					'isStatic' => true,
					'isVariadic' => false,
					'parameters' => [],
				],
				'doSomethingWithDescriptionNoParams' => [
					'class' => Baz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [],
				],
				'doSomethingStaticallyWithDescriptionNoParams' => [
					'class' => Baz::class,
					'returnType' => 'void',
					'isStatic' => true,
					'isVariadic' => false,
					'parameters' => [],
				],
				'methodFromTrait' => [
					'class' => Baz::class,
					'returnType' => BazBaz::class,
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [],
				],
			],
		);
		$bazBazMethods = array_merge(
			$bazMethods,
			[
				'getTest' => [
					'class' => BazBaz::class,
					'returnType' => 'OtherNamespace\Test',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [],
				],
				'getTestStatically' => [
					'class' => BazBaz::class,
					'returnType' => 'OtherNamespace\Test',
					'isStatic' => true,
					'isVariadic' => false,
					'parameters' => [],
				],
				'getTestWithDescription' => [
					'class' => BazBaz::class,
					'returnType' => 'OtherNamespace\Test',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [],
				],
				'getTestStaticallyWithDescription' => [
					'class' => BazBaz::class,
					'returnType' => 'OtherNamespace\Test',
					'isStatic' => true,
					'isVariadic' => false,
					'parameters' => [],
				],
				'doSomethingWithSpecificScalarParamsWithoutDefault' => [
					'class' => BazBaz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'int',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'b',
							'type' => 'int|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'c',
							'type' => 'int',
							'passedByReference' => PassedByReference::createCreatesNewVariable(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'd',
							'type' => 'int|null',
							'passedByReference' => PassedByReference::createCreatesNewVariable(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'doSomethingWithSpecificScalarParamsWithDefault' => [
					'class' => BazBaz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'int|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => false,
						],
						[
							'name' => 'b',
							'type' => 'int|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => false,
						],
						[
							'name' => 'c',
							'type' => 'int|null',
							'passedByReference' => PassedByReference::createCreatesNewVariable(),
							'isOptional' => true,
							'isVariadic' => false,
						],
						[
							'name' => 'd',
							'type' => 'int|null',
							'passedByReference' => PassedByReference::createCreatesNewVariable(),
							'isOptional' => true,
							'isVariadic' => false,
						],
					],
				],
				'doSomethingWithSpecificObjectParamsWithoutDefault' => [
					'class' => BazBaz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'OtherNamespace\Ipsum',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'b',
							'type' => 'OtherNamespace\Ipsum|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'c',
							'type' => 'OtherNamespace\Ipsum',
							'passedByReference' => PassedByReference::createCreatesNewVariable(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'd',
							'type' => 'OtherNamespace\Ipsum|null',
							'passedByReference' => PassedByReference::createCreatesNewVariable(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
				'doSomethingWithSpecificObjectParamsWithDefault' => [
					'class' => BazBaz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'OtherNamespace\Ipsum|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => false,
						],
						[
							'name' => 'b',
							'type' => 'OtherNamespace\Ipsum|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => false,
						],
						[
							'name' => 'c',
							'type' => 'OtherNamespace\Ipsum|null',
							'passedByReference' => PassedByReference::createCreatesNewVariable(),
							'isOptional' => true,
							'isVariadic' => false,
						],
						[
							'name' => 'd',
							'type' => 'OtherNamespace\Ipsum|null',
							'passedByReference' => PassedByReference::createCreatesNewVariable(),
							'isOptional' => true,
							'isVariadic' => false,
						],
					],
				],
				'doSomethingWithSpecificVariadicScalarParamsNotNullable' => [
					'class' => BazBaz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => true,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'int',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => true,
						],
					],
				],
				'doSomethingWithSpecificVariadicScalarParamsNullable' => [
					'class' => BazBaz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => true,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'int|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => true,
						],
					],
				],
				'doSomethingWithSpecificVariadicObjectParamsNotNullable' => [
					'class' => BazBaz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => true,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'OtherNamespace\Ipsum',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => true,
						],
					],
				],
				'doSomethingWithSpecificVariadicObjectParamsNullable' => [
					'class' => BazBaz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => true,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'OtherNamespace\Ipsum|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => true,
						],
					],
				],
				'doSomethingWithComplicatedParameters' => [
					'class' => BazBaz::class,
					'returnType' => 'void',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'a',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'b',
							'type' => 'mixed',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => false,
						],
						[
							'name' => 'c',
							'type' => 'bool|float|int|OtherNamespace\\Test|string',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'd',
							'type' => 'bool|float|int|OtherNamespace\\Test|string|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => true,
							'isVariadic' => false,
						],
					],
				],
				'paramMultipleTypesWithExtraSpaces' => [
					'class' => BazBaz::class,
					'returnType' => 'float|int',
					'isStatic' => false,
					'isVariadic' => false,
					'parameters' => [
						[
							'name' => 'string',
							'type' => 'string|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
						[
							'name' => 'object',
							'type' => 'OtherNamespace\\Test|null',
							'passedByReference' => PassedByReference::createNo(),
							'isOptional' => false,
							'isVariadic' => false,
						],
					],
				],
			],
		);

		return [
			[Foo::class, $fooMethods],
			[Bar::class, $barMethods],
			[Baz::class, $bazMethods],
			[BazBaz::class, $bazBazMethods],
		];
	}

	/**
	 * @dataProvider dataMethods
	 * @param array<string, mixed> $methods
	 */
	public function testMethods(string $className, array $methods): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass($className);
		$scope = $this->createMock(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canCallMethod')->willReturn(true);
		foreach ($methods as $methodName => $expectedMethodData) {
			$this->assertTrue($class->hasMethod($methodName), sprintf('Method %s() not found in class %s.', $methodName, $className));

			$method = $class->getMethod($methodName, $scope);
			$selectedParametersAcceptor = $method->getOnlyVariant();
			$this->assertSame(
				$expectedMethodData['class'],
				$method->getDeclaringClass()->getName(),
				sprintf('Declaring class of method %s() does not match.', $methodName),
			);
			$this->assertSame(
				$expectedMethodData['returnType'],
				$selectedParametersAcceptor->getReturnType()->describe(VerbosityLevel::precise()),
				sprintf('Return type of method %s::%s() does not match', $className, $methodName),
			);
			$this->assertSame(
				$expectedMethodData['isStatic'],
				$method->isStatic(),
				sprintf('Scope of method %s::%s() does not match', $className, $methodName),
			);
			$this->assertSame(
				$expectedMethodData['isVariadic'],
				$selectedParametersAcceptor->isVariadic(),
				sprintf('Method %s::%s() does not match expected variadicity', $className, $methodName),
			);
			$this->assertCount(
				count($expectedMethodData['parameters']),
				$selectedParametersAcceptor->getParameters(),
				sprintf('Method %s::%s() does not match expected count of parameters', $className, $methodName),
			);
			foreach ($selectedParametersAcceptor->getParameters() as $i => $parameter) {
				$this->assertSame(
					$expectedMethodData['parameters'][$i]['name'],
					$parameter->getName(),
				);
				$this->assertSame(
					$expectedMethodData['parameters'][$i]['type'],
					$parameter->getType()->describe(VerbosityLevel::precise()),
				);
				$this->assertTrue(
					$expectedMethodData['parameters'][$i]['passedByReference']->equals($parameter->passedByReference()),
				);
				$this->assertSame(
					$expectedMethodData['parameters'][$i]['isOptional'],
					$parameter->isOptional(),
				);
				$this->assertSame(
					$expectedMethodData['parameters'][$i]['isVariadic'],
					$parameter->isVariadic(),
				);
			}
		}
	}

	public function testOverridingNativeMethodsWithAnnotationsDoesNotBreakGetNativeMethod(): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$class = $reflectionProvider->getClass(Bar::class);
		$this->assertTrue($class->hasNativeMethod('overridenMethodWithAnnotation'));
		$this->assertInstanceOf(PhpMethodReflection::class, $class->getNativeMethod('overridenMethodWithAnnotation'));
	}

}
