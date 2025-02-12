<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\FunctionDynamicParameterTypeExtension;
use PHPStan\Type\MethodDynamicParameterTypeExtension;
use PHPStan\Type\StaticMethodDynamicParameterTypeExtension;

interface DynamicParameterTypeExtensionProvider
{

	/** @return FunctionDynamicParameterTypeExtension[] */
	public function getFunctionDynamicParameterTypeExtensions(): array;

	/** @return MethodDynamicParameterTypeExtension[] */
	public function getMethodDynamicParameterTypeExtensions(): array;

	/** @return StaticMethodDynamicParameterTypeExtension[] */
	public function getStaticMethodDynamicParameterTypeExtensions(): array;

}
