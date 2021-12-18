<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

class TypeNodeResolverExtensionRegistry
{

	/** @var TypeNodeResolverExtension[] */
	private array $extensions;

	/**
	 * @param TypeNodeResolverExtension[] $extensions
	 */
	public function __construct(
		TypeNodeResolver $typeNodeResolver,
		array $extensions,
	)
	{
		foreach ($extensions as $extension) {
			if (!$extension instanceof TypeNodeResolverAwareExtension) {
				continue;
			}

			$extension->setTypeNodeResolver($typeNodeResolver);
		}
		$this->extensions = $extensions;
	}

	/**
	 * @return TypeNodeResolverExtension[]
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

}
