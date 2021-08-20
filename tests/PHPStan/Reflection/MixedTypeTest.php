<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use NativeMixedType\Foo;
use PhpParser\Node\Name;
use PHPStan\Testing\BaseTestCase;
use PHPStan\Type\MixedType;

class MixedTypeTest extends BaseTestCase
{

	public function testMixedType(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$reflectionProvider = $this->createBroker();
		$class = $reflectionProvider->getClass(Foo::class);
		$propertyType = $class->getNativeProperty('fooProp')->getNativeType();
		$this->assertInstanceOf(MixedType::class, $propertyType);
		$this->assertTrue($propertyType->isExplicitMixed());

		$method = $class->getNativeMethod('doFoo');
		$methodVariant = ParametersAcceptorSelector::selectSingle($method->getVariants());
		$methodReturnType = $methodVariant->getReturnType();
		$this->assertInstanceOf(MixedType::class, $methodReturnType);
		$this->assertTrue($methodReturnType->isExplicitMixed());

		$methodParameterType = $methodVariant->getParameters()[0]->getType();
		$this->assertInstanceOf(MixedType::class, $methodParameterType);
		$this->assertTrue($methodParameterType->isExplicitMixed());

		$function = $reflectionProvider->getFunction(new Name('NativeMixedType\doFoo'), null);
		$functionVariant = ParametersAcceptorSelector::selectSingle($function->getVariants());
		$functionReturnType = $functionVariant->getReturnType();
		$this->assertInstanceOf(MixedType::class, $functionReturnType);
		$this->assertTrue($functionReturnType->isExplicitMixed());

		$functionParameterType = $functionVariant->getParameters()[0]->getType();
		$this->assertInstanceOf(MixedType::class, $functionParameterType);
		$this->assertTrue($functionParameterType->isExplicitMixed());
	}

}
