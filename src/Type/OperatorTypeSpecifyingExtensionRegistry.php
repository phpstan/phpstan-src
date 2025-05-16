<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr;
use function array_filter;
use function array_values;
use function count;

final class OperatorTypeSpecifyingExtensionRegistry
{

	/**
	 * @param OperatorTypeSpecifyingExtension[] $extensions
	 */
	public function __construct(
		private array $extensions,
	)
	{
	}

	/**
	 * @return OperatorTypeSpecifyingExtension[]
	 */
	public function getOperatorTypeSpecifyingExtensions(string $operator, Type $leftType, Type $rightType): array
	{
		return array_values(array_filter($this->extensions, static fn (OperatorTypeSpecifyingExtension $extension): bool => $extension->isOperatorSupported($operator, $leftType, $rightType)));
	}

	public function callOperatorTypeSpecifyingExtensions(Expr\BinaryOp $expr, Type $leftType, Type $rightType): ?Type
	{
		$operatorSigil = $expr->getOperatorSigil();
		$operatorTypeSpecifyingExtensions = $this->getOperatorTypeSpecifyingExtensions($operatorSigil, $leftType, $rightType);

		/** @var Type[] $extensionTypes */
		$extensionTypes = [];

		foreach ($operatorTypeSpecifyingExtensions as $extension) {
			$extensionTypes[] = $extension->specifyType($operatorSigil, $leftType, $rightType);
		}

		if (count($extensionTypes) > 0) {
			return TypeCombinator::union(...$extensionTypes);
		}

		return null;
	}

}
