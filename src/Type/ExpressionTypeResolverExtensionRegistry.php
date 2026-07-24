<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;

#[AutowiredService]
final class ExpressionTypeResolverExtensionRegistry
{

	/**
	 * @param ExtensionsCollection<ExpressionTypeResolverExtension> $extensions
	 */
	public function __construct(
		#[AutowiredExtensions(interface: ExpressionTypeResolverExtension::class)]
		private ExtensionsCollection $extensions,
	)
	{
	}

	/**
	 * @return list<ExpressionTypeResolverExtension>
	 */
	public function getExtensions(): array
	{
		return $this->extensions->getAll();
	}

}
