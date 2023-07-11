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
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function count;
use function is_string;
use function strtolower;
use function substr;

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

		if ($itemType instanceof NeverType || $keyType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		if ($arrayArgType instanceof MixedType) {
			return new BenevolentUnionType([
				new ArrayType(new MixedType(), new MixedType()),
				new NullType(),
			]);
		}

		if ($callbackArg === null || ($callbackArg instanceof ConstFetch && strtolower($callbackArg->name->getParts()[0]) === 'null')) {
			return TypeCombinator::union(
				...array_map([$this, 'removeFalsey'], $arrayArgType->getArrays()),
			);
		}

		if ($flagArg === null) {
			if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
				$statement = $callbackArg->stmts[0];
				if ($statement instanceof Return_ && $statement->expr !== null) {
					return $this->filterByTruthyValue($scope, $callbackArg->params[0]->var, $arrayArgType, null, $statement->expr);
				}
			} elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
				return $this->filterByTruthyValue($scope, $callbackArg->params[0]->var, $arrayArgType, null, $callbackArg->expr);
			} elseif ($callbackArg instanceof String_) {
				$itemVar = new Variable('item');
				$expr = new FuncCall(self::createFunctionName($callbackArg->value), [new Arg($itemVar)]);
				return $this->filterByTruthyValue($scope, $itemVar, $arrayArgType, null, $expr);
			}
		}

		if ($flagArg instanceof ConstFetch && $flagArg->name->getParts()[0] === 'ARRAY_FILTER_USE_KEY') {
			if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
				$statement = $callbackArg->stmts[0];
				if ($statement instanceof Return_ && $statement->expr !== null) {
					return $this->filterByTruthyValue($scope, null, $arrayArgType, $callbackArg->params[0]->var, $statement->expr);
				}
			} elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
				return $this->filterByTruthyValue($scope, null, $arrayArgType, $callbackArg->params[0]->var, $callbackArg->expr);
			} elseif ($callbackArg instanceof String_) {
				$keyVar = new Variable('key');
				$expr = new FuncCall(self::createFunctionName($callbackArg->value), [new Arg($keyVar)]);
				return $this->filterByTruthyValue($scope, null, $arrayArgType, $keyVar, $expr);
			}
		}

		if ($flagArg instanceof ConstFetch && $flagArg->name->getParts()[0] === 'ARRAY_FILTER_USE_BOTH') {
			if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
				$statement = $callbackArg->stmts[0];
				if ($statement instanceof Return_ && $statement->expr !== null) {
					return $this->filterByTruthyValue($scope, $callbackArg->params[0]->var, $arrayArgType, $callbackArg->params[1]->var ?? null, $statement->expr);
				}
			} elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
				return $this->filterByTruthyValue($scope, $callbackArg->params[0]->var, $arrayArgType, $callbackArg->params[1]->var ?? null, $callbackArg->expr);
			} elseif ($callbackArg instanceof String_) {
				$itemVar = new Variable('item');
				$keyVar = new Variable('key');
				$expr = new FuncCall(self::createFunctionName($callbackArg->value), [new Arg($itemVar), new Arg($keyVar)]);
				return $this->filterByTruthyValue($scope, $itemVar, $arrayArgType, $keyVar, $expr);
			}
		}

		return new ArrayType($keyType, $itemType);
	}

	public function removeFalsey(Type $type): Type
	{
		$falseyTypes = StaticTypeFactory::falsey();

		if (count($type->getConstantArrays()) > 0) {
			$result = [];
			foreach ($type->getConstantArrays() as $constantArray) {
				$keys = $constantArray->getKeyTypes();
				$values = $constantArray->getValueTypes();

				$builder = ConstantArrayTypeBuilder::createEmpty();

				foreach ($values as $offset => $value) {
					$isFalsey = $falseyTypes->isSuperTypeOf($value);

					if ($isFalsey->maybe()) {
						$builder->setOffsetValueType($keys[$offset], TypeCombinator::remove($value, $falseyTypes), true);
					} elseif ($isFalsey->no()) {
						$builder->setOffsetValueType($keys[$offset], $value, $constantArray->isOptionalKey($offset));
					}
				}

				$result[] = $builder->getArray();
			}

			return TypeCombinator::union(...$result);
		}

		$keyType = $type->getIterableKeyType();
		$valueType = $type->getIterableValueType();

		$valueType = TypeCombinator::remove($valueType, $falseyTypes);

		if ($valueType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		return new ArrayType($keyType, $valueType);
	}

	private function filterByTruthyValue(Scope $scope, Error|Variable|null $itemVar, Type $arrayType, Error|Variable|null $keyVar, Expr $expr): Type
	{
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		$constantArrays = $arrayType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$results = [];
			foreach ($constantArrays as $constantArray) {
				$builder = ConstantArrayTypeBuilder::createEmpty();
				foreach ($constantArray->getKeyTypes() as $i => $keyType) {
					$itemType = $constantArray->getValueTypes()[$i];
					[$newKeyType, $newItemType, $optional] = $this->processKeyAndItemType($scope, $keyType, $itemType, $itemVar, $keyVar, $expr);
					if ($newKeyType instanceof NeverType || $newItemType instanceof NeverType) {
						continue;
					}
					if ($itemType->equals($newItemType) && $keyType->equals($newKeyType)) {
						$builder->setOffsetValueType($keyType, $itemType, $optional);
						continue;
					}

					$builder->setOffsetValueType($newKeyType, $newItemType, true);
				}

				$results[] = $builder->getArray();
			}

			return TypeCombinator::union(...$results);
		}

		[$newKeyType, $newItemType] = $this->processKeyAndItemType($scope, $arrayType->getIterableKeyType(), $arrayType->getIterableValueType(), $itemVar, $keyVar, $expr);

		if ($newItemType instanceof NeverType || $newKeyType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		return new ArrayType($newKeyType, $newItemType);
	}

	/**
	 * @return array{Type, Type, bool}
	 */
	private function processKeyAndItemType(MutatingScope $scope, Type $keyType, Type $itemType, Error|Variable|null $itemVar, Error|Variable|null $keyVar, Expr $expr): array
	{
		$itemVarName = null;
		if ($itemVar !== null) {
			if (!$itemVar instanceof Variable || !is_string($itemVar->name)) {
				throw new ShouldNotHappenException();
			}
			$itemVarName = $itemVar->name;
			$scope = $scope->assignVariable($itemVarName, $itemType, new MixedType());
		}

		$keyVarName = null;
		if ($keyVar !== null) {
			if (!$keyVar instanceof Variable || !is_string($keyVar->name)) {
				throw new ShouldNotHappenException();
			}
			$keyVarName = $keyVar->name;
			$scope = $scope->assignVariable($keyVarName, $keyType, new MixedType());
		}

		$booleanResult = $scope->getType($expr)->toBoolean();
		if ($booleanResult->isFalse()->yes()) {
			return [new NeverType(), new NeverType(), false];
		}

		$scope = $scope->filterByTruthyValue($expr);

		return [
			$keyVarName !== null ? $scope->getVariableType($keyVarName) : $keyType,
			$itemVarName !== null ? $scope->getVariableType($itemVarName) : $itemType,
			!$booleanResult instanceof ConstantBooleanType,
		];
	}

	private static function createFunctionName(string $funcName): Name
	{
		if ($funcName[0] === '\\') {
			return new Name\FullyQualified(substr($funcName, 1));
		}

		return new Name($funcName);
	}

}
