<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use AttributeReflectionTest\Foo;
use AttributeReflectionTest\MutualAttrA;
use AttributeReflectionTest\MutualAttrB;
use AttributeReflectionTest\MyAttr;
use AttributeReflectionTest\SelfReferencingAttr;
use AttributeReflectionTest\SelfRefOnParam;
use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function count;
use const PHP_VERSION_ID;

class AttributeReflectionTest extends PHPStanTestCase
{

	public static function dataAttributeReflections(): iterable
	{
		$reflectionProvider = self::createReflectionProvider();

		yield [
			$reflectionProvider->getFunction(new Name('AttributeReflectionTest\\myFunction'), null)->getAttributes(),
			[
				[MyAttr::class, []],
			],
		];

		yield [
			$reflectionProvider->getFunction(new Name('AttributeReflectionTest\\myFunction2'), null)->getAttributes(),
			[
				['AttributeReflectionTest\\Nonexistent', []],
			],
		];

		yield [
			$reflectionProvider->getFunction(new Name('AttributeReflectionTest\\myFunction3'), null)->getAttributes(),
			[],
		];

		yield [
			$reflectionProvider->getFunction(new Name('AttributeReflectionTest\\myFunction4'), null)->getAttributes(),
			[
				[
					MyAttr::class,
					[
						'one' => '11',
						'two' => '12',
					],
				],
			],
		];

		$foo = $reflectionProvider->getClass(Foo::class);

		yield [
			$foo->getAttributes(),
			[
				[
					MyAttr::class,
					[
						'one' => '1',
						'two' => '2',
					],
				],
			],
		];

		yield [
			$foo->getConstant('MY_CONST')->getAttributes(),
			[
				[
					MyAttr::class,
					[
						'one' => '3',
						'two' => '4',
					],
				],
			],
		];

		yield [
			$foo->getNativeProperty('prop')->getAttributes(),
			[
				[
					MyAttr::class,
					[
						'one' => '5',
						'two' => '6',
					],
				],
			],
		];

		yield [
			$foo->getConstructor()->getAttributes(),
			[
				[
					MyAttr::class,
					[
						'one' => '7',
						'two' => '8',
					],
				],
			],
		];

		if (PHP_VERSION_ID >= 80100) {
			$enum = $reflectionProvider->getClass('AttributeReflectionTest\\FooEnum');

			yield [
				$enum->getEnumCase('TEST')->getAttributes(),
				[
					[
						MyAttr::class,
						[
							'one' => '15',
							'two' => '16',
						],
					],
				],
			];

			yield [
				$enum->getEnumCases()['TEST']->getAttributes(),
				[
					[
						MyAttr::class,
						[
							'one' => '15',
							'two' => '16',
						],
					],
				],
			];
		}

		if (PHP_VERSION_ID >= 80500) {
			yield [
				$reflectionProvider->getConstant(new Name('AttributeReflectionTest\\ExampleConstWithAttribute'), null)->getAttributes(),
				[
					[
						MyAttr::class,
						[
							'one' => '1',
							'two' => '2',
						],
					],
				],
			];

			$barClosureInAttribute = $reflectionProvider->getClass('ClosureInAttribute\\Bar');
			$barClosureInAttributeMethod = $barClosureInAttribute->getNativeMethod('doBar');
			yield [
				$barClosureInAttributeMethod->getOnlyVariant()->getParameters()[0]->getAttributes(),
				[
					[
						'ClosureInAttribute\\AttrWithCallback',
						[
							'callback' => 'Closure(int): void',
						],
					],
				],
			];
			yield [
				$barClosureInAttributeMethod->getOnlyVariant()->getParameters()[1]->getAttributes(),
				[
					[
						'ClosureInAttribute\\AttrWithCallback',
						[
							'callback' => 'Closure(int): void',
						],
					],
				],
			];
			yield [
				$barClosureInAttributeMethod->getOnlyVariant()->getParameters()[2]->getAttributes(),
				[
					[
						'ClosureInAttribute\\AttrWithCallback',
						[
							'callback' => 'static-Closure(int): mixed',
						],
					],
				],
			];
			yield [
				$barClosureInAttributeMethod->getOnlyVariant()->getParameters()[3]->getAttributes(),
				[
					[
						'ClosureInAttribute\\AttrWithCallback',
						[
							'callback' => 'static-Closure(int): mixed',
						],
					],
				],
			];
			yield [
				$barClosureInAttributeMethod->getOnlyVariant()->getParameters()[4]->getAttributes(),
				[
					[
						'ClosureInAttribute\\AttrWithCallback',
						[
							'callback' => 'static-Closure(int): string',
						],
					],
				],
			];

			$bazClosureInAttribute = $reflectionProvider->getClass('ClosureInAttribute\\Baz');
			$bazClosureInAttributeMethod = $bazClosureInAttribute->getNativeMethod('doBaz');
			yield [
				$bazClosureInAttributeMethod->getOnlyVariant()->getParameters()[0]->getAttributes(),
				[
					[
						'ClosureInAttribute\\AttrWithCallback2',
						[
							'callback' => 'static-Closure(mixed): mixed',
						],
					],
				],
			];
			yield [
				$bazClosureInAttributeMethod->getOnlyVariant()->getParameters()[1]->getAttributes(),
				[
					[
						'ClosureInAttribute\\AttrWithCallback2',
						[
							'callback' => 'static-Closure(int=): mixed',
						],
					],
				],
			];
			yield [
				$bazClosureInAttributeMethod->getOnlyVariant()->getParameters()[2]->getAttributes(),
				[
					[
						'ClosureInAttribute\\AttrWithCallback2',
						[
							'callback' => 'static-Closure(int ...): mixed',
						],
					],
				],
			];
		}

		yield [
			$foo->getConstructor()->getOnlyVariant()->getParameters()[0]->getAttributes(),
			[
				[
					MyAttr::class,
					[
						'one' => '9',
						'two' => '10',
					],
				],
			],
		];

		$selfRef = $reflectionProvider->getClass(SelfReferencingAttr::class);

		yield [
			$selfRef->getConstructor()->getAttributes(),
			[
				[
					SelfReferencingAttr::class,
					[
						'param' => "'hi'",
					],
				],
			],
		];

		$mutualA = $reflectionProvider->getClass(MutualAttrA::class);

		yield [
			$mutualA->getConstructor()->getAttributes(),
			[
				[
					MutualAttrB::class,
					[
						'param' => "'world'",
					],
				],
			],
		];

		$mutualB = $reflectionProvider->getClass(MutualAttrB::class);

		yield [
			$mutualB->getConstructor()->getAttributes(),
			[
				[
					MutualAttrA::class,
					[
						'param' => "'hello'",
					],
				],
			],
		];

		$selfRefOnParam = $reflectionProvider->getClass(SelfRefOnParam::class);

		yield [
			$selfRefOnParam->getConstructor()->getOnlyVariant()->getParameters()[0]->getAttributes(),
			[
				[
					SelfRefOnParam::class,
					[
						'param' => "'hello'",
					],
				],
			],
		];
	}

	/**
	 * @param list<AttributeReflection> $attributeReflections
	 * @param list<array{string, array<string, string>}> $expectations
	 */
	#[RequiresPhp('>= 8.0.0')]
	#[DataProvider('dataAttributeReflections')]
	public function testAttributeReflections(
		array $attributeReflections,
		array $expectations,
	): void
	{
		$this->assertCount(count($expectations), $attributeReflections);
		foreach ($expectations as $i => [$name, $argumentTypes]) {
			$attribute = $attributeReflections[$i];
			$this->assertSame($name, $attribute->getName());

			$attributeArgumentTypes = $attribute->getArgumentTypes();
			$this->assertCount(count($argumentTypes), $attributeArgumentTypes);

			foreach ($argumentTypes as $argumentName => $argumentType) {
				$this->assertArrayHasKey($argumentName, $attributeArgumentTypes);
				$this->assertSame($argumentType, $attributeArgumentTypes[$argumentName]->describe(VerbosityLevel::precise()));
			}
		}
	}

}
