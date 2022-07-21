<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Error;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_map;
use function count;
use function is_string;
use function strtolower;

class ArrayFilterFunctionReturnTypeReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_filter';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$arrayArg = $functionCall->getArgs()[0]->value ?? null;
		$callbackArg = $functionCall->getArgs()[1]->value ?? null;
		$flagArg = $functionCall->getArgs()[2]->value ?? null;

		if ($arrayArg === null) {
			return new ArrayType(new MixedType(), new MixedType());
		}

		$arrayArgType = $scope->getType($arrayArg);
		$keyType = $arrayArgType->getIterableKeyType();
		$itemType = $arrayArgType->getIterableValueType();

		if ($arrayArgType instanceof MixedType) {
			return new BenevolentUnionType([
				new ArrayType(new MixedType(), new MixedType()),
				new NullType(),
			]);
		}

		if ($callbackArg === null || ($callbackArg instanceof ConstFetch && strtolower($callbackArg->name->parts[0]) === 'null')) {
			return TypeCombinator::union(
				...array_map([$this, 'removeFalsey'], TypeUtils::getArrays($arrayArgType)),
			);
		}

		if ($flagArg === null) {
			if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
				$statement = $callbackArg->stmts[0];
				if ($statement instanceof Return_ && $statement->expr !== null) {
					[$itemType, $keyType] = $this->filterByTruthyValue($scope, $callbackArg->params[0]->var, $itemType, null, $keyType, $statement->expr);
				}
			} elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
				[$itemType, $keyType] = $this->filterByTruthyValue($scope, $callbackArg->params[0]->var, $itemType, null, $keyType, $callbackArg->expr);
			} elseif ($callbackArg instanceof String_) {
				$itemVar = new Variable('item');
				$expr = new FuncCall(new Name($callbackArg->value), [new Arg($itemVar)]);
				[$itemType, $keyType] = $this->filterByTruthyValue($scope, $itemVar, $itemType, null, $keyType, $expr);
			}
		}

		if ($flagArg instanceof ConstFetch && $flagArg->name->parts[0] === 'ARRAY_FILTER_USE_KEY') {
			if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
				$statement = $callbackArg->stmts[0];
				if ($statement instanceof Return_ && $statement->expr !== null) {
					[$itemType, $keyType] = $this->filterByTruthyValue($scope, null, $itemType, $callbackArg->params[0]->var, $keyType, $statement->expr);
				}
			} elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
				[$itemType, $keyType] = $this->filterByTruthyValue($scope, null, $itemType, $callbackArg->params[0]->var, $keyType, $callbackArg->expr);
			} elseif ($callbackArg instanceof String_) {
				$keyVar = new Variable('key');
				$expr = new FuncCall(new Name($callbackArg->value), [new Arg($keyVar)]);
				[$itemType, $keyType] = $this->filterByTruthyValue($scope, null, $itemType, $keyVar, $keyType, $expr);
			}
		}

		if ($flagArg instanceof ConstFetch && $flagArg->name->parts[0] === 'ARRAY_FILTER_USE_BOTH') {
			if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
				$statement = $callbackArg->stmts[0];
				if ($statement instanceof Return_ && $statement->expr !== null) {
					[$itemType, $keyType] = $this->filterByTruthyValue($scope, $callbackArg->params[0]->var, $itemType, $callbackArg->params[1]->var ?? null, $keyType, $statement->expr);
				}
			} elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
				[$itemType, $keyType] = $this->filterByTruthyValue($scope, $callbackArg->params[0]->var, $itemType, $callbackArg->params[1]->var ?? null, $keyType, $callbackArg->expr);
			} elseif ($callbackArg instanceof String_) {
				$itemVar = new Variable('item');
				$keyVar = new Variable('key');
				$expr = new FuncCall(new Name($callbackArg->value), [new Arg($itemVar), new Arg($keyVar)]);
				[$itemType, $keyType] = $this->filterByTruthyValue($scope, $itemVar, $itemType, $keyVar, $keyType, $expr);
			}
		}

		if ($itemType instanceof NeverType || $keyType instanceof NeverType) {
			return new ConstantArrayType([], []);
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

	/**
	 * @return array{Type, Type}
	 */
	private function filterByTruthyValue(Scope $scope, Error|Variable|null $itemVar, Type $itemType, Error|Variable|null $keyVar, Type $keyType, Expr $expr): array
	{
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		$itemVarName = null;
		if ($itemVar !== null) {
			if (!$itemVar instanceof Variable || !is_string($itemVar->name)) {
				throw new ShouldNotHappenException();
			}
			$itemVarName = $itemVar->name;
			$scope = $scope->assignVariable($itemVarName, $itemType, $itemType);
		}

		$keyVarName = null;
		if ($keyVar !== null) {
			if (!$keyVar instanceof Variable || !is_string($keyVar->name)) {
				throw new ShouldNotHappenException();
			}
			$keyVarName = $keyVar->name;
			$scope = $scope->assignVariable($keyVarName, $keyType, $keyType);
		}

		$scope = $scope->filterByTruthyValue($expr);

		return [
			$itemVarName !== null ? $scope->getVariableType($itemVarName) : $itemType,
			$keyVarName !== null ? $scope->getVariableType($keyVarName) : $keyType,
		];
	}

}
