<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;

interface OperatorTypeSpecifyingExtensionRegistryProvider
{

	public function getRegistry(): OperatorTypeSpecifyingExtensionRegistry;

}
