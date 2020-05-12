<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\Broker;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;

class DirectOperatorTypeSpecifyingExtensionRegistryProvider implements OperatorTypeSpecifyingExtensionRegistryProvider
{

	/** @var OperatorTypeSpecifyingExtension[] */
	private array $extensions;

	private Broker $broker;

	/**
	 * @param \PHPStan\Type\OperatorTypeSpecifyingExtension[] $extensions
	 */
	public function __construct(array $extensions)
	{
		$this->extensions = $extensions;
	}

	public function setBroker(Broker $broker): void
	{
		$this->broker = $broker;
	}

	public function getRegistry(): OperatorTypeSpecifyingExtensionRegistry
	{
		return new OperatorTypeSpecifyingExtensionRegistry(
			$this->broker,
			$this->extensions
		);
	}

}
