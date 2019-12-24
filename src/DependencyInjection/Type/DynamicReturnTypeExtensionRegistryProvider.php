<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\DynamicReturnTypeExtensionRegistry;

interface DynamicReturnTypeExtensionRegistryProvider
{

	public function getRegistry(): DynamicReturnTypeExtensionRegistry;

}
