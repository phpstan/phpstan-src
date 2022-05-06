<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

interface TypeNodeResolverExtensionRegistry
{

	/**
	 * @return TypeNodeResolverExtension[]
	 */
	public function getExtensions(): array;

}
