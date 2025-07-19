<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Error;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_map;
use function count;
use function in_array;
use function is_string;
use function sprintf;
use function substr;

#[AutowiredService]
final class ArrayFilterFunctionReturnTypeHelper
{

	private const USE_BOTH = 1;
	private const USE_KEY = 2;
	private const USE_ITEM = 3;

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getType(Scope $scope, ?Expr $arrayArg, ?Expr $callbackArg, ?Expr $flagArg): Type
	{
		if ($arrayArg === null) {
			return new ArrayType(new MixedType(), new MixedType());
		}

		$arrayArgType = $scope->getType($arrayArg);
		$arrayArgType = TypeUtils::toBenevolentUnion($arrayArgType);
		$keyType = $arrayArgType->getIterableKeyType();
		$itemType = $arrayArgType->getIterableValueType();

		if ($itemType instanceof NeverType || $keyType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		if ($arrayArgType instanceof MixedType) {
			if ($this->phpVersion->throwsValueErrorForInternalFunctions()) {
				return new ArrayType(new MixedType(), new MixedType());
			}

			return new BenevolentUnionType([
				new ArrayType(new MixedType(), new MixedType()),
				new NullType(),
			]);
		}

		if ($callbackArg === null || $scope->getType($callbackArg)->isNull()->yes()) {
			return TypeCombinator::union(
				...array_map([$this, 'removeFalsey'], $arrayArgType->getArrays()),
			);
		}

		$mode = $this->determineMode($flagArg, $scope);
		if ($mode === null) {
			return new ArrayType($keyType, $itemType);
		}

		if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
			$statement = $callbackArg->stmts[0];
			if ($statement instanceof Return_ && $statement->expr !== null) {
				if ($mode === self::USE_ITEM) {
					$keyVar = null;
					$itemVar = $callbackArg->params[0]->var;
				} elseif ($mode === self::USE_KEY) {
					$keyVar = $callbackArg->params[0]->var;
					$itemVar = null;
				} elseif ($mode === self::USE_BOTH) {
					$keyVar = $callbackArg->params[1]->var ?? null;
					$itemVar = $callbackArg->params[0]->var;
				}
				return $this->filterByTruthyValue($scope, $itemVar, $arrayArgType, $keyVar, $statement->expr);
			}
		} elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
			if ($mode === self::USE_ITEM) {
				$keyVar = null;
				$itemVar = $callbackArg->params[0]->var;
			} elseif ($mode === self::USE_KEY) {
				$keyVar = $callbackArg->params[0]->var;
				$itemVar = null;
			} elseif ($mode === self::USE_BOTH) {
				$keyVar = $callbackArg->params[1]->var ?? null;
				$itemVar = $callbackArg->params[0]->var;
			}
			return $this->filterByTruthyValue($scope, $itemVar, $arrayArgType, $keyVar, $callbackArg->expr);
		} elseif (
			($callbackArg instanceof FuncCall || $callbackArg instanceof MethodCall || $callbackArg instanceof StaticCall)
			&& $callbackArg->isFirstClassCallable()
		) {
			[$args, $itemVar, $keyVar] = $this->createDummyArgs($mode);
			$expr = clone $callbackArg;
			$expr->args = $args;
			return $this->filterByTruthyValue($scope, $itemVar, $arrayArgType, $keyVar, $expr);
		} else {
			$constantStrings = $scope->getType($callbackArg)->getConstantStrings();
			if (count($constantStrings) > 0) {
				$results = [];
				[$args, $itemVar, $keyVar] = $this->createDummyArgs($mode);

				foreach ($constantStrings as $constantString) {
					$funcName = self::createFunctionName($constantString->getValue());
					if ($funcName === null) {
						$results[] = new ErrorType();
						continue;
					}

					$expr = new FuncCall($funcName, $args);
					$results[] = $this->filterByTruthyValue($scope, $itemVar, $arrayArgType, $keyVar, $expr);
				}
				return TypeCombinator::union(...$results);
			}
		}

		return new ArrayType($keyType, $itemType);
	}

	private function removeFalsey(Type $type): Type
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
				$optionalKeys = $constantArray->getOptionalKeys();
				foreach ($constantArray->getKeyTypes() as $i => $keyType) {
					$itemType = $constantArray->getValueTypes()[$i];
					[$newKeyType, $newItemType, $optional] = $this->processKeyAndItemType($scope, $keyType, $itemType, $itemVar, $keyVar, $expr);
					$optional = $optional || in_array($i, $optionalKeys, true);
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
			$scope = $scope->assignVariable($itemVarName, $itemType, new MixedType(), TrinaryLogic::createYes());
		}

		$keyVarName = null;
		if ($keyVar !== null) {
			if (!$keyVar instanceof Variable || !is_string($keyVar->name)) {
				throw new ShouldNotHappenException();
			}
			$keyVarName = $keyVar->name;
			$scope = $scope->assignVariable($keyVarName, $keyType, new MixedType(), TrinaryLogic::createYes());
		}

		$booleanResult = $scope->getType($expr)->toBoolean();
		if ($booleanResult->isFalse()->yes()) {
			return [new NeverType(), new NeverType(), false];
		}

		$scope = $scope->filterByTruthyValue($expr);

		return [
			$keyVarName !== null ? $scope->getVariableType($keyVarName) : $keyType,
			$itemVarName !== null ? $scope->getVariableType($itemVarName) : $itemType,
			!$booleanResult->isTrue()->yes(),
		];
	}

	private static function createFunctionName(string $funcName): ?Name
	{
		if ($funcName === '') {
			return null;
		}

		if ($funcName[0] === '\\') {
			$funcName = substr($funcName, 1);

			if ($funcName === '') {
				return null;
			}

			return new Name\FullyQualified($funcName);
		}

		return new Name($funcName);
	}

	/**
	 * @param self::USE_* $mode
	 * @return array{list<Arg>, ?Variable, ?Variable}
	 */
	private function createDummyArgs(int $mode): array
	{
		if ($mode === self::USE_ITEM) {
			$itemVar = new Variable('item');
			$keyVar = null;
			$args = [new Arg($itemVar)];
		} elseif ($mode === self::USE_KEY) {
			$itemVar = null;
			$keyVar = new Variable('key');
			$args = [new Arg($keyVar)];
		} elseif ($mode === self::USE_BOTH) {
			$itemVar = new Variable('item');
			$keyVar = new Variable('key');
			$args = [new Arg($itemVar), new Arg($keyVar)];
		}
		return [$args, $itemVar, $keyVar];
	}

	/**
	 * @param non-empty-string $constantName
	 */
	private function getConstant(string $constantName): int
	{
		$constant = $this->reflectionProvider->getConstant(new Name($constantName), null);
		$valueType = $constant->getValueType();
		if (!$valueType instanceof ConstantIntegerType) {
			throw new ShouldNotHappenException(sprintf('Constant %s does not have integer type.', $constantName));
		}

		return $valueType->getValue();
	}

	/**
	 * @return self::USE_*|null
	 */
	private function determineMode(?Expr $flagArg, Scope $scope): ?int
	{
		if ($flagArg === null) {
			return self::USE_ITEM;
		}

		$flagValues = $scope->getType($flagArg)->getConstantScalarValues();
		if (count($flagValues) !== 1) {
			return null;
		}

		if ($flagValues[0] === $this->getConstant('ARRAY_FILTER_USE_KEY')) {
			return self::USE_KEY;
		} elseif ($flagValues[0] === $this->getConstant('ARRAY_FILTER_USE_BOTH')) {
			return self::USE_BOTH;
		}

		return null;
	}

}
