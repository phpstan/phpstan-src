<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\BinaryOp\Mul;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\Int_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

final class ArraySumFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_sum';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return null;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$resultTypes = [];

		if (count($argType->getConstantArrays()) > 0) {
			foreach ($argType->getConstantArrays() as $constantArray) {
				$node = new Int_(0);

				foreach ($constantArray->getValueTypes() as $i => $type) {
					if ($constantArray->isOptionalKey($i)) {
						$node = new Plus($node, new TypeExpr(TypeCombinator::union($type, new ConstantIntegerType(0))));
					} else {
						$node = new Plus($node, new TypeExpr($type));
					}
				}

				$resultTypes[] = $scope->getType($node);
			}
		} else {
			$itemType = $argType->getIterableValueType();

			$mulNode = new Mul(new TypeExpr($itemType), new TypeExpr(IntegerRangeType::fromInterval(0, null)));

			$resultTypes[] = $scope->getType(new Plus(new TypeExpr($itemType), $mulNode));
		}

		if (!$argType->isIterableAtLeastOnce()->yes()) {
			$resultTypes[] = new ConstantIntegerType(0);
		}

		return TypeCombinator::union(...$resultTypes)->toNumber();
	}

}
