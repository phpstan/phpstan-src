<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateUnionType;

/** @api */
class TypeCombinator
{

	public static function addNull(Type $type): Type
	{
		return self::union($type, new NullType());
	}

	public static function remove(Type $fromType, Type $typeToRemove): Type
	{
		if ($typeToRemove instanceof UnionType) {
			foreach ($typeToRemove->getTypes() as $unionTypeToRemove) {
				$fromType = self::remove($fromType, $unionTypeToRemove);
			}
			return $fromType;
		}

		if ($fromType instanceof UnionType) {
			$innerTypes = [];
			foreach ($fromType->getTypes() as $innerType) {
				$innerTypes[] = self::remove($innerType, $typeToRemove);
			}

			return self::union(...$innerTypes);
		}

		$isSuperType = $typeToRemove->isSuperTypeOf($fromType);
		if ($isSuperType->yes()) {
			return new NeverType();
		}
		if ($isSuperType->no()) {
			return $fromType;
		}

		if ($typeToRemove instanceof MixedType) {
			$typeToRemoveSubtractedType = $typeToRemove->getSubtractedType();
			if ($typeToRemoveSubtractedType !== null) {
				return self::intersect($fromType, $typeToRemoveSubtractedType);
			}
		}

		if ($fromType instanceof BooleanType) {
			if ($typeToRemove instanceof ConstantBooleanType) {
				return new ConstantBooleanType(!$typeToRemove->getValue());
			}
		} elseif ($fromType instanceof IterableType) {
			$arrayType = new ArrayType(new MixedType(), new MixedType());
			if ($typeToRemove->isSuperTypeOf($arrayType)->yes()) {
				return new GenericObjectType(\Traversable::class, [
					$fromType->getIterableKeyType(),
					$fromType->getIterableValueType(),
				]);
			}

			$traversableType = new ObjectType(\Traversable::class);
			if ($typeToRemove->isSuperTypeOf($traversableType)->yes()) {
				return new ArrayType($fromType->getIterableKeyType(), $fromType->getIterableValueType());
			}
		} elseif ($fromType instanceof IntegerRangeType) {
			$type = $fromType->tryRemove($typeToRemove);
			if ($type !== null) {
				return $type;
			}
		} elseif ($fromType instanceof IntegerType) {
			if ($typeToRemove instanceof IntegerRangeType || $typeToRemove instanceof ConstantIntegerType) {
				if ($typeToRemove instanceof IntegerRangeType) {
					$removeValueMin = $typeToRemove->getMin();
					$removeValueMax = $typeToRemove->getMax();
				} else {
					$removeValueMin = $typeToRemove->getValue();
					$removeValueMax = $typeToRemove->getValue();
				}
				$lowerPart = $removeValueMin !== null ? IntegerRangeType::fromInterval(null, $removeValueMin, -1) : null;
				$upperPart = $removeValueMax !== null ? IntegerRangeType::fromInterval($removeValueMax, null, +1) : null;
				if ($lowerPart !== null && $upperPart !== null) {
					return self::union($lowerPart, $upperPart);
				}
				return $lowerPart ?? $upperPart ?? new NeverType();
			}
		} elseif ($fromType->isArray()->yes()) {
			if ($typeToRemove instanceof ConstantArrayType && $typeToRemove->isIterableAtLeastOnce()->no()) {
				return self::intersect($fromType, new NonEmptyArrayType());
			}

			if ($typeToRemove instanceof NonEmptyArrayType) {
				return new ConstantArrayType([], []);
			}

			if ($fromType instanceof ConstantArrayType && $typeToRemove instanceof HasOffsetType) {
				return $fromType->unsetOffset($typeToRemove->getOffsetType());
			}
		} elseif ($fromType instanceof StringType) {
			if ($typeToRemove instanceof ConstantStringType && $typeToRemove->getValue() === '') {
				return self::intersect($fromType, new AccessoryNonEmptyStringType());
			}
			if ($typeToRemove instanceof AccessoryNonEmptyStringType) {
				return new ConstantStringType('');
			}
		} elseif ($fromType instanceof ObjectType && $fromType->getClassName() === \DateTimeInterface::class) {
			if ($typeToRemove instanceof ObjectType && $typeToRemove->getClassName() === \DateTimeImmutable::class) {
				return new ObjectType(\DateTime::class);
			}

			if ($typeToRemove instanceof ObjectType && $typeToRemove->getClassName() === \DateTime::class) {
				return new ObjectType(\DateTimeImmutable::class);
			}
		}

		if ($fromType instanceof SubtractableType) {
			$typeToSubtractFrom = $fromType;
			if ($fromType instanceof TemplateType) {
				$typeToSubtractFrom = $fromType->getBound();
			}

			if ($typeToSubtractFrom->isSuperTypeOf($typeToRemove)->yes()) {
				return $fromType->subtract($typeToRemove);
			}
		}

		return $fromType;
	}

