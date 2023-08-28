<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\LNumber;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function is_float;
use function is_int;

final class ArraySumFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_sum';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$arrayType = $scope->getType($functionCall->getArgs()[0]->value);
		$itemType = $arrayType->getIterableValueType();

		if ($arrayType->isIterableAtLeastOnce()->no()) {
			return new ConstantIntegerType(0);
		}

		$constantArray = $arrayType->getConstantArrays()[0] ?? null;

		if ($constantArray !== null) {
			$node = new LNumber(0);

			foreach ($constantArray->getValueTypes() as $type) {
				$node = new Plus($node, new TypeExpr($type));
			}

			$newItemType = $scope->getType($node);
		} else {
			$newItemType = new NeverType();

			if ($itemType instanceof UnionType) {
				$types = $itemType->getTypes();
			} else {
				$types = [$itemType];
			}

			$positive = false;
			$negative = false;

			foreach ($types as $type) {
				$constant = $type->getConstantScalarValues()[0] ?? null;
				if ($constant !== null) {
					if (is_float($constant)) {
						$nextType = new FloatType();
					} elseif (is_int($constant)) {
						if ($constant > 0) {
							$nextType = IntegerRangeType::fromInterval($negative ? 0 : 1, null);
							$positive = true;
						} elseif ($constant < 0) {
							$nextType = IntegerRangeType::fromInterval(null, $positive ? 0 : -1);
							$negative = true;
						} else {
							$nextType = new ConstantIntegerType(0);
						}
					} else {
						$nextType = $type;
					}
				} else {
					$nextType = $type;
				}

				$newItemType = TypeCombinator::union($newItemType, $nextType);
			}

			$nonEmptyArrayType = new NonEmptyArrayType();

			if (!$nonEmptyArrayType->isSuperTypeOf($arrayType)->yes()) {
				$newItemType = TypeCombinator::union($newItemType, new ConstantIntegerType(0));
			}
		}

		$intUnionFloat = new UnionType([new IntegerType(), new FloatType()]);

		if ($intUnionFloat->isSuperTypeOf($newItemType)->yes()) {
			return $newItemType;
		}
		return $intUnionFloat;
	}

}
