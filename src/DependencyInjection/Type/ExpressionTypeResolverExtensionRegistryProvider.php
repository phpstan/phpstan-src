<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Type\ExpressionTypeResolverExtensionRegistry;

interface ExpressionTypeResolverExtensionRegistryProvider
{

	public function getRegistry(): ExpressionTypeResolverExtensionRegistry;

}
