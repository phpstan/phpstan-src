<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

class DirectAlwaysUsedClassConstantsExtensionProvider implements AlwaysUsedClassConstantsExtensionProvider
{

	/**
	 * @param AlwaysUsedClassConstantsExtension[] $extensions
	 */
	public function __construct(private array $extensions)
	{
	}

	/**
	 * @return AlwaysUsedClassConstantsExtension[]
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

}
