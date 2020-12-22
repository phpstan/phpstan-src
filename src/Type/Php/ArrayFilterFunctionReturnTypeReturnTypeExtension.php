<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

class ArrayFilterFunctionReturnTypeReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_filter';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$arrayArg = $functionCall->args[0]->value ?? null;
		$callbackArg = $functionCall->args[1]->value ?? null;
		$flagArg = $functionCall->args[2]->value ?? null;

		if ($arrayArg !== null) {
			$arrayArgType = $scope->getType($arrayArg);
			$keyType = $arrayArgType->getIterableKeyType();
			$itemType = $arrayArgType->getIterableValueType();

			if ($arrayArgType instanceof MixedType) {
				return new BenevolentUnionType([
					new ArrayType(new MixedType(), new MixedType()),
					new NullType(),
				]);
			}

			if ($callbackArg === null) {
				return TypeCombinator::union(
					...array_map([$this, 'removeFalsey'], TypeUtils::getArrays($arrayArgType))
				);
			}

			if ($flagArg === null) {
				$var = null;
				$expr = null;
				if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
					$statement = $callbackArg->stmts[0];
					if ($statement instanceof Return_ && $statement->expr !== null) {
						$var = $callbackArg->params[0]->var;
						$expr = $statement->expr;
					}
				} elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
					$var = $callbackArg->params[0]->var;
					$expr = $callbackArg->expr;
				}
				if ($var !== null && $expr !== null) {
					if (!$var instanceof Variable || !is_string($var->name)) {
						throw new \PHPStan\ShouldNotHappenException();
					}
					$itemVariableName = $var->name;
					if (!$scope instanceof MutatingScope) {
						throw new \PHPStan\ShouldNotHappenException();
					}
					$scope = $scope->assignVariable($itemVariableName, $itemType);
					$scope = $scope->filterByTruthyValue($expr);
					$itemType = $scope->getVariableType($itemVariableName);
				}
			}

		} else {
			$keyType = new MixedType();
			$itemType = new MixedType();
		}

		return new ArrayType($keyType, $itemType);
	}

	public function removeFalsey(Type $type): Type
	{
		$falseyTypes = StaticTypeFactory::falsey();

		if ($type instanceof ConstantArrayType) {
			$keys = $type->getKeyTypes();
			$values = $type->getValueTypes();

			$builder = ConstantArrayTypeBuilder::createEmpty();

			foreach ($values as $offset => $value) {
				$isFalsey = $falseyTypes->isSuperTypeOf($value);

				if ($isFalsey->maybe()) {
					$builder->setOffsetValueType($keys[$offset], TypeCombinator::remove($value, $falseyTypes), true);
				} elseif ($isFalsey->no()) {
					$builder->setOffsetValueType($keys[$offset], $value);
				}
			}

			return $builder->getArray();
		}

		$keyType = $type->getIterableKeyType();
		$valueType = $type->getIterableValueType();

		$valueType = TypeCombinator::remove($valueType, $falseyTypes);

		if ($valueType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		return new ArrayType($keyType, $valueType);
	}

}
