<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class ArrayColumnHelper
{

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	/**
	 * @return array{Type, TrinaryLogic}
	 */
	public function getReturnValueType(Type $arrayType, Type $columnType, Scope $scope): array
	{
		$iterableAtLeastOnce = $arrayType->isIterableAtLeastOnce();
		if ($iterableAtLeastOnce->no()) {
			return [new NeverType(), $iterableAtLeastOnce];
		}

		$iterableValueType = $arrayType->getIterableValueType();
		$returnValueType = $this->getOffsetOrProperty($iterableValueType, $columnType, $scope, false);

		if ($returnValueType === null) {
			$returnValueType = $this->getOffsetOrProperty($iterableValueType, $columnType, $scope, true);
			$iterableAtLeastOnce = TrinaryLogic::createMaybe();
			if ($returnValueType === null) {
				throw new ShouldNotHappenException();
			}
		}

		return [$returnValueType, $iterableAtLeastOnce];
	}

	public function getReturnIndexType(Type $arrayType, Type $indexType, Scope $scope): Type
	{
		if (!$indexType->isNull()->yes()) {
			$iterableValueType = $arrayType->getIterableValueType();

			$type = $this->getOffsetOrProperty($iterableValueType, $indexType, $scope, false);
			if ($type !== null) {
				return $type;
			}

			$type = $this->getOffsetOrProperty($iterableValueType, $indexType, $scope, true);
			if ($type !== null) {
				return TypeCombinator::union($type, new IntegerType());
			}
		}

		return new IntegerType();
	}

	public function handleAnyArray(Type $arrayType, Type $columnType, Type $indexType, Scope $scope): Type
	{
		[$returnValueType, $iterableAtLeastOnce] = $this->getReturnValueType($arrayType, $columnType, $scope);
		if ($returnValueType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		$returnKeyType = $this->getReturnIndexType($arrayType, $indexType, $scope);
		$returnType = new ArrayType($this->castToArrayKeyType($returnKeyType), $returnValueType);

		if ($iterableAtLeastOnce->yes()) {
			$returnType = TypeCombinator::intersect($returnType, new NonEmptyArrayType());
		}
		if ($indexType->isNull()->yes()) {
			$returnType = TypeCombinator::intersect($returnType, new AccessoryArrayListType());
		}

		return $returnType;
	}

	public function handleConstantArray(ConstantArrayType $arrayType, Type $columnType, Type $indexType, Scope $scope): ?Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		foreach ($arrayType->getValueTypes() as $i => $iterableValueType) {
			$valueType = $this->getOffsetOrProperty($iterableValueType, $columnType, $scope, false);
			if ($valueType === null) {
				return null;
			}
			if ($valueType instanceof NeverType) {
				continue;
			}

			if (!$indexType->isNull()->yes()) {
				$type = $this->getOffsetOrProperty($iterableValueType, $indexType, $scope, false);
				if ($type !== null) {
					$keyType = $type;
				} else {
					$type = $this->getOffsetOrProperty($iterableValueType, $indexType, $scope, true);
					if ($type !== null) {
						$keyType = TypeCombinator::union($type, new IntegerType());
					} else {
						$keyType = null;
					}
				}
			} else {
				$keyType = null;
			}

			if ($keyType !== null) {
				$keyType = $this->castToArrayKeyType($keyType);
			}
			$builder->setOffsetValueType($keyType, $valueType, $arrayType->isOptionalKey($i));
		}

		return $builder->getArray();
	}

	private function getOffsetOrProperty(Type $type, Type $offsetOrProperty, Scope $scope, bool $allowMaybe): ?Type
	{
		$offsetIsNull = $offsetOrProperty->isNull();
		if ($offsetIsNull->yes()) {
			return $type;
		}

		$returnTypes = [];

		if ($offsetIsNull->maybe()) {
			$returnTypes[] = $type;
		}

		if (!$type->canAccessProperties()->no()) {
			$propertyTypes = $offsetOrProperty->getConstantStrings();
			if ($propertyTypes === []) {
				return new MixedType();
			}
			foreach ($propertyTypes as $propertyType) {
				$propertyName = $propertyType->getValue();
				$hasProperty = $type->hasProperty($propertyName);
				if ($hasProperty->maybe()) {
					return $allowMaybe ? new MixedType() : null;
				}
				if (!$hasProperty->yes()) {
					continue;
				}

				$returnTypes[] = $type->getProperty($propertyName, $scope)->getReadableType();
			}
		}

		if ($type->isOffsetAccessible()->yes()) {
			$hasOffset = $type->hasOffsetValueType($offsetOrProperty);
			if (!$allowMaybe && $hasOffset->maybe()) {
				return null;
			}
			if (!$hasOffset->no()) {
				$returnTypes[] = $type->getOffsetValueType($offsetOrProperty);
			}
		}

		if ($returnTypes === []) {
			return new NeverType();
		}

		return TypeCombinator::union(...$returnTypes);
	}

	private function castToArrayKeyType(Type $type): Type
	{
		$isArray = $type->isArray();
		if ($isArray->yes()) {
			return $this->phpVersion->throwsTypeErrorForInternalFunctions() ? new NeverType() : new IntegerType();
		}
		if ($isArray->no()) {
			return $type->toArrayKey();
		}
		$withoutArrayType = TypeCombinator::remove($type, new ArrayType(new MixedType(), new MixedType()));
		$keyType = $withoutArrayType->toArrayKey();
		if ($this->phpVersion->throwsTypeErrorForInternalFunctions()) {
			return $keyType;
		}
		return TypeCombinator::union($keyType, new IntegerType());
	}

}
