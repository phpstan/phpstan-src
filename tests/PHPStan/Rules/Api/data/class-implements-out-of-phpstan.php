<?php

namespace App\ClassImplements;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;

class Foo implements DynamicThrowTypeExtensionProvider
{
	public function getDynamicFunctionThrowTypeExtensions(): array
	{
		// TODO: Implement getDynamicFunctionThrowTypeExtensions() method.
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
