<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;

interface ParameterOutTypeExtensionProvider
{

	/** @return FunctionParameterOutTypeExtension[] */
	public function getDynamicFunctionParameterOutTypeExtensions(): array;

	/** @return MethodParameterOutTypeExtension[] */
	public function getDynamicMethodParameterOutTypeExtensions(): array;

	/** @return StaticMethodParameterOutTypeExtension[] */
	public function getDynamicStaticMethodParameterOutTypeExtensions(): array;

}
