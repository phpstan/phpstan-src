<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function count;

#[RequiresPhp('>= 8.4')]
class ClassReflectionPropertyHooksTest extends PHPStanTestCase
{

	public static function dataPropertyHooks(): iterable
	{
		yield [
			'PropertyHooksTypes\\Foo',
			'i',
			'set',
			['int'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\Foo',
			'i',
			'get',
			[],
			'int',
			true,
		];

		yield [
			'PropertyHooksTypes\\Foo',
			'l',
			'get',
			[],
			'array<string>',
			true,
		];

		yield [
			'PropertyHooksTypes\\Foo',
			'n',
			'set',
			['array<string>|int'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooShort',
			'i',
			'set',
			['int'],
			'void',
			false,
		];

		yield [
			'PropertyHooksTypes\\FooShort',
			'k',
			'set',
			['int|string'],
			'void',
			false,
		];

		yield [
			'PropertyHooksTypes\\FooShort',
			'l',
			'set',
			['array<string>'],
			'void',
			false,
		];

		yield [
			'PropertyHooksTypes\\FooShort',
			'm',
			'set',
			['array<string>'],
			'void',
			false,
		];

		yield [
			'PropertyHooksTypes\\FooShort',
			'n',
			'set',
			['array<string>|int'],
			'void',
			false,
		];

		yield [
			'PropertyHooksTypes\\FooConstructor',
			'i',
			'set',
			['int'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooConstructor',
			'j',
			'set',
			['int'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooConstructor',
			'k',
			'set',
			['int|string'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooConstructor',
			'l',
			'set',
			['array<string>'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooConstructor',
			'l',
			'get',
			[],
			'array<string>',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooConstructor',
			'm',
			'set',
			['array<string>'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooConstructor',
			'n',
			'set',
			['array<string>|int'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooConstructorWithParam',
			'l',
			'set',
			['array<string>'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooConstructorWithParam',
			'l',
			'get',
			[],
			'array<string>',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooConstructorWithParam',
			'm',
			'set',
			['array<string>'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooGenerics',
			'm',
			'set',
			['array<T of stdClass (class PropertyHooksTypes\FooGenerics, parameter)>'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooGenerics',
			'n',
			'set',
			['array<T of stdClass (class PropertyHooksTypes\FooGenerics, parameter)>|int'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooGenerics',
			'm',
			'get',
			[],
			'array<T of stdClass (class PropertyHooksTypes\FooGenerics, parameter)>',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooGenerics',
			'n',
			'get',
			[],
			'int',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooGenerics',
			'n',
			'get',
			[],
			'int',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooGenerics',
			'm',
			'get',
			[],
			'array<T of stdClass (class PropertyHooksTypes\FooGenerics, parameter)>',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooGenericsConstructor',
			'l',
			'set',
			['array<T of stdClass (class PropertyHooksTypes\FooGenericsConstructor, parameter)>'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooGenericsConstructor',
			'm',
			'set',
			['array<T of stdClass (class PropertyHooksTypes\FooGenericsConstructor, parameter)>'],
			'void',
			true,
		];

		yield [
			'PropertyHooksTypes\\FooGenericsConstructor',
			'n',
			'set',
			['array<T of stdClass (class PropertyHooksTypes\FooGenericsConstructor, parameter)>|int'],
			'void',
			true,
		];
	}

	/**
	 * @param ExtendedPropertyReflection::HOOK_* $hookName
	 * @param string[] $parameterTypes
	 */
	#[DataProvider('dataPropertyHooks')]
	public function testPropertyHooks(
		string $className,
		string $propertyName,
		string $hookName,
		array $parameterTypes,
		string $returnType,
		bool $isVirtual,
	): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$classReflection = $reflectionProvider->getClass($className);
		$propertyReflection = $classReflection->getNativeProperty($propertyName);
		$this->assertSame($isVirtual, $propertyReflection->isVirtual()->yes());

		$hookReflection = $propertyReflection->getHook($hookName);
		$hookVariant = $hookReflection->getOnlyVariant();
		$this->assertSame($returnType, $hookVariant->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertCount(count($parameterTypes), $hookVariant->getParameters());

		foreach ($hookVariant->getParameters() as $i => $parameter) {
			$this->assertSame($parameterTypes[$i], $parameter->getType()->describe(VerbosityLevel::precise()));
		}
	}

	public static function dataPropertyHooksFromType(): iterable
	{
		$specificFooGenerics = new GenericObjectType('PropertyHooksTypes\\FooGenerics', [new IntegerType()]);

		yield [
			$specificFooGenerics,
			'm',
			'set',
			['array<int>'],
			'void',
			true,
		];

		yield [
			$specificFooGenerics,
			'n',
			'set',
			['array<int>|int'],
			'void',
			true,
		];

		$specificFooGenericsConstructor = new GenericObjectType('PropertyHooksTypes\\FooGenericsConstructor', [new IntegerType()]);

		yield [
			$specificFooGenericsConstructor,
			'n',
			'set',
			['array<int>|int'],
			'void',
			true,
		];

		yield [
			$specificFooGenericsConstructor,
			'm',
			'set',
			['array<int>'],
			'void',
			true,
		];

		yield [
			$specificFooGenericsConstructor,
			'm',
			'get',
			[],
			'array<int>',
			true,
		];
	}

	/**
	 * @param ExtendedPropertyReflection::HOOK_* $hookName
	 * @param string[] $parameterTypes
	 */
	#[DataProvider('dataPropertyHooksFromType')]
	public function testPropertyHooksFromType(
		GenericObjectType $type,
		string $propertyName,
		string $hookName,
		array $parameterTypes,
		string $returnType,
		bool $isVirtual,
	): void
	{
		$classReflection = $type->getClassReflection();
		$this->assertNotNull($classReflection);
		$propertyReflection = $classReflection->getNativeProperty($propertyName);
		$this->assertSame($isVirtual, $propertyReflection->isVirtual()->yes());

		$hookReflection = $propertyReflection->getHook($hookName);
		$hookVariant = $hookReflection->getOnlyVariant();
		$this->assertSame($returnType, $hookVariant->getReturnType()->describe(VerbosityLevel::precise()));
		$this->assertCount(count($parameterTypes), $hookVariant->getParameters());

		foreach ($hookVariant->getParameters() as $i => $parameter) {
			$this->assertSame($parameterTypes[$i], $parameter->getType()->describe(VerbosityLevel::precise()));
		}
	}

}
