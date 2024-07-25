<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

final class DirectAlwaysUsedMethodExtensionProvider implements AlwaysUsedMethodExtensionProvider
{

	/**
	 * @param AlwaysUsedMethodExtension[] $extensions
	 */
	public function __construct(private array $extensions)
	{
	}

	public function getExtensions(): array
	{
		return $this->extensions;
	}

}
