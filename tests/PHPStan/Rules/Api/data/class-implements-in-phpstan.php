<?php

namespace PHPStan\ClassImplements;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;

class Foo implements ReflectionProviderProvider
{
	public function getReflectionProvider(): \PHPStan\Reflection\ReflectionProvider
	{
		// TODO: Implement getReflectionProvider() method.
	}

	public function getDynamicMethodThrowTypeExtensions(): array
	{
		// TODO: Implement getDynamicMethodThrowTypeExtensions() method.
	}

	public function getDynamicStaticMethodThrowTypeExtensions(): array
	{
		// TODO: Implement getDynamicStaticMethodThrowTypeExtensions() method.
	}

}

class Bar implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		// TODO: Implement isFunctionSupported() method.
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?\PHPStan\Type\Type
	{
		// TODO: Implement getThrowTypeFromFunctionCall() method.
	}

}
