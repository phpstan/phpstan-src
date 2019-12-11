<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Broker\Broker;
use PHPStan\Cache\Cache;
use PHPStan\Parser\FunctionCallStatementFinder;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Generic\TemplateTypeMap;

class PhpMethodReflectionTest extends \PHPStan\Testing\TestCase
{

	public function testGetVariantsWithGoodParameter(): void
	{
		$classReflection = $this->createMock(ClassReflection::class);
		$classReflection->method('getName')->willReturn('MyClass');
		$classReflection->method('getFileName')->willReturn(false);

		$niceParameter = $this->createMock(\ReflectionParameter::class);
		$niceParameter->method('getName')->willReturn('niceParam');

		$nativeMethodReflection = $this->createMock(BuiltinMethodReflection::class);
		$nativeMethodReflection->method('getName')->willReturn('someMethod');
		$nativeMethodReflection->method('getParameters')->willReturn([$niceParameter]);

		$methodReflection = $this->createPhpMethodReflection($classReflection, $nativeMethodReflection);

		$this->assertNotEmpty($methodReflection->getVariants());
	}

	public function testGetVariantsWithBadParameter(): void
	{
		$classReflection = $this->createMock(ClassReflection::class);
		$classReflection->method('getName')->willReturn('MyClass');
		$classReflection->method('getFileName')->willReturn('/path/to/some/file');

		$badParameter = $this->createMock(\ReflectionParameter::class);
		$badParameter->method('getName')->willReturn(null);

		$nativeMethodReflection = $this->createMock(BuiltinMethodReflection::class);
		$nativeMethodReflection->method('getName')->willReturn('someMethod');
		$nativeMethodReflection->method('getParameters')->willReturn([$badParameter]);

		$methodReflection = $this->createPhpMethodReflection($classReflection, $nativeMethodReflection);

		try {
			$methodReflection->getVariants();
			$this->fail('Bad parameter should trigger an exception.');
		} catch (\PHPStan\Reflection\BadParameterFromReflectionException $exception) {
			$this->assertContains(' MyClass ', $exception->getMessage());
			$this->assertContains(' someMethod() ', $exception->getMessage());
			$this->assertContains('/path/to/some/file', $exception->getMessage());
		}
	}

	private function createPhpMethodReflection(
		ClassReflection $classReflection,
		BuiltinMethodReflection $nativeMethodReflection
	): PhpMethodReflection
	{
		return new PhpMethodReflection(
			$classReflection,
			null,
			$nativeMethodReflection,
			$this->createMock(Broker::class),
			$this->createMock(Parser::class),
			$this->createMock(FunctionCallStatementFinder::class),
			$this->createMock(Cache::class),
			$this->createMock(TemplateTypeMap::class),
			[],
			null,
			null,
			null,
			false,
			false,
			false,
			null
		);
	}

}
