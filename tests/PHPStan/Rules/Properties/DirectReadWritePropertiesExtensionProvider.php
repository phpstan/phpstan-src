<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

class DirectReadWritePropertiesExtensionProvider implements ReadWritePropertiesExtensionProvider
{

	/** @var ReadWritePropertiesExtension[] */
	private array $extensions;

	/**
	 * @param ReadWritePropertiesExtension[] $extensions
	 */
	public function __construct(array $extensions)
	{
		$this->extensions = $extensions;
	}

	/**
	 * @return ReadWritePropertiesExtension[]
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

}
