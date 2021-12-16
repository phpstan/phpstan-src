<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

class DirectAlwaysUsedClassConstantsExtensionProvider implements AlwaysUsedClassConstantsExtensionProvider
{

	/** @var AlwaysUsedClassConstantsExtension[] */
	private array $extensions;

	/**
	 * @param AlwaysUsedClassConstantsExtension[] $extensions
	 */
	public function __construct(array $extensions)
	{
		$this->extensions = $extensions;
	}

	/**
	 * @return AlwaysUsedClassConstantsExtension[]
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

}
