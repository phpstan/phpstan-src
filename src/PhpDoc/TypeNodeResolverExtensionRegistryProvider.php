<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

interface TypeNodeResolverExtensionRegistryProvider
{

	public function getRegistry(): TypeNodeResolverExtensionRegistry;

}
