<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_map;
use function count;
use function in_array;
use function sprintf;

#[AutowiredService]
final class ArrayFilterFunctionReturnTypeHelper
{

	private const USE_BOTH = 1;
	private const USE_KEY = 2;
	private const USE_ITEM = 3;

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
		private ArrayPredicateCallbackResolver $predicateCallbackResolver,
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

		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		if ($mode === self::USE_ITEM) {
			$mapping = ArrayCallbackParameterMapping::item();
		} elseif ($mode === self::USE_KEY) {
			$mapping = ArrayCallbackParameterMapping::key();
		} else {
			$mapping = ArrayCallbackParameterMapping::valueAndKey();
		}

		$predicates = $this->predicateCallbackResolver->resolve($scope, $callbackArg, $mapping);
		if ($predicates === null) {
			return new ArrayType($keyType, $itemType);
		}

		$results = [];
		foreach ($predicates as $predicate) {
			if ($predicate->getExpr() === null) {
				$results[] = new ErrorType();
				continue;
			}

			$results[] = $this->filterByTruthyValue($scope, $predicate, $arrayArgType);
		}

		return TypeCombinator::union(...$results);
	}

	private function removeFalsey(Type $type): Type
	{
		return $type->filterArrayRemovingFalsey();
	}

	private function filterByTruthyValue(MutatingScope $scope, ArrayCallbackPredicate $predicate, Type $arrayType): Type
	{
		$constantArrays = $arrayType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$results = [];
			foreach ($constantArrays as $constantArray) {
				$builder = ConstantArrayTypeBuilder::createEmpty();
				$optionalKeys = $constantArray->getOptionalKeys();
				foreach ($constantArray->getKeyTypes() as $i => $keyType) {
					$itemType = $constantArray->getValueTypes()[$i];
					[$newKeyType, $newItemType, $optional] = $this->processKeyAndItemType($scope, $keyType, $itemType, $predicate);
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

				if ($constantArray->isUnsealed()->yes()) {
					$unsealedTypes = $constantArray->getUnsealedTypes();
					if ($unsealedTypes !== null) {
						[$newKey, $newValue] = $this->processKeyAndItemType($scope, $unsealedTypes[0], $unsealedTypes[1], $predicate);
						// Drop the unsealed slot when the predicate
						// rejects every possible extra (key or value
						// narrows to `Never`).
						if (!$newKey instanceof NeverType && !$newValue instanceof NeverType) {
							$builder->makeUnsealed($newKey, $newValue);
						}
					}
				}

				$results[] = $builder->getArray();
			}

			return TypeCombinator::union(...$results);
		}

		[$newKeyType, $newItemType] = $this->processKeyAndItemType($scope, $arrayType->getIterableKeyType(), $arrayType->getIterableValueType(), $predicate);

		if ($newItemType instanceof NeverType || $newKeyType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		return new ArrayType($newKeyType, $newItemType);
	}

	/**
	 * @return array{Type, Type, bool}
	 */
	private function processKeyAndItemType(MutatingScope $scope, Type $keyType, Type $itemType, ArrayCallbackPredicate $predicate): array
	{
		[$scope, $itemVarName, $keyVarName] = $this->predicateCallbackResolver->assignPredicateVariables($scope, $predicate, $itemType, $keyType);

		$expr = $predicate->getExpr();
		if ($expr === null) {
			throw new ShouldNotHappenException();
		}

		$booleanResult = $scope->getType($expr)->toBoolean();
		if ($booleanResult->isFalse()->yes()) {
			return [new NeverType(), new NeverType(), false];
		}

		$truthyScope = $scope->filterByTruthyValue($expr);

		$optional = !$booleanResult->isTrue()->yes();
		if ($optional) {
			$falseyScope = $scope->filterByFalseyValue($expr);
			$falseyItemType = $itemVarName !== null ? $falseyScope->getVariableType($itemVarName) : $itemType;
			$falseyKeyType = $keyVarName !== null ? $falseyScope->getVariableType($keyVarName) : $keyType;
			if ($falseyItemType instanceof NeverType || $falseyKeyType instanceof NeverType) {
				$optional = false;
			}
		}

		return [
			$keyVarName !== null ? $truthyScope->getVariableType($keyVarName) : $keyType,
			$itemVarName !== null ? $truthyScope->getVariableType($itemVarName) : $itemType,
			$optional,
		];
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
