<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ConstantType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;

class MinMaxFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @var string[] */
	private array $functionNames = [
		'min' => '',
		'max' => '',
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return isset($this->functionNames[$functionReflection->getName()]);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		if (count($functionCall->getArgs()) === 1) {
			$argType = $scope->getType($functionCall->getArgs()[0]->value);
			if ($argType->isArray()->yes()) {
				$isIterable = $argType->isIterableAtLeastOnce();
				if ($isIterable->no()) {
					return new ConstantBooleanType(false);
				}
				$iterableValueType = $argType->getIterableValueType();
				$argumentTypes = [];
				if (!$isIterable->yes()) {
					$argumentTypes[] = new ConstantBooleanType(false);
				}
				if ($iterableValueType instanceof UnionType) {
					foreach ($iterableValueType->getTypes() as $innerType) {
						$argumentTypes[] = $innerType;
					}
				} else {
					$argumentTypes[] = $iterableValueType;
				}

				return $this->processType(
					$functionReflection->getName(),
					$argumentTypes,
				);
			}

			return new ErrorType();
		}

		// rewrite min($x, $y) as $x < $y ? $x : $y
		// we don't handle arrays, which have different semantics
		$functionName = $functionReflection->getName();
		$args = $functionCall->getArgs();
		if (count($functionCall->getArgs()) === 2) {
			$argType0 = $scope->getType($args[0]->value);
			$argType1 = $scope->getType($args[1]->value);

			if ($argType0->isArray()->no() && $argType1->isArray()->no()) {
				if ($functionName === 'min') {
					return $scope->getType(new Ternary(
						new Smaller($args[0]->value, $args[1]->value),
						$args[0]->value,
						$args[1]->value,
					));
				} elseif ($functionName === 'max') {
					return $scope->getType(new Ternary(
						new Smaller($args[0]->value, $args[1]->value),
						$args[1]->value,
						$args[0]->value,
					));
				}
			}
		}

		$argumentTypes = [];
		foreach ($functionCall->getArgs() as $arg) {
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

	/**
	 * @param Type[] $types
	 */
	private function processType(
		string $functionName,
		array $types,
	): Type
	{
		$resultType = null;
		foreach ($types as $type) {
			if (!$type instanceof ConstantType) {
				return TypeCombinator::union(...$types);
			}

			if ($resultType === null) {
				$resultType = $type;
				continue;
			}

			$compareResult = $this->compareTypes($resultType, $type);
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
			$firstType->isConstantArray()->yes()
			&& $secondType instanceof ConstantScalarType
		) {
			return $secondType;
		}

		if (
			$firstType instanceof ConstantScalarType
			&& $secondType->isConstantArray()->yes()
		) {
			return $firstType;
		}

		if (
			$firstType instanceof ConstantArrayType
			&& $secondType instanceof ConstantArrayType
		) {
			if ($secondType->count() < $firstType->count()) {
				return $secondType;
			} elseif ($firstType->count() < $secondType->count()) {
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

}
