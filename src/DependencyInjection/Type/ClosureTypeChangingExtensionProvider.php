<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\FunctionClosureTypeChangingExtension;
use PHPStan\Type\MethodClosureTypeChangingExtension;
use PHPStan\Type\StaticMethodClosureTypeChangingExtension;

interface ClosureTypeChangingExtensionProvider
{

	/** @return FunctionClosureTypeChangingExtension[] */
	public function getFunctionClosureTypeChangingExtensions(): array;

	/** @return MethodClosureTypeChangingExtension[] */
	public function getMethodClosureTypeChangingExtensions(): array;

	/** @return StaticMethodClosureTypeChangingExtension[] */
	public function getStaticMethodClosureTypeChangingExtensions(): array;

}
