<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\LNumber;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
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

		$argType = $scope->getType($functionCall->getArgs()[0]->value);

		if ($argType instanceof UnionType) {
			$arrayTypes = $argType->getTypes();
		} else {
			$arrayTypes = [$argType];
		}

		$resultTypes = [];

		foreach ($arrayTypes as $arrayType) {
			if (!$arrayType->isArray()->yes()) {
				return new ErrorType();
			}

			if ($arrayType->isIterableAtLeastOnce()->no()) {
				$resultTypes[] = new ConstantIntegerType(0);
				continue;
			}

			$constantArray = $arrayType->getConstantArrays()[0] ?? null;

			if ($constantArray !== null) {
				$node = new LNumber(0);

				foreach ($constantArray->getValueTypes() as $type) {
					$node = new Plus($node, new TypeExpr($type));
				}

				$newItemType = $scope->getType($node);
			} else {
				$itemType = $arrayType->getIterableValueType();

				$newTypes = [];

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
								$nextType = IntegerRangeType::fromInterval(1, null);
								$positive = true;
							} elseif ($constant < 0) {
								$nextType = IntegerRangeType::fromInterval(null, -1);
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

					$newTypes[] = $nextType;
				}

				if (($positive && $negative) || !$arrayType->isIterableAtLeastOnce()->yes()) {
					$newTypes[] = new ConstantIntegerType(0);
				}

				$newItemType = TypeCombinator::union(...$newTypes);
			}

			$resultTypes[] = $newItemType;
		}

		$resultType = TypeCombinator::union(...$resultTypes);

		$intUnionFloat = new UnionType([new IntegerType(), new FloatType()]);

		if ($intUnionFloat->isSuperTypeOf($resultType)->yes()) {
			return $resultType;
		}
		return $intUnionFloat;
	}

}
