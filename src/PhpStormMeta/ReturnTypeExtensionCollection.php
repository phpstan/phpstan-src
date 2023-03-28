<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta;

use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;

final class ReturnTypeExtensionCollection
{

	/** @var list<DynamicMethodReturnTypeExtension> */
	public array $nonStaticMethodExtensions = [];

	/** @var list<DynamicStaticMethodReturnTypeExtension> */
	public array $staticMethodExtensions = [];

	/** @var list<DynamicFunctionReturnTypeExtension> */
	public array $functionExtensions = [];

}