	public static function removeNull(Type $type): Type
	{
		if (self::containsNull($type)) {
			return self::remove($type, new NullType());
		}

		return $type;
	}

	public static function containsNull(Type $type): bool
	{
		if ($type instanceof UnionType) {
			foreach ($type->getTypes() as $innerType) {
				if ($innerType instanceof NullType) {
					return true;
				}
			}

			return false;
		}

		return $type instanceof NullType;
	}

	public static function union(Type ...$types): Type
	{
		$benevolentTypes = [];
		$benevolentUnionObject = null;
		// transform A | (B | C) to A | B | C
		for ($i = 0; $i < count($types); $i++) {
			if ($types[$i] instanceof BenevolentUnionType) {
				if ($types[$i] instanceof TemplateBenevolentUnionType && $benevolentUnionObject === null) {
					$benevolentUnionObject = $types[$i];
				}
				foreach ($types[$i]->getTypes() as $benevolentInnerType) {
					$benevolentTypes[$benevolentInnerType->describe(VerbosityLevel::value())] = $benevolentInnerType;
				}
				array_splice($types, $i, 1, $types[$i]->getTypes());
				continue;
			}
			if (!($types[$i] instanceof UnionType)) {
				continue;
			}
			if ($types[$i] instanceof TemplateType) {
				continue;
			}

			array_splice($types, $i, 1, $types[$i]->getTypes());
		}

		$typesCount = count($types);
		$arrayTypes = [];
		$arrayAccessoryTypes = [];
		$scalarTypes = [];
		$hasGenericScalarTypes = [];
		for ($i = 0; $i < $typesCount; $i++) {
			if ($types[$i] instanceof NeverType) {
				unset($types[$i]);
				continue;
			}
			if ($types[$i] instanceof ConstantScalarType) {
				$type = $types[$i];
				$scalarTypes[get_class($type)][md5($type->describe(VerbosityLevel::cache()))] = $type;
				unset($types[$i]);
				continue;
			}
			if ($types[$i] instanceof BooleanType) {
				$hasGenericScalarTypes[ConstantBooleanType::class] = true;
			}
			if ($types[$i] instanceof FloatType) {
				$hasGenericScalarTypes[ConstantFloatType::class] = true;
			}
			if ($types[$i] instanceof IntegerType && !$types[$i] instanceof IntegerRangeType) {
				$hasGenericScalarTypes[ConstantIntegerType::class] = true;
			}
			if ($types[$i] instanceof StringType && !$types[$i] instanceof ClassStringType) {
				$hasGenericScalarTypes[ConstantStringType::class] = true;
			}
			if ($types[$i] instanceof IntersectionType) {
				$intermediateArrayType = null;
				$intermediateAccessoryTypes = [];
				foreach ($types[$i]->getTypes() as $innerType) {
					if ($innerType instanceof ArrayType) {
						$intermediateArrayType = $innerType;
						continue;
					}
					if ($innerType instanceof AccessoryType || $innerType instanceof CallableType) {
						$intermediateAccessoryTypes[$innerType->describe(VerbosityLevel::cache())] = $innerType;
						continue;
					}
				}

				if ($intermediateArrayType !== null) {
					$arrayTypes[] = $intermediateArrayType;
					$arrayAccessoryTypes[] = $intermediateAccessoryTypes;
					unset($types[$i]);
					continue;
				}
			}
			if (!$types[$i] instanceof ArrayType) {
				continue;
			}

			$arrayTypes[] = $types[$i];
			$arrayAccessoryTypes[] = [];
			unset($types[$i]);
		}

		foreach ($scalarTypes as $classType => $scalarTypeItems) {
			$scalarTypes[$classType] = array_values($scalarTypeItems);
		}

		/** @var ArrayType[] $arrayTypes */
		$arrayTypes = $arrayTypes;

		$arrayAccessoryTypesToProcess = [];
		if (count($arrayAccessoryTypes) > 1) {
			$arrayAccessoryTypesToProcess = array_values(array_intersect_key(...$arrayAccessoryTypes));
		} elseif (count($arrayAccessoryTypes) > 0) {
			$arrayAccessoryTypesToProcess = array_values($arrayAccessoryTypes[0]);
		}

		$types = array_values(
			array_merge(
				$types,
				self::processArrayTypes($arrayTypes, $arrayAccessoryTypesToProcess)
			)
		);

		// simplify string[] | int[] to (string|int)[]
		$typesCount = count($types);
		for ($i = 0; $i < $typesCount; $i++) {
			for ($j = $i + 1; $j < $typesCount; $j++) {
				if ($types[$i] instanceof IterableType && $types[$j] instanceof IterableType) {
					$types[$i] = new IterableType(
						self::union($types[$i]->getIterableKeyType(), $types[$j]->getIterableKeyType()),
						self::union($types[$i]->getIterableValueType(), $types[$j]->getIterableValueType())
					);
					array_splice($types, $j, 1);
					$typesCount--;
					continue 2;
				}
			}
		}

		foreach ($scalarTypes as $classType => $scalarTypeItems) {
			if (isset($hasGenericScalarTypes[$classType])) {
				unset($scalarTypes[$classType]);
				continue;
			}
			if ($classType === ConstantBooleanType::class && count($scalarTypeItems) === 2) {
				$types[] = new BooleanType();
				unset($scalarTypes[$classType]);
				continue;
			}

			$typesCount = count($types);
			$scalarTypeItemsCount = count($scalarTypeItems);
			for ($i = 0; $i < $typesCount; $i++) {
				for ($j = 0; $j < $scalarTypeItemsCount; $j++) {
					$compareResult = self::compareTypesInUnion($types[$i], $scalarTypeItems[$j]);
					if ($compareResult === null) {
						continue;
					}

					[$a, $b] = $compareResult;
					if ($a !== null) {
						$types[$i] = $a;
						array_splice($scalarTypeItems, $j--, 1);
						$scalarTypeItemsCount--;
						continue 1;
					}
					if ($b !== null) {
						$scalarTypeItems[$j] = $b;
						array_splice($types, $i--, 1);
						$typesCount--;
						continue 2;
					}
				}
			}

			$scalarTypes[$classType] = $scalarTypeItems;
		}

		if (count($types) > 16) {
			$newTypes = [];
			foreach ($types as $type) {
				$newTypes[$type->describe(VerbosityLevel::cache())] = $type;
			}
			$types = array_values($newTypes);
		}

		// transform A | A to A
		// transform A | never to A
		$typesCount = count($types);
		for ($i = 0; $i < $typesCount; $i++) {
			for ($j = $i + 1; $j < $typesCount; $j++) {
				$compareResult = self::compareTypesInUnion($types[$i], $types[$j]);
				if ($compareResult === null) {
					continue;
				}

				[$a, $b] = $compareResult;
				if ($a !== null) {
					$types[$i] = $a;
					array_splice($types, $j--, 1);
					$typesCount--;
					continue 1;
				}
				if ($b !== null) {
					$types[$j] = $b;
					array_splice($types, $i--, 1);
					$typesCount--;
					continue 2;
				}
			}
		}

		foreach ($scalarTypes as $scalarTypeItems) {
			foreach ($scalarTypeItems as $scalarType) {
				$types[] = $scalarType;
			}
		}

		$typesCount = count($types);
		if ($typesCount === 0) {
			return new NeverType();
		}
		if ($typesCount === 1) {
			return $types[0];
		}

		if (count($benevolentTypes) > 0) {
			$tempTypes = $types;
			foreach ($tempTypes as $i => $type) {
				if (!isset($benevolentTypes[$type->describe(VerbosityLevel::value())])) {
					break;
				}

				unset($tempTypes[$i]);
			}

			if (count($tempTypes) === 0) {
				if ($benevolentUnionObject instanceof TemplateBenevolentUnionType) {
					return $benevolentUnionObject->withTypes($types);
				}

				return new BenevolentUnionType($types);
			}
		}

		return new UnionType($types);
	}

