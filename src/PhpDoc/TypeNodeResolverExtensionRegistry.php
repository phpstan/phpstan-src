<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

class TypeNodeResolverExtensionRegistry
{

	/**
	 * @param TypeNodeResolverExtension[] $extensions
	 */
	public function __construct(
		TypeNodeResolver $typeNodeResolver,
		private array $extensions,
	)
	{
		foreach ($extensions as $extension) {
			if (!$extension instanceof TypeNodeResolverAwareExtension) {
				continue;
			}

			$extension->setTypeNodeResolver($typeNodeResolver);
		}
	}

	/**
	 * @return TypeNodeResolverExtension[]
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

}
