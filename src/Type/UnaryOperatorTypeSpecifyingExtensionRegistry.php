<?php declare(strict_types = 1);

namespace PHPStan\Type;

use function array_filter;
use function array_values;
use function count;

final class UnaryOperatorTypeSpecifyingExtensionRegistry
{

	/**
	 * @param UnaryOperatorTypeSpecifyingExtension[] $extensions
	 */
	public function __construct(
		private array $extensions,
	)
	{
	}

	/**
	 * @return UnaryOperatorTypeSpecifyingExtension[]
	 */
	private function getOperatorTypeSpecifyingExtensions(string $operator, Type $operandType): array
	{
		return array_values(array_filter($this->extensions, static fn (UnaryOperatorTypeSpecifyingExtension $extension): bool => $extension->isOperatorSupported($operator, $operandType)));
	}

	public function callUnaryOperatorTypeSpecifyingExtensions(string $operatorSigil, Type $operandType): ?Type
	{
		$operatorTypeSpecifyingExtensions = $this->getOperatorTypeSpecifyingExtensions($operatorSigil, $operandType);

		/** @var list<Type> $extensionTypes */
		$extensionTypes = [];

		foreach ($operatorTypeSpecifyingExtensions as $extension) {
			$extensionTypes[] = $extension->specifyType($operatorSigil, $operandType);
		}

		if (count($extensionTypes) > 0) {
			return TypeCombinator::union(...$extensionTypes);
		}

		return null;
	}

}
