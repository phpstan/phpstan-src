<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use function array_filter;
use function array_values;

final class OperatorTypeSpecifyingExtensionRegistry
{

	/**
	 * @param OperatorTypeSpecifyingExtension[] $extensions
	 */
	public function __construct(
		?Broker $broker,
		private array $extensions,
	)
	{
		if ($broker === null) {
			return;
		}

		foreach ($extensions as $extension) {
			if (!$extension instanceof BrokerAwareExtension) {
				continue;
			}

			$extension->setBroker($broker);
		}
	}

	/**
	 * @return OperatorTypeSpecifyingExtension[]
	 */
	public function getOperatorTypeSpecifyingExtensions(string $operator, Type $leftType, Type $rightType): array
	{
		return array_values(array_filter($this->extensions, static fn (OperatorTypeSpecifyingExtension $extension): bool => $extension->isOperatorSupported($operator, $leftType, $rightType)));
	}

}
