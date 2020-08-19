<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use NativeStaticReturnType\Foo;
use PHPStan\Testing\TestCase;
use PHPStan\Type\StaticType;

class StaticTypeTest extends TestCase
{

	public function testMixedType(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$reflectionProvider = $this->createBroker();
		$class = $reflectionProvider->getClass(Foo::class);

		$method = $class->getNativeMethod('doFoo');
		$methodVariant = ParametersAcceptorSelector::selectSingle($method->getVariants());
		$methodReturnType = $methodVariant->getReturnType();
		$this->assertInstanceOf(StaticType::class, $methodReturnType);
		$this->assertSame(Foo::class, $methodReturnType->getClassName());
	}

}
