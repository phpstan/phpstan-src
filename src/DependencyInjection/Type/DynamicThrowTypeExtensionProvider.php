<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;

interface DynamicThrowTypeExtensionProvider
{

	/** @return DynamicFunctionThrowTypeExtension[] */
	public function getDynamicFunctionThrowTypeExtensions(string $thisIsFine): array;

	/** @return DynamicMethodThrowTypeExtension[] */
	public function getDynamicMethodThrowTypeExtensions(): array;

	/** @return DynamicStaticMethodThrowTypeExtension[] */
	public function getDynamicStaticMethodThrowTypeExtensions(): array;

	public function thisIsFine(): void;

}
