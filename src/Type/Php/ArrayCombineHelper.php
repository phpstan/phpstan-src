<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function count;
use function is_int;
use function is_string;

#[AutowiredService]
final class ArrayCombineHelper
{

	/**
	 * @return array{Type, TrinaryLogic} The return type and if a ValueError may occur on PHP8 (and a warning on PHP7).
	 */
	public function getReturnAndThrowType(Expr $firstArg, Expr $secondArg, Scope $scope): array
	{
		$keysParamType = $scope->getType($firstArg);
		$valuesParamType = $scope->getType($secondArg);

		$constantKeysArrays = $keysParamType->getConstantArrays();
		$constantValuesArrays = $valuesParamType->getConstantArrays();
		if (
			$constantKeysArrays !== []
			&& $constantValuesArrays !== []
			&& count($constantKeysArrays) === count($constantValuesArrays)
		) {
			$results = [];
			foreach ($constantKeysArrays as $k => $constantKeysArray) {
				$constantValueArrays = $constantValuesArrays[$k];

				$keyTypes = $constantKeysArray->getValueTypes();
				$valueTypes = $constantValueArrays->getValueTypes();

				if (count($keyTypes) !== count($valueTypes)) {
					return [new NeverType(), TrinaryLogic::createYes()];
				}

				$keyTypes = $this->sanitizeConstantArrayKeyTypes($keyTypes);
				if ($keyTypes === null) {
					continue;
				}

				$builder = ConstantArrayTypeBuilder::createEmpty();
				foreach ($keyTypes as $i => $keyType) {
					if (!array_key_exists($i, $valueTypes)) {
						$results = [];
						break 2;
					}
					$valueType = $valueTypes[$i];
					$builder->setOffsetValueType($keyType, $valueType);
				}

				// When both inputs carry unsealed extras (of matching,
				// unbounded count) the extra positions pair up: the keys'
				// unsealed value becomes a key, the values' unsealed value
				// becomes its value. If only one side is unsealed, the
				// sealed side caps the size, so no extras can survive.
				$keysUnsealed = $constantKeysArray->getUnsealedTypes();
				$valuesUnsealed = $constantValueArrays->getUnsealedTypes();
				if (
					$constantKeysArray->isUnsealed()->yes()
					&& $constantValueArrays->isUnsealed()->yes()
					&& $keysUnsealed !== null
					&& $valuesUnsealed !== null
				) {
					$builder->makeUnsealed($keysUnsealed[1]->toArrayKey(), $valuesUnsealed[1]);
				}

				$results[] = $builder->getArray();
			}

			if ($results !== []) {
				return [TypeCombinator::union(...$results), TrinaryLogic::createNo()];
			}
		}

		if ($keysParamType->isArray()->yes()) {
			$itemType = $keysParamType->getIterableValueType();

			if ($itemType->isInteger()->no()) {
				if ($itemType->toString() instanceof ErrorType) {
					return [new NeverType(), TrinaryLogic::createNo()];
				}

				$keyType = $itemType->toString();
			} else {
				$keyType = $itemType;
			}
		} else {
			$keyType = new MixedType();
		}

		$arrayType = new ArrayType(
			$keyType,
			$valuesParamType->isArray()->yes() ? $valuesParamType->getIterableValueType() : new MixedType(),
		);

		if ($keysParamType->isIterableAtLeastOnce()->yes() && $valuesParamType->isIterableAtLeastOnce()->yes()) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		if ($firstArg instanceof Variable && $secondArg instanceof Variable && $firstArg->name === $secondArg->name) {
			return [$arrayType, TrinaryLogic::createNo()];
		}

		return [$arrayType, TrinaryLogic::createMaybe()];
	}

	/**
	 * @param array<int, Type> $types
	 *
	 * @return list<ConstantScalarType>|null
	 */
	private function sanitizeConstantArrayKeyTypes(array $types): ?array
	{
		$sanitizedTypes = [];

		foreach ($types as $type) {
			if (!$type->isInteger()->yes() && ! $type->toString() instanceof ErrorType) {
				$type = $type->toString();
			}

			$scalars = $type->getConstantScalarTypes();
			if (count($scalars) === 0) {
				return null;
			}

			foreach ($scalars as $scalar) {
				$value = $scalar->getValue();
				if (!is_int($value) && !is_string($value)) {
					return null;
				}

				$sanitizedTypes[] = $scalar;
			}
		}

		return $sanitizedTypes;
	}

}
