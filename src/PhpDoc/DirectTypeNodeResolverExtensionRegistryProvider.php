<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

final class DirectTypeNodeResolverExtensionRegistryProvider implements TypeNodeResolverExtensionRegistryProvider
{

	public function __construct(private TypeNodeResolverExtensionRegistry $registry)
	{
	}

	public function getRegistry(): TypeNodeResolverExtensionRegistry
	{
		return $this->registry;
	}

}