	/**
	 * @param Type $a
	 * @param Type $b
	 * @return array{Type, null}|array{null, Type}|null
	 */
	private static function compareTypesInUnion(Type $a, Type $b): ?array
	{
		if ($a instanceof IntegerRangeType) {
			$type = $a->tryUnion($b);
			if ($type !== null) {
				$a = $type;
				return [$a, null];
			}
		}
		if ($b instanceof IntegerRangeType) {
			$type = $b->tryUnion($a);
			if ($type !== null) {
				$b = $type;
				return [null, $b];
			}
		}

		if ($a instanceof SubtractableType) {
			$typeWithoutSubtractedTypeA = $a->getTypeWithoutSubtractedType();
			if ($typeWithoutSubtractedTypeA instanceof MixedType && $b instanceof MixedType) {
				$isSuperType = $typeWithoutSubtractedTypeA->isSuperTypeOfMixed($b);
			} else {
				$isSuperType = $typeWithoutSubtractedTypeA->isSuperTypeOf($b);
			}
			if ($isSuperType->yes()) {
				$subtractedType = null;
				if ($b instanceof SubtractableType) {
					$subtractedType = $b->getSubtractedType();
				}
				$a = self::intersectWithSubtractedType($a, $subtractedType);
				return [$a, null];
			}
		}

		if ($b instanceof SubtractableType) {
			$typeWithoutSubtractedTypeB = $b->getTypeWithoutSubtractedType();
			if ($typeWithoutSubtractedTypeB instanceof MixedType && $a instanceof MixedType) {
				$isSuperType = $typeWithoutSubtractedTypeB->isSuperTypeOfMixed($a);
			} else {
				$isSuperType = $typeWithoutSubtractedTypeB->isSuperTypeOf($a);
			}
			if ($isSuperType->yes()) {
				$subtractedType = null;
				if ($a instanceof SubtractableType) {
					$subtractedType = $a->getSubtractedType();
				}
				$b = self::intersectWithSubtractedType($b, $subtractedType);
				return [null, $b];
			}
		}

		if (
			!$b instanceof ConstantArrayType
			&& $b->isSuperTypeOf($a)->yes()
		) {
			return [null, $b];
		}

		if (
			!$a instanceof ConstantArrayType
			&& $a->isSuperTypeOf($b)->yes()
		) {
			return [$a, null];
		}

		if (
			$a instanceof ConstantStringType
			&& $a->getValue() === ''
			&& $b->describe(VerbosityLevel::value()) === 'non-empty-string'
		) {
			return [null, new StringType()];
		}

		if (
			$b instanceof ConstantStringType
			&& $b->getValue() === ''
			&& $a->describe(VerbosityLevel::value()) === 'non-empty-string'
		) {
			return [new StringType(), null];
		}

		return null;
	}

