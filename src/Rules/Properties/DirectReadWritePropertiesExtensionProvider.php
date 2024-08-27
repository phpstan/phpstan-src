<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

final class DirectReadWritePropertiesExtensionProvider implements ReadWritePropertiesExtensionProvider
{

	/**
	 * @param ReadWritePropertiesExtension[] $extensions
	 */
	public function __construct(private array $extensions)
	{
	}

	/**
	 * @return ReadWritePropertiesExtension[]
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

}
