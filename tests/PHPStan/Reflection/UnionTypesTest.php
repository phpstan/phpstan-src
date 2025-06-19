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
		$this->assertInstanceOf(UnionType::class, $propertyType);
		$this->assertSame('bool|int', $propertyType->describe(VerbosityLevel::precise()));

		$method = $class->getNativeMethod('doFoo');
		$methodVariant = $method->getOnlyVariant();
		$methodReturnType = $methodVariant->getReturnType();
		$this->assertInstanceOf(UnionType::class, $methodReturnType);
		$this->assertSame('NativeUnionTypes\\Bar|NativeUnionTypes\\Foo', $methodReturnType->describe(VerbosityLevel::precise()));

		$methodParameterType = $methodVariant->getParameters()[0]->getType();
		$this->assertInstanceOf(UnionType::class, $methodParameterType);
		$this->assertSame('bool|int', $methodParameterType->describe(VerbosityLevel::precise()));

		$function = $reflectionProvider->getFunction(new Name('NativeUnionTypes\doFoo'), null);
		$functionVariant = $function->getOnlyVariant();
		$functionReturnType = $functionVariant->getReturnType();
		$this->assertInstanceOf(UnionType::class, $functionReturnType);
		$this->assertSame('NativeUnionTypes\\Bar|NativeUnionTypes\\Foo', $functionReturnType->describe(VerbosityLevel::precise()));

		$functionParameterType = $functionVariant->getParameters()[0]->getType();
		$this->assertInstanceOf(UnionType::class, $functionParameterType);
		$this->assertSame('bool|int', $functionParameterType->describe(VerbosityLevel::precise()));
	}

}
