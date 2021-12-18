<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use function array_filter;
use function array_values;

class OperatorTypeSpecifyingExtensionRegistry
{

	/** @var OperatorTypeSpecifyingExtension[] */
	private array $extensions;

	/**
	 * @param OperatorTypeSpecifyingExtension[] $extensions
	 */
	public function __construct(
		Broker $broker,
		array $extensions,
	)
	{
		foreach ($extensions as $extension) {
			if (!$extension instanceof BrokerAwareExtension) {
				continue;
			}

			$extension->setBroker($broker);
		}
		$this->extensions = $extensions;
	}

	/**
	 * @return OperatorTypeSpecifyingExtension[]
	 */
	public function getOperatorTypeSpecifyingExtensions(string $operator, Type $leftType, Type $rightType): array
	{
		return array_values(array_filter($this->extensions, static fn (OperatorTypeSpecifyingExtension $extension): bool => $extension->isOperatorSupported($operator, $leftType, $rightType)));
	}

}
