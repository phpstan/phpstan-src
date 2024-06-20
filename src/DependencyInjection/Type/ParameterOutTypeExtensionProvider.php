<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;

interface ParameterOutTypeExtensionProvider
{

	/** @return FunctionParameterOutTypeExtension[] */
	public function getFunctionParameterOutTypeExtensions(): array;

	/** @return MethodParameterOutTypeExtension[] */
	public function getMethodParameterOutTypeExtensions(): array;

	/** @return StaticMethodParameterOutTypeExtension[] */
	public function getStaticMethodParameterOutTypeExtensions(): array;

}
