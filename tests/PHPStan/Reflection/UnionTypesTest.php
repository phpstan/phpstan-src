<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use NativeUnionTypes\Foo;
use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

class UnionTypesTest extends PHPStanTestCase
{

	public function testUnionTypes(): void
	{
		require_once __DIR__ . '/../../../stubs/runtime/ReflectionUnionType.php';

		$reflectionProvider = self::createReflectionProvider();
		$class = $reflectionProvider->getClass(Foo::class);
		$propertyType = $class->getNativeProperty('fooProp')->getNativeType();
		self::assertInstanceOf(UnionType::class, $propertyType);
		self::assertSame('bool|int', $propertyType->describe(VerbosityLevel::precise()));

		$method = $class->getNativeMethod('doFoo');
		$methodVariant = $method->getOnlyVariant();
		$methodReturnType = $methodVariant->getReturnType();
		self::assertInstanceOf(UnionType::class, $methodReturnType);
		self::assertSame('NativeUnionTypes\\Bar|NativeUnionTypes\\Foo', $methodReturnType->describe(VerbosityLevel::precise()));

		$methodParameterType = $methodVariant->getParameters()[0]->getType();
		self::assertInstanceOf(UnionType::class, $methodParameterType);
		self::assertSame('bool|int', $methodParameterType->describe(VerbosityLevel::precise()));

		$function = $reflectionProvider->getFunction(new Name('NativeUnionTypes\doFoo'), null);
		$functionVariant = $function->getOnlyVariant();
		$functionReturnType = $functionVariant->getReturnType();
		self::assertInstanceOf(UnionType::class, $functionReturnType);
		self::assertSame('NativeUnionTypes\\Bar|NativeUnionTypes\\Foo', $functionReturnType->describe(VerbosityLevel::precise()));

		$functionParameterType = $functionVariant->getParameters()[0]->getType();
		self::assertInstanceOf(UnionType::class, $functionParameterType);
		self::assertSame('bool|int', $functionParameterType->describe(VerbosityLevel::precise()));
	}

}