	private static function unionWithSubtractedType(
		Type $type,
		?Type $subtractedType
	): Type
	{
		if ($subtractedType === null) {
			return $type;
		}

		if ($type instanceof SubtractableType) {
			if ($type->getSubtractedType() === null) {
				return $type;
			}

			$subtractedType = self::union(
				$type->getSubtractedType(),
				$subtractedType
			);
			if ($subtractedType instanceof NeverType) {
				$subtractedType = null;
			}

			return $type->changeSubtractedType($subtractedType);
		}

		if ($subtractedType->isSuperTypeOf($type)->yes()) {
			return new NeverType();
		}

		return self::remove($type, $subtractedType);
	}

	private static function intersectWithSubtractedType(
		SubtractableType $subtractableType,
		?Type $subtractedType
	): Type
	{
		if ($subtractableType->getSubtractedType() === null) {
			return $subtractableType;
		}

		if ($subtractedType === null) {
			return $subtractableType->getTypeWithoutSubtractedType();
		}

		$subtractedType = self::intersect(
			$subtractableType->getSubtractedType(),
			$subtractedType
		);
		if ($subtractedType instanceof NeverType) {
			$subtractedType = null;
		}

		return $subtractableType->changeSubtractedType($subtractedType);
	}

	/**
	 * @param ArrayType[] $arrayTypes
	 * @param Type[] $accessoryTypes
	 * @return Type[]
	 */
	private static function processArrayTypes(array $arrayTypes, array $accessoryTypes): array
	{
		foreach ($arrayTypes as $arrayType) {
			if (!$arrayType instanceof ConstantArrayType) {
				continue;
			}
			if (count($arrayType->getKeyTypes()) > 0) {
				continue;
			}

			foreach ($accessoryTypes as $i => $accessoryType) {
				if (!$accessoryType instanceof NonEmptyArrayType) {
					continue;
				}

				unset($accessoryTypes[$i]);
				break 2;
			}
		}
		if (count($arrayTypes) === 0) {
			return [];
		} elseif (count($arrayTypes) === 1) {
			return [
				self::intersect($arrayTypes[0], ...$accessoryTypes),
			];
		}

		$keyTypesForGeneralArray = [];
		$valueTypesForGeneralArray = [];
		$generalArrayOccurred = false;
		$constantKeyTypesNumbered = [];

		/** @var int|float $nextConstantKeyTypeIndex */
		$nextConstantKeyTypeIndex = 1;

		foreach ($arrayTypes as $arrayType) {
			if (!$arrayType instanceof ConstantArrayType || $generalArrayOccurred) {
				$keyTypesForGeneralArray[] = $arrayType->getKeyType();
				$valueTypesForGeneralArray[] = $arrayType->getItemType();
				$generalArrayOccurred = true;
				continue;
			}

			foreach ($arrayType->getKeyTypes() as $i => $keyType) {
				$keyTypesForGeneralArray[] = $keyType;
				$valueTypesForGeneralArray[] = $arrayType->getValueTypes()[$i];

				$keyTypeValue = $keyType->getValue();
				if (array_key_exists($keyTypeValue, $constantKeyTypesNumbered)) {
					continue;
				}

				$constantKeyTypesNumbered[$keyTypeValue] = $nextConstantKeyTypeIndex;
				$nextConstantKeyTypeIndex *= 2;
				if (!is_int($nextConstantKeyTypeIndex)) {
					$generalArrayOccurred = true;
					continue;
				}
			}
		}

		if ($generalArrayOccurred) {
			return [
				self::intersect(new ArrayType(
					self::union(...$keyTypesForGeneralArray),
					self::union(...$valueTypesForGeneralArray)
				), ...$accessoryTypes),
			];
		}

		/** @var ConstantArrayType[] $arrayTypes */
		$arrayTypes = $arrayTypes;

		/** @var int[] $constantKeyTypesNumbered */
		$constantKeyTypesNumbered = $constantKeyTypesNumbered;

		$constantArraysBuckets = [];
		foreach ($arrayTypes as $arrayTypeAgain) {
			$arrayIndex = 0;
			foreach ($arrayTypeAgain->getKeyTypes() as $keyType) {
				$arrayIndex += $constantKeyTypesNumbered[$keyType->getValue()];
			}

			if (!array_key_exists($arrayIndex, $constantArraysBuckets)) {
				$bucket = [];
				foreach ($arrayTypeAgain->getKeyTypes() as $i => $keyType) {
					$bucket[$keyType->getValue()] = [
						'keyType' => $keyType,
						'valueType' => $arrayTypeAgain->getValueTypes()[$i],
						'optional' => $arrayTypeAgain->isOptionalKey($i),
					];
				}
				$constantArraysBuckets[$arrayIndex] = $bucket;
				continue;
			}

			$bucket = $constantArraysBuckets[$arrayIndex];
			foreach ($arrayTypeAgain->getKeyTypes() as $i => $keyType) {
				$bucket[$keyType->getValue()]['valueType'] = self::union(
					$bucket[$keyType->getValue()]['valueType'],
					$arrayTypeAgain->getValueTypes()[$i]
				);
				$bucket[$keyType->getValue()]['optional'] = $bucket[$keyType->getValue()]['optional'] || $arrayTypeAgain->isOptionalKey($i);
			}

			$constantArraysBuckets[$arrayIndex] = $bucket;
		}

		$resultArrays = [];
		foreach ($constantArraysBuckets as $bucket) {
			$builder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($bucket as $data) {
				$builder->setOffsetValueType($data['keyType'], $data['valueType'], $data['optional']);
			}

			$resultArrays[] = self::intersect($builder->getArray(), ...$accessoryTypes);
		}

		return self::reduceArrays($resultArrays);
	}

