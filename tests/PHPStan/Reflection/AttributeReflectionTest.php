<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use AttributeReflectionTest\Foo;
use AttributeReflectionTest\MyAttr;
use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
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
	}

	/**
	 * @dataProvider dataAttributeReflections
	 * @param list<AttributeReflection> $attributeReflections
	 * @param list<array{string, array<string, string>}> $expectations
	 */
	public function testAttributeReflections(
		array $attributeReflections,
		array $expectations,
	): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0');
		}

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
