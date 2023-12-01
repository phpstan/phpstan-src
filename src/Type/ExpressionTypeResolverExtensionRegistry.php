<?php declare(strict_types = 1);

namespace PHPStan\Type;

class ExpressionTypeResolverExtensionRegistry
{

	/**
	 * @param array<ExpressionTypeResolverExtension> $extensions
	 */
	public function __construct(
		private array $extensions,
	)
	{
	}

	/**
	 * @return array<ExpressionTypeResolverExtension>
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

}
