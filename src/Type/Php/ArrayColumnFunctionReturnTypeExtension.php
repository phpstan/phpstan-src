<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class ArrayColumnFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_column';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$numArgs = count($functionCall->getArgs());
		if ($numArgs < 2) {
			return null;
		}

		$arrayType = $scope->getType($functionCall->getArgs()[0]->value);
		$columnType = $scope->getType($functionCall->getArgs()[1]->value);
		$indexType = $numArgs >= 3 ? $scope->getType($functionCall->getArgs()[2]->value) : null;

		$constantArrayTypes = $arrayType->getConstantArrays();
		if (count($constantArrayTypes) === 1) {
			$type = $this->handleConstantArray($constantArrayTypes[0], $columnType, $indexType, $scope);
			if ($type !== null) {
				return $type;
			}
		}

		return $this->handleAnyArray($arrayType, $columnType, $indexType, $scope);
	}

	private function handleAnyArray(Type $arrayType, Type $columnType, ?Type $indexType, Scope $scope): Type
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
			$returnType = AccessoryArrayListType::intersectWith($returnType);
		}

		return $returnType;
	}

	private function handleConstantArray(ConstantArrayType $arrayType, Type $columnType, ?Type $indexType, Scope $scope): ?Type
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
		$offsetIsNull = (new NullType())->isSuperTypeOf($offsetOrProperty);
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
