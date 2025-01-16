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

	public function handleAnyArray(Type $arrayType, Type $columnType, ?Type $indexType, Scope $scope): Type
	{
		$iterableAtLeastOnce = $arrayType->isIterableAtLeastOnce();
		if ($iterableAtLeastOnce->no()) {
			return new ConstantArrayType([], []);
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

		if ($returnValueType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		if ($indexType !== null) {
			$type = $this->getOffsetOrProperty($iterableValueType, $indexType, $scope, false);
			if ($type !== null) {
				$returnKeyType = $type;
			} else {
				$type = $this->getOffsetOrProperty($iterableValueType, $indexType, $scope, true);
				if ($type !== null) {
					$returnKeyType = TypeCombinator::union($type, new IntegerType());
				} else {
					$returnKeyType = new IntegerType();
				}
			}
		} else {
			$returnKeyType = new IntegerType();
		}

		$returnType = new ArrayType($this->castToArrayKeyType($returnKeyType), $returnValueType);

		if ($iterableAtLeastOnce->yes()) {
			$returnType = TypeCombinator::intersect($returnType, new NonEmptyArrayType());
		}
		if ($indexType === null) {
			$returnType = TypeCombinator::intersect($returnType, new AccessoryArrayListType());
		}

		return $returnType;
	}

	public function handleConstantArray(ConstantArrayType $arrayType, Type $columnType, ?Type $indexType, Scope $scope): ?Type
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

			if ($indexType !== null) {
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
