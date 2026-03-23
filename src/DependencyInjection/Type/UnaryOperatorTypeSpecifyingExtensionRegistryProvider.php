<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\UnaryOperatorTypeSpecifyingExtensionRegistry;

interface UnaryOperatorTypeSpecifyingExtensionRegistryProvider
{

	public function getRegistry(): UnaryOperatorTypeSpecifyingExtensionRegistry;

}
