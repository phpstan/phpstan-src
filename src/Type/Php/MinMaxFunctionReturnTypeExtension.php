<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Scalar;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function in_array;

#[AutowiredService]
final class MinMaxFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['min', 'max'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		if (count($args) === 1) {
			$argType = $scope->getType($args[0]->value);
			if ($argType->isArray()->yes()) {
				return $this->processArrayType(
					$functionReflection->getName(),
					$argType,
					$scope,
				);
			}

			return new ErrorType();
		}

		// PHP evaluates min($x, $y) like $x < $y ? $x : $y or like $y < $x ? $y : $x,
		// and max($x, $y) like $x < $y ? $y : $x or like $x >= $y ? $x : $y.
		// Which one depends on the PHP version and on whether the call was compiled to a frameless call.
		// They differ when the arguments compare as equal (min) or cannot be ordered (NAN),
		// so the result is the union of both rewrites.
		// we don't handle arrays, which have different semantics
		$functionName = $functionReflection->getName();
		if (count($args) === 2) {
			$argType0 = $scope->getType($args[0]->value);
			$argType1 = $scope->getType($args[1]->value);

			if ($argType0->isArray()->no() && $argType1->isArray()->no()) {
				// a literal in the comparison must stay a literal: narrowing skips those,
				// and a comparison with NAN would otherwise turn the literal into never
				$left = $this->rememberType($args[0]->value, $argType0, $scope);
				$right = $this->rememberType($args[1]->value, $argType1, $scope);

				if ($functionName === 'min') {
					$rewrites = [
						new Ternary(new Smaller($left, $right), $args[0]->value, $args[1]->value),
						new Ternary(new Smaller($right, $left), $args[1]->value, $args[0]->value),
					];
				} else {
					$rewrites = [
						new Ternary(new Smaller($left, $right), $args[1]->value, $args[0]->value),
						new Ternary(new GreaterOrEqual($left, $right), $args[0]->value, $args[1]->value),
					];
				}

				$type = $scope->getType($rewrites[0]);
				if ($argType0->isInteger()->yes() && $argType1->isInteger()->yes()) {
					// integers are totally ordered and equal integers are the same value
					return $type;
				}

				return TypeCombinator::union($type, $scope->getType($rewrites[1]));
			}
		}

		$argumentTypes = [];
		foreach ($args as $arg) {
			$argType = $scope->getType($arg->value);
			if ($arg->unpack) {
				$iterableValueType = $argType->getIterableValueType();
				if ($iterableValueType instanceof UnionType) {
					foreach ($iterableValueType->getTypes() as $innerType) {
						$argumentTypes[] = $innerType;
					}
				} else {
					$argumentTypes[] = $iterableValueType;
				}
				continue;
			}

			$argumentTypes[] = $argType;
		}

		return $this->processType(
			$functionName,
			$argumentTypes,
		);
	}

	private function processArrayType(string $functionName, Type $argType, Scope $scope): Type
	{
		$throwsValueError = $scope->getPhpVersion()->throwsValueErrorForInternalFunctions();
		$constArrayTypes = $argType->getConstantArrays();
		if (count($constArrayTypes) > 0) {
			$resultTypes = [];
			foreach ($constArrayTypes as $constArrayType) {
				$isIterable = $constArrayType->isIterableAtLeastOnce();
				if ($isIterable->no() && !$throwsValueError->yes()) {
					$resultTypes[] = new ConstantBooleanType(false);
					continue;
				}
				$argumentTypes = [];
				if (!$isIterable->yes() && !$throwsValueError->yes()) {
					$argumentTypes[] = new ConstantBooleanType(false);
				}

				foreach ($constArrayType->getValueTypes() as $innerType) {
					$argumentTypes[] = $innerType;
				}

				$unsealedTypes = $constArrayType->getUnsealedTypes();
				if ($unsealedTypes !== null && $constArrayType->isUnsealed()->yes()) {
					// Unsealed extras can hold further values, so the min/max
					// must also range over the unsealed value type — otherwise
					// the explicit entries would be reported as the answer.
					$argumentTypes[] = $unsealedTypes[1];
				}

				$resultTypes[] = $this->processType($functionName, $argumentTypes);
			}

			return TypeCombinator::union(...$resultTypes);
		}

		$isIterable = $argType->isIterableAtLeastOnce();
		if ($isIterable->no() && !$throwsValueError->yes()) {
			return new ConstantBooleanType(false);
		}
		$iterableValueType = $argType->getIterableValueType();
		$argumentTypes = [];
		if (!$isIterable->yes() && !$throwsValueError->yes()) {
			$argumentTypes[] = new ConstantBooleanType(false);
		}

		$argumentTypes[] = $iterableValueType;

		return $this->processType($functionName, $argumentTypes);
	}

	/**
	 * @param list<Type> $types
	 */
	private function processType(
		string $functionName,
		array $types,
	): Type
	{
		$resultType = null;
		foreach ($types as $type) {
			if ($resultType === null) {
				$resultType = $type;
				continue;
			}

			$compareResult = $this->compareTypes($resultType, $type);
			if ($compareResult === null) {
				return TypeCombinator::union(...$types);
			}

			if ($functionName === 'min') {
				if ($compareResult === $type) {
					$resultType = $type;
				}
			} elseif ($functionName === 'max') {
				if ($compareResult === $resultType) {
					$resultType = $type;
				}
			}
		}

		if ($resultType === null) {
			return new ErrorType();
		}

		return $resultType;
	}

	private function compareTypes(
		Type $firstType,
		Type $secondType,
	): ?Type
	{
		if (
			$firstType->isArray()->yes()
			&& $secondType->isConstantScalarValue()->yes()
		) {
			return $secondType;
		}

		if (
			$firstType->isConstantScalarValue()->yes()
			&& $secondType->isArray()->yes()
		) {
			return $firstType;
		}

		if (
			$firstType instanceof ConstantArrayType
			&& $secondType instanceof ConstantArrayType
		) {
			if ($secondType->getArraySize() < $firstType->getArraySize()) {
				return $secondType;
			} elseif ($firstType->getArraySize() < $secondType->getArraySize()) {
				return $firstType;
			}

			foreach ($firstType->getValueTypes() as $i => $firstValueType) {
				$secondValueType = $secondType->getValueTypes()[$i];
				$compareResult = $this->compareTypes($firstValueType, $secondValueType);
				if ($compareResult === $firstValueType) {
					return $firstType;
				}

				if ($compareResult === $secondValueType) {
					return $secondType;
				}
			}

			return null;
		}

		if (
			$firstType instanceof ConstantScalarType
			&& $secondType instanceof ConstantScalarType
		) {
			if ($secondType->getValue() < $firstType->getValue()) {
				return $secondType;
			}

			if ($firstType->getValue() < $secondType->getValue()) {
				return $firstType;
			}
		}

		return null;
	}

	private function rememberType(Expr $expr, Type $type, Scope $scope): Expr
	{
		if ($expr instanceof Scalar || ($expr instanceof UnaryMinus && $expr->expr instanceof Scalar)) {
			return $expr;
		}

		return new AlwaysRememberedExpr($expr, $type, $scope->getNativeType($expr));
	}

}
