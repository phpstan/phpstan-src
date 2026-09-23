<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
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

#[AutowiredService]
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
		[$returnValueType, $certainty] = $this->getOffsetOrProperty($iterableValueType, $columnType, $scope);

		if (!$certainty->yes()) {
			$iterableAtLeastOnce = TrinaryLogic::createMaybe();
		}

		return [$returnValueType, $iterableAtLeastOnce];
	}

	public function getReturnIndexType(Type $arrayType, Type $indexType, Scope $scope): Type
	{
		if (!$indexType->isNull()->yes()) {
			$iterableValueType = $arrayType->getIterableValueType();

			[$type, $certainty] = $this->getOffsetOrProperty($iterableValueType, $indexType, $scope);
			if ($type instanceof NeverType) {
				return new IntegerType();
			}
			if ($certainty->yes()) {
				return $type;
			}

			return TypeCombinator::union($type, new IntegerType());
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
			[$valueType, $certainty] = $this->getOffsetOrProperty($iterableValueType, $columnType, $scope);
			if (!$certainty->yes()) {
				return null;
			}
			if ($valueType instanceof NeverType) {
				continue;
			}

			if (!$indexType->isNull()->yes()) {
				[$type, $certainty] = $this->getOffsetOrProperty($iterableValueType, $indexType, $scope);
				if ($type instanceof NeverType) {
					$keyType = null;
				} elseif ($certainty->yes()) {
					$keyType = $type;
				} else {
					$keyType = TypeCombinator::union($type, new IntegerType());
				}
			} else {
				$keyType = null;
			}

			if ($keyType !== null) {
				$keyType = $this->castToArrayKeyType($keyType);
			}
			$builder->setOffsetValueType($keyType, $valueType, $arrayType->isOptionalKey($i));
		}

		if ($arrayType->isUnsealed()->yes()) {
			$unsealedTypes = $arrayType->getUnsealedTypes();
			if ($unsealedTypes !== null) {
				[$unsealedValueType, $unsealedCertainty] = $this->getOffsetOrProperty($unsealedTypes[1], $columnType, $scope);
				if (!$unsealedCertainty->yes()) {
					return null;
				}
				if (!$unsealedValueType instanceof NeverType) {
					if (!$indexType->isNull()->yes()) {
						[$unsealedKeyFromIndex, $unsealedKeyCertainty] = $this->getOffsetOrProperty($unsealedTypes[1], $indexType, $scope);
						if ($unsealedKeyFromIndex instanceof NeverType) {
							$unsealedKey = $unsealedTypes[0];
						} elseif ($unsealedKeyCertainty->yes()) {
							$unsealedKey = $this->castToArrayKeyType($unsealedKeyFromIndex);
						} else {
							$unsealedKey = $this->castToArrayKeyType(TypeCombinator::union($unsealedKeyFromIndex, new IntegerType()));
						}
					} else {
						// `null` indexType keeps integer-keyed list semantics —
						// the unsealed range remains keyed by the source's
						// unsealed keys (typically `int`).
						$unsealedKey = $unsealedTypes[0];
					}

					$builder->makeUnsealed($unsealedKey, $unsealedValueType);
				}
			}
		}

		return $builder->getArray();
	}

	/**
	 * @return array{Type, TrinaryLogic}
	 */
	private function getOffsetOrProperty(Type $type, Type $offsetOrProperty, Scope $scope): array
	{
		$offsetIsNull = $offsetOrProperty->isNull();
		if ($offsetIsNull->yes()) {
			return [$type, TrinaryLogic::createYes()];
		}

		$returnTypes = [];

		if ($offsetIsNull->maybe()) {
			$returnTypes[] = $type;
		}

		if (!$type->canAccessProperties()->no()) {
			$propertyTypes = $offsetOrProperty->getConstantStrings();
			if ($propertyTypes === []) {
				return [new MixedType(), TrinaryLogic::createMaybe()];
			}
			foreach ($propertyTypes as $propertyType) {
				$propertyName = $propertyType->getValue();
				$hasProperty = $type->hasInstanceProperty($propertyName);
				if ($hasProperty->maybe()) {
					return [new MixedType(), TrinaryLogic::createMaybe()];
				}
				if (!$hasProperty->yes()) {
					continue;
				}

				$property = $type->getInstanceProperty($propertyName, $scope);
				if (!$scope->canReadProperty($property)) {
					foreach ($type->getObjectClassReflections() as $classReflection) {
						if ($classReflection->hasMethod('__isset') && $classReflection->hasMethod('__get')) {
							return [new MixedType(), TrinaryLogic::createMaybe()];
						}

						if (!$classReflection->isFinalByKeyword()) {
							if ($property->isPrivate()) {
								return [new MixedType(), TrinaryLogic::createMaybe()];
							}

							return [$property->getReadableType(), TrinaryLogic::createMaybe()];
						}
					}
					continue;
				}

				$returnTypes[] = $property->getReadableType();
			}
		}

		$certainty = TrinaryLogic::createYes();
		if ($type->isOffsetAccessible()->yes()) {
			$hasOffset = $type->hasOffsetValueType($offsetOrProperty);
			if ($hasOffset->maybe()) {
				$certainty = TrinaryLogic::createMaybe();
			}
			if (!$hasOffset->no()) {
				$returnTypes[] = $type->getOffsetValueType($offsetOrProperty);
			}
		}

		if ($returnTypes === []) {
			return [new NeverType(), TrinaryLogic::createYes()];
		}

		return [TypeCombinator::union(...$returnTypes), $certainty];
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
