<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\DynamicFunctionParameterOutTypeExtension;
use PHPStan\Type\DynamicMethodParameterOutTypeExtension;
use PHPStan\Type\DynamicStaticMethodParameterOutTypeExtension;

interface DynamicParameterOutTypeExtensionProvider
{

	/** @return DynamicFunctionParameterOutTypeExtension[] */
	public function getDynamicFunctionParameterOutTypeExtensions(): array;

	/** @return DynamicMethodParameterOutTypeExtension[] */
	public function getDynamicMethodParameterOutTypeExtensions(): array;

	/** @return DynamicStaticMethodParameterOutTypeExtension[] */
	public function getDynamicStaticMethodParameterOutTypeExtensions(): array;

}
