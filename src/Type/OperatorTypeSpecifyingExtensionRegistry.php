<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;

class OperatorTypeSpecifyingExtensionRegistry
{

	/** @var OperatorTypeSpecifyingExtension[] */
	private array $extensions;

	/**
	 * @param \PHPStan\Type\OperatorTypeSpecifyingExtension[] $extensions
	 */
	public function __construct(
		Broker $broker,
		array $extensions
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
		return array_values(array_filter($this->extensions, static function (OperatorTypeSpecifyingExtension $extension) use ($operator, $leftType, $rightType): bool {
			return $extension->isOperatorSupported($operator, $leftType, $rightType);
		}));
	}

}
