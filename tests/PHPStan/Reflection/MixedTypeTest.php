<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use NativeMixedType\Foo;
use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\MixedType;
use const PHP_VERSION_ID;

class MixedTypeTest extends PHPStanTestCase
{

	public function testMixedType(): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$reflectionProvider = self::createReflectionProvider();
		$class = $reflectionProvider->getClass(Foo::class);
		$propertyType = $class->getNativeProperty('fooProp')->getNativeType();
		self::assertInstanceOf(MixedType::class, $propertyType);
		self::assertTrue($propertyType->isExplicitMixed());

		$method = $class->getNativeMethod('doFoo');
		$methodVariant = $method->getOnlyVariant();
		$methodReturnType = $methodVariant->getReturnType();
		self::assertInstanceOf(MixedType::class, $methodReturnType);
		self::assertTrue($methodReturnType->isExplicitMixed());

		$methodParameterType = $methodVariant->getParameters()[0]->getType();
		self::assertInstanceOf(MixedType::class, $methodParameterType);
		self::assertTrue($methodParameterType->isExplicitMixed());

		$function = $reflectionProvider->getFunction(new Name('NativeMixedType\doFoo'), null);
		$functionVariant = $function->getOnlyVariant();
		$functionReturnType = $functionVariant->getReturnType();
		self::assertInstanceOf(MixedType::class, $functionReturnType);
		self::assertTrue($functionReturnType->isExplicitMixed());

		$functionParameterType = $functionVariant->getParameters()[0]->getType();
		self::assertInstanceOf(MixedType::class, $functionParameterType);
		self::assertTrue($functionParameterType->isExplicitMixed());
	}

}