	/**
	 * @param Type[] $constantArrays
	 * @return Type[]
	 */
	private static function reduceArrays(array $constantArrays): array
	{
		$newArrays = [];
		$arraysToProcess = [];
		foreach ($constantArrays as $constantArray) {
			if (!$constantArray instanceof ConstantArrayType) {
				$newArrays[] = $constantArray;
				continue;
			}

			$arraysToProcess[] = $constantArray;
		}

		for ($i = 0; $i < count($arraysToProcess); $i++) {
			for ($j = $i + 1; $j < count($arraysToProcess); $j++) {
				if ($arraysToProcess[$j]->isKeysSupersetOf($arraysToProcess[$i])) {
					$arraysToProcess[$j] = $arraysToProcess[$j]->mergeWith($arraysToProcess[$i]);
					array_splice($arraysToProcess, $i--, 1);
					continue 2;

				} elseif ($arraysToProcess[$i]->isKeysSupersetOf($arraysToProcess[$j])) {
					$arraysToProcess[$i] = $arraysToProcess[$i]->mergeWith($arraysToProcess[$j]);
					array_splice($arraysToProcess, $j--, 1);
					continue 1;
				}
			}
		}

		return array_merge($newArrays, $arraysToProcess);
	}

	public static function intersect(Type ...$types): Type
	{
		$sortTypes = static function (Type $a, Type $b): int {
			if (!$a instanceof UnionType || !$b instanceof UnionType) {
				return 0;
			}

			if ($a instanceof TemplateType) {
				return -1;
			}
			if ($b instanceof TemplateType) {
				return 1;
			}

			if ($a instanceof BenevolentUnionType) {
				return -1;
			}
			if ($b instanceof BenevolentUnionType) {
				return 1;
			}

			return 0;
		};
		usort($types, $sortTypes);
		// transform A & (B | C) to (A & B) | (A & C)
		foreach ($types as $i => $type) {
			if (!$type instanceof UnionType) {
				continue;
			}

			$topLevelUnionSubTypes = [];
			$innerTypes = $type->getTypes();
			usort($innerTypes, $sortTypes);
			foreach ($innerTypes as $innerUnionSubType) {
				$topLevelUnionSubTypes[] = self::intersect(
					$innerUnionSubType,
					...array_slice($types, 0, $i),
					...array_slice($types, $i + 1)
				);
			}

			$union = self::union(...$topLevelUnionSubTypes);
			if ($type instanceof BenevolentUnionType) {
				$union = TypeUtils::toBenevolentUnion($union);
			}

			if ($type instanceof TemplateUnionType || $type instanceof TemplateBenevolentUnionType) {
				$union = TemplateTypeFactory::create(
					$type->getScope(),
					$type->getName(),
					$union,
					$type->getVariance()
				);
				if ($type->isArgument()) {
					return TemplateTypeHelper::toArgument($union);
				}
			}

			return $union;
		}

		// transform A & (B & C) to A & B & C
		for ($i = 0; $i < count($types); $i++) {
			$type = $types[$i];
			if (!($type instanceof IntersectionType)) {
				continue;
			}

			array_splice($types, $i--, 1, $type->getTypes());
		}

		// transform IntegerType & ConstantIntegerType to ConstantIntegerType
		// transform Child & Parent to Child
		// transform Object & ~null to Object
		// transform A & A to A
		// transform int[] & string to never
		// transform callable & int to never
		// transform A & ~A to never
		// transform int & string to never
		for ($i = 0; $i < count($types); $i++) {
			for ($j = $i + 1; $j < count($types); $j++) {
				if ($types[$j] instanceof SubtractableType) {
					$typeWithoutSubtractedTypeA = $types[$j]->getTypeWithoutSubtractedType();

					if ($typeWithoutSubtractedTypeA instanceof MixedType && $types[$i] instanceof MixedType) {
						$isSuperTypeSubtractableA = $typeWithoutSubtractedTypeA->isSuperTypeOfMixed($types[$i]);
					} else {
						$isSuperTypeSubtractableA = $typeWithoutSubtractedTypeA->isSuperTypeOf($types[$i]);
					}
					if ($isSuperTypeSubtractableA->yes()) {
						$types[$i] = self::unionWithSubtractedType($types[$i], $types[$j]->getSubtractedType());
						array_splice($types, $j--, 1);
						continue 1;
					}
				}

				if ($types[$i] instanceof SubtractableType) {
					$typeWithoutSubtractedTypeB = $types[$i]->getTypeWithoutSubtractedType();

					if ($typeWithoutSubtractedTypeB instanceof MixedType && $types[$j] instanceof MixedType) {
						$isSuperTypeSubtractableB = $typeWithoutSubtractedTypeB->isSuperTypeOfMixed($types[$j]);
					} else {
						$isSuperTypeSubtractableB = $typeWithoutSubtractedTypeB->isSuperTypeOf($types[$j]);
					}
					if ($isSuperTypeSubtractableB->yes()) {
						$types[$j] = self::unionWithSubtractedType($types[$j], $types[$i]->getSubtractedType());
						array_splice($types, $i--, 1);
						continue 2;
					}
				}

				if ($types[$i] instanceof IntegerRangeType) {
					$intersectionType = $types[$i]->tryIntersect($types[$j]);
					if ($intersectionType !== null) {
						$types[$j] = $intersectionType;
						array_splice($types, $i--, 1);
						continue 2;
					}
				}

				if ($types[$j] instanceof IterableType) {
					$isSuperTypeA = $types[$j]->isSuperTypeOfMixed($types[$i]);
				} else {
					$isSuperTypeA = $types[$j]->isSuperTypeOf($types[$i]);
				}

				if ($isSuperTypeA->yes()) {
					array_splice($types, $j--, 1);
					continue;
				}

				if ($types[$i] instanceof IterableType) {
					$isSuperTypeB = $types[$i]->isSuperTypeOfMixed($types[$j]);
				} else {
					$isSuperTypeB = $types[$i]->isSuperTypeOf($types[$j]);
				}

				if ($isSuperTypeB->maybe()) {
					if ($types[$i] instanceof ConstantArrayType && $types[$j] instanceof HasOffsetType) {
						$types[$i] = $types[$i]->makeOffsetRequired($types[$j]->getOffsetType());
						array_splice($types, $j--, 1);
						continue;
					}

					if ($types[$j] instanceof ConstantArrayType && $types[$i] instanceof HasOffsetType) {
						$types[$j] = $types[$j]->makeOffsetRequired($types[$i]->getOffsetType());
						array_splice($types, $i--, 1);
						continue 2;
					}

					if (
						($types[$i] instanceof ArrayType || $types[$i] instanceof IterableType) &&
						($types[$j] instanceof ArrayType || $types[$j] instanceof IterableType)
					) {
						$keyType = self::intersect($types[$i]->getKeyType(), $types[$j]->getKeyType());
						$itemType = self::intersect($types[$i]->getItemType(), $types[$j]->getItemType());
						if ($types[$i] instanceof IterableType && $types[$j] instanceof IterableType) {
							$types[$j] = new IterableType($keyType, $itemType);
						} else {
							$types[$j] = new ArrayType($keyType, $itemType);
						}
						array_splice($types, $i--, 1);
						continue 2;
					}

					continue;
				}

				if ($isSuperTypeB->yes()) {
					array_splice($types, $i--, 1);
					continue 2;
				}

				if ($isSuperTypeA->no()) {
					return new NeverType();
				}
			}
		}

		if (count($types) === 1) {
			return $types[0];

		}

		return new IntersectionType($types);
	}

}
