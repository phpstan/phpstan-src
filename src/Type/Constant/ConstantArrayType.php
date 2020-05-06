<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\InaccessibleMethod;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_unique;

class ConstantArrayType extends ArrayType implements ConstantType
{

	private const DESCRIBE_LIMIT = 8;

	/** @var array<int, ConstantIntegerType|ConstantStringType> */
	private $keyTypes;

	/** @var array<int, Type> */
	private $valueTypes;

	/** @var int */
	private $nextAutoIndex;

	/** @var int[] */
	private $optionalKeys;

	/**
	 * @param array<int, ConstantIntegerType|ConstantStringType> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param int $nextAutoIndex
	 * @param int[] $optionalKeys
	 */
	public function __construct(
		array $keyTypes,
		array $valueTypes,
		int $nextAutoIndex = 0,
		array $optionalKeys = []
	)
	{
		assert(count($keyTypes) === count($valueTypes));

		parent::__construct(
			count($keyTypes) > 0 ? TypeCombinator::union(...$keyTypes) : new NeverType(),
			count($valueTypes) > 0 ? TypeCombinator::union(...$valueTypes) : new NeverType()
		);

		$this->keyTypes = $keyTypes;
		$this->valueTypes = $valueTypes;
		$this->nextAutoIndex = $nextAutoIndex;
		$this->optionalKeys = $optionalKeys;
	}

	public function isEmpty(): bool
	{
		return count($this->keyTypes) === 0;
	}

	public function getNextAutoIndex(): int
	{
		return $this->nextAutoIndex;
	}

	/**
	 * @return int[]
	 */
	public function getOptionalKeys(): array
	{
		return $this->optionalKeys;
	}

	/**
	 * @return self[]
	 */
	public function getAllArrays(): array
	{
		$optionalKeysCombination = $this->powerSet($this->optionalKeys);
		$requiredKeys = [];
		foreach (array_keys($this->keyTypes) as $i) {
			if (in_array($i, $this->optionalKeys, true)) {
				continue;
			}
			$requiredKeys[] = $i;
		}

		$arrays = [];
		foreach ($optionalKeysCombination as $combination) {
			$keys = array_merge($requiredKeys, $combination);
			$keyTypes = [];
			$valueTypes = [];
			foreach ($keys as $i) {
				$keyTypes[] = $this->keyTypes[$i];
				$valueTypes[] = $this->valueTypes[$i];
			}

			$arrays[] = new self($keyTypes, $valueTypes, 0 /*TODO*/, []);
		}

		return $arrays;
	}

	/**
	 * @template T
	 * @param T[] $in
	 * @return T[][]
	 */
	private function powerSet(array $in): array
	{
		$count = count($in);
		$members = pow(2, $count);
		$return = [];
		for ($i = 0; $i < $members; $i++) {
			$b = sprintf('%0' . $count . 'b', $i);
			$out = [];
			for ($j = 0; $j < $count; $j++) {
				if ($b[$j] !== '1') {
					continue;
				}

				$out[] = $in[$j];
			}
			$return[] = $out;
		}

		return $return;
	}

	public function getKeyType(): Type
	{
		if (count($this->keyTypes) > 1) {
			return new UnionType($this->keyTypes);
		}

		return parent::getKeyType();
	}

	/**
	 * @return array<int, ConstantIntegerType|ConstantStringType>
	 */
	public function getKeyTypes(): array
	{
		return $this->keyTypes;
	}

	/**
	 * @return array<int, Type>
	 */
	public function getValueTypes(): array
	{
		return $this->valueTypes;
	}

	public function isOptionalKey(int $i): bool
	{
		return in_array($i, $this->optionalKeys, true);
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof MixedType && !$type instanceof TemplateMixedType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		if ($type instanceof self && count($this->keyTypes) === 0) {
			return TrinaryLogic::createFromBoolean(count($type->keyTypes) === 0);
		}

		$result = TrinaryLogic::createYes();
		foreach ($this->keyTypes as $i => $keyType) {
			$valueType = $this->valueTypes[$i];
			$hasOffset = $type->hasOffsetValueType($keyType);
			if ($hasOffset->no()) {
				if ($this->isOptionalKey($i)) {
					continue;
				}
				return $hasOffset;
			}
			if ($hasOffset->maybe() && $this->isOptionalKey($i)) {
				$hasOffset = TrinaryLogic::createYes();
			}

			$result = $result->and($hasOffset);
			$otherValueType = $type->getOffsetValueType($keyType);
			$acceptsValue = $valueType->accepts($otherValueType, $strictTypes);
			if ($acceptsValue->no()) {
				return $acceptsValue;
			}
			$result = $result->and($acceptsValue);
		}

		return $result->and($type->isArray());
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			if (count($this->keyTypes) === 0) {
				if (count($type->keyTypes) > 0) {
					return TrinaryLogic::createNo();
				}

				return TrinaryLogic::createYes();
			}

			$results = [];
			foreach ($this->keyTypes as $i => $keyType) {
				$hasOffset = $type->hasOffsetValueType($keyType);
				if ($hasOffset->no()) {
					return TrinaryLogic::createNo();
				}
				$results[] = $this->valueTypes[$i]->isSuperTypeOf($type->getOffsetValueType($keyType));
			}

			return TrinaryLogic::createYes()->and(...$results);
		}

		if ($type instanceof ArrayType) {
			$result = TrinaryLogic::createMaybe();
			if (count($this->keyTypes) === 0) {
				return $result;
			}

			return $result->and(
				$this->getKeyType()->isSuperTypeOf($type->getKeyType()),
				$this->getItemType()->isSuperTypeOf($type->getItemType())
			);
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if (count($this->keyTypes) !== count($type->keyTypes)) {
			return false;
		}

		foreach ($this->keyTypes as $i => $keyType) {
			$valueType = $this->valueTypes[$i];
			if (!$valueType->equals($type->valueTypes[$i])) {
				return false;
			}
			if (!$keyType->equals($type->keyTypes[$i])) {
				return false;
			}
		}

		if ($this->optionalKeys !== $type->optionalKeys) {
			return false;
		}

		return true;
	}

	public function isCallable(): TrinaryLogic
	{
		$typeAndMethod = $this->findTypeAndMethodName();
		if ($typeAndMethod === null) {
			return TrinaryLogic::createNo();
		}

		return $typeAndMethod->getCertainty();
	}

	/**
	 * @param \PHPStan\Reflection\ClassMemberAccessAnswerer $scope
	 * @return \PHPStan\Reflection\ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		$typeAndMethodName = $this->findTypeAndMethodName();
		if ($typeAndMethodName === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		if ($typeAndMethodName->isUnknown() || !$typeAndMethodName->getCertainty()->yes()) {
			return [new TrivialParametersAcceptor()];
		}

		$method = $typeAndMethodName->getType()
			->getMethod($typeAndMethodName->getMethod(), $scope);

		if (!$scope->canCallMethod($method)) {
			return [new InaccessibleMethod($method)];
		}

		return $method->getVariants();
	}

	private function findTypeAndMethodName(): ?ConstantArrayTypeAndMethod
	{
		if (count($this->keyTypes) !== 2) {
			return null;
		}

		if ($this->keyTypes[0]->isSuperTypeOf(new ConstantIntegerType(0))->no()) {
			return null;
		}

		if ($this->keyTypes[1]->isSuperTypeOf(new ConstantIntegerType(1))->no()) {
			return null;
		}

		[$classOrObject, $method] = $this->valueTypes;

		if (!$method instanceof ConstantStringType) {
			return ConstantArrayTypeAndMethod::createUnknown();
		}

		if ($classOrObject instanceof ConstantStringType) {
			$broker = Broker::getInstance();
			if (!$broker->hasClass($classOrObject->getValue())) {
				return ConstantArrayTypeAndMethod::createUnknown();
			}
			$type = new ObjectType($broker->getClass($classOrObject->getValue())->getName());
		} elseif ((new \PHPStan\Type\ObjectWithoutClassType())->isSuperTypeOf($classOrObject)->yes()) {
			$type = $classOrObject;
		} else {
			return ConstantArrayTypeAndMethod::createUnknown();
		}

		$has = $type->hasMethod($method->getValue());
		if (!$has->no()) {
			if ($this->isOptionalKey(0) || $this->isOptionalKey(1)) {
				$has = $has->and(TrinaryLogic::createMaybe());
			}

			return ConstantArrayTypeAndMethod::createConcrete($type, $method->getValue(), $has);
		}

		return null;
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		$offsetType = ArrayType::castToArrayKeyType($offsetType);
		if ($offsetType instanceof UnionType) {
			$results = [];
			foreach ($offsetType->getTypes() as $innerType) {
				$results[] = $this->hasOffsetValueType($innerType);
			}

			return TrinaryLogic::extremeIdentity(...$results);
		}

		$result = TrinaryLogic::createNo();
		foreach ($this->keyTypes as $i => $keyType) {
			$has = $keyType->isSuperTypeOf($offsetType);
			if ($has->yes()) {
				if ($this->isOptionalKey($i)) {
					return TrinaryLogic::createMaybe();
				}
				return TrinaryLogic::createYes();
			}
			if (!$has->maybe()) {
				continue;
			}

			$result = TrinaryLogic::createMaybe();
		}

		return $result;
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		$offsetType = ArrayType::castToArrayKeyType($offsetType);
		$matchingValueTypes = [];
		foreach ($this->keyTypes as $i => $keyType) {
			if ($keyType->isSuperTypeOf($offsetType)->no()) {
				continue;
			}

			$matchingValueTypes[] = $this->valueTypes[$i];
		}

		if (count($matchingValueTypes) > 0) {
			$type = TypeCombinator::union(...$matchingValueTypes);
			if ($type instanceof ErrorType) {
				return new MixedType();
			}

			return $type;
		}

		return new ErrorType(); // undefined offset
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType): Type
	{
		$builder = ConstantArrayTypeBuilder::createFromConstantArray($this);
		$builder->setOffsetValueType($offsetType, $valueType);

		return $builder->getArray();
	}

	public function unsetOffset(Type $offsetType): Type
	{
		$offsetType = ArrayType::castToArrayKeyType($offsetType);
		if ($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType) {
			foreach ($this->keyTypes as $i => $keyType) {
				if ($keyType->getValue() === $offsetType->getValue()) {
					$newKeyTypes = $this->keyTypes;
					unset($newKeyTypes[$i]);
					$newValueTypes = $this->valueTypes;
					unset($newValueTypes[$i]);
					$optionalKeys = $this->optionalKeys;
					unset($optionalKeys[$i]);

					return new self(array_values($newKeyTypes), array_values($newValueTypes), $this->nextAutoIndex, array_values($optionalKeys));
				}
			}
		}

		return $this->generalize();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean(count($this->keyTypes) > 0);
	}

	public function removeLast(): self
	{
		if (count($this->keyTypes) === 0) {
			return $this;
		}

		$i = count($this->keyTypes) - 1;

		$keyTypes = $this->keyTypes;
		$valueTypes = $this->valueTypes;
		$optionalKeys = $this->optionalKeys;
		unset($optionalKeys[$i]);

		$removedKeyType = array_pop($keyTypes);
		array_pop($valueTypes);
		$nextAutoindex = $removedKeyType instanceof ConstantIntegerType
			? $removedKeyType->getValue()
			: $this->nextAutoIndex;

		return new self(
			$keyTypes,
			$valueTypes,
			$nextAutoindex,
			array_values($optionalKeys)
		);
	}

	public function removeFirst(): ArrayType
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($this->keyTypes as $i => $keyType) {
			if ($i === 0) {
				continue;
			}

			$valueType = $this->valueTypes[$i];
			if ($keyType instanceof ConstantIntegerType) {
				$keyType = null;
			}

			$builder->setOffsetValueType($keyType, $valueType);
		}

		return $builder->getArray();
	}

	public function slice(int $offset, ?int $limit, bool $preserveKeys = false): self
	{
		if (count($this->keyTypes) === 0) {
			return $this;
		}

		$keyTypes = array_slice($this->keyTypes, $offset, $limit);
		$valueTypes = array_slice($this->valueTypes, $offset, $limit);

		if (!$preserveKeys) {
			$i = 0;
			/** @var array<int, ConstantIntegerType|ConstantStringType> $keyTypes */
			$keyTypes = array_map(static function ($keyType) use (&$i) {
				if ($keyType instanceof ConstantIntegerType) {
					$i++;
					return new ConstantIntegerType($i - 1);
				}

				return $keyType;
			}, $keyTypes);
		}

		/** @var int|float $nextAutoIndex */
		$nextAutoIndex = 0;
		foreach ($keyTypes as $keyType) {
			if (!$keyType instanceof ConstantIntegerType) {
				continue;
			}

			/** @var int|float $nextAutoIndex */
			$nextAutoIndex = max($nextAutoIndex, $keyType->getValue() + 1);
		}

		return new self(
			$keyTypes,
			$valueTypes,
			(int) $nextAutoIndex,
			[]
		);
	}

	public function toBoolean(): BooleanType
	{
		return new ConstantBooleanType(count($this->keyTypes) > 0);
	}

	public function generalize(): Type
	{
		if (count($this->keyTypes) === 0) {
			return $this;
		}

		return new ArrayType(
			TypeUtils::generalizeType($this->getKeyType()),
			$this->getItemType()
		);
	}

	/**
	 * @return self
	 */
	public function generalizeValues(): ArrayType
	{
		$valueTypes = [];
		foreach ($this->valueTypes as $valueType) {
			$valueTypes[] = TypeUtils::generalizeType($valueType);
		}

		return new self($this->keyTypes, $valueTypes, $this->nextAutoIndex, $this->optionalKeys);
	}

	/**
	 * @return self
	 */
	public function getKeysArray(): ArrayType
	{
		$keyTypes = [];
		$valueTypes = [];
		$optionalKeys = [];
		$autoIndex = 0;

		foreach ($this->keyTypes as $i => $keyType) {
			$keyTypes[] = new ConstantIntegerType($i);
			$valueTypes[] = $keyType;
			$autoIndex++;

			if (!$this->isOptionalKey($i)) {
				continue;
			}

			$optionalKeys[] = $i;
		}

		return new self($keyTypes, $valueTypes, $autoIndex, $optionalKeys);
	}

	/**
	 * @return self
	 */
	public function getValuesArray(): ArrayType
	{
		$keyTypes = [];
		$valueTypes = [];
		$optionalKeys = [];
		$autoIndex = 0;

		foreach ($this->valueTypes as $i => $valueType) {
			$keyTypes[] = new ConstantIntegerType($i);
			$valueTypes[] = $valueType;
			$autoIndex++;

			if (!$this->isOptionalKey($i)) {
				continue;
			}

			$optionalKeys[] = $i;
		}

		return new self($keyTypes, $valueTypes, $autoIndex, $optionalKeys);
	}

	public function count(): Type
	{
		$optionalKeysCount = count($this->optionalKeys);
		$totalKeysCount = count($this->getKeyTypes());
		if ($optionalKeysCount === $totalKeysCount) {
			return new ConstantIntegerType($totalKeysCount);
		}

		return IntegerRangeType::fromInterval($totalKeysCount - $optionalKeysCount, $totalKeysCount);
	}

	public function describe(VerbosityLevel $level): string
	{
		$describeValue = function (bool $truncate) use ($level): string {
			$items = [];
			$values = [];
			$exportValuesOnly = true;
			foreach ($this->keyTypes as $i => $keyType) {
				$valueType = $this->valueTypes[$i];
				if ($keyType->getValue() !== $i) {
					$exportValuesOnly = false;
				}

				$isOptional = $this->isOptionalKey($i);
				if ($isOptional) {
					$exportValuesOnly = false;
				}

				$items[] = sprintf('%s%s => %s', $isOptional ? '?' : '', var_export($keyType->getValue(), true), $valueType->describe($level));
				$values[] = $valueType->describe($level);
			}

			$append = '';
			if ($truncate && count($items) > self::DESCRIBE_LIMIT) {
				$items = array_slice($items, 0, self::DESCRIBE_LIMIT);
				$values = array_slice($values, 0, self::DESCRIBE_LIMIT);
				$append = ', ...';
			}

			return sprintf(
				'array(%s%s)',
				implode(', ', $exportValuesOnly ? $values : $items),
				$append
			);
		};
		return $level->handle(
			function () use ($level): string {
				return parent::describe($level);
			},
			static function () use ($describeValue): string {
				return $describeValue(true);
			},
			static function () use ($describeValue): string {
				return $describeValue(false);
			}
		);
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if ($receivedType instanceof self && !$this->isSuperTypeOf($receivedType)->no()) {
			$typeMap = TemplateTypeMap::createEmpty();
			foreach ($this->keyTypes as $i => $keyType) {
				$valueType = $this->valueTypes[$i];
				$receivedValueType = $receivedType->getOffsetValueType($keyType);
				$typeMap = $typeMap->union($valueType->inferTemplateTypes($receivedValueType));
			}

			return $typeMap;
		}

		if ($receivedType instanceof ArrayType) {
			return parent::inferTemplateTypes($receivedType);
		}

		return TemplateTypeMap::createEmpty();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$variance = $positionVariance->compose(TemplateTypeVariance::createInvariant());
		$references = [];

		foreach ($this->keyTypes as $type) {
			foreach ($type->getReferencedTemplateTypes($variance) as $reference) {
				$references[] = $reference;
			}
		}

		foreach ($this->valueTypes as $type) {
			foreach ($type->getReferencedTemplateTypes($variance) as $reference) {
				$references[] = $reference;
			}
		}

		return $references;
	}

	public function traverse(callable $cb): Type
	{
		$keyTypes = [];
		$valueTypes = [];

		$stillOriginal = true;
		foreach ($this->keyTypes as $keyType) {
			$transformedKeyType = $cb($keyType);
			if ($transformedKeyType !== $keyType) {
				$stillOriginal = false;
			}

			if (!$transformedKeyType instanceof ConstantIntegerType && !$transformedKeyType instanceof ConstantStringType) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			$keyTypes[] = $transformedKeyType;
		}

		foreach ($this->valueTypes as $valueType) {
			$transformedValueType = $cb($valueType);
			if ($transformedValueType !== $valueType) {
				$stillOriginal = false;
			}

			$valueTypes[] = $transformedValueType;
		}

		if ($stillOriginal) {
			return $this;
		}

		return new self($keyTypes, $valueTypes, $this->nextAutoIndex, $this->optionalKeys);
	}

	public function isKeysSupersetOf(self $otherArray): bool
	{
		if (count($this->keyTypes) === 0) {
			return count($otherArray->keyTypes) === 0;
		}

		if (count($otherArray->keyTypes) === 0) {
			return false;
		}

		$otherKeys = $otherArray->keyTypes;
		foreach ($this->keyTypes as $keyType) {
			foreach ($otherArray->keyTypes as $j => $otherKeyType) {
				if (!$keyType->equals($otherKeyType)) {
					continue;
				}

				unset($otherKeys[$j]);
				continue 2;
			}
		}

		return count($otherKeys) === 0;
	}

	public function mergeWith(self $otherArray): self
	{
		// only call this after verifying isKeysSupersetOf
		$valueTypes = $this->valueTypes;
		$optionalKeys = $this->optionalKeys;
		foreach ($this->keyTypes as $i => $keyType) {
			$otherIndex = $otherArray->getKeyIndex($keyType);
			if ($otherIndex === null) {
				$optionalKeys[] = $i;
				continue;
			}
			if ($otherArray->isOptionalKey($otherIndex)) {
				$optionalKeys[] = $i;
			}
			$otherValueType = $otherArray->valueTypes[$otherIndex];
			$valueTypes[$i] = TypeCombinator::union($valueTypes[$i], $otherValueType);
		}

		$optionalKeys = array_values(array_unique($optionalKeys));

		return new self($this->keyTypes, $valueTypes, $this->nextAutoIndex, $optionalKeys);
	}

	/**
	 * @param ConstantIntegerType|ConstantStringType $otherKeyType
	 * @return int|null
	 */
	private function getKeyIndex($otherKeyType): ?int
	{
		foreach ($this->keyTypes as $i => $keyType) {
			if ($keyType->equals($otherKeyType)) {
				return $i;
			}
		}

		return null;
	}

	public function makeOffsetRequired(Type $offsetType): self
	{
		$offsetType = ArrayType::castToArrayKeyType($offsetType);
		$optionalKeys = $this->optionalKeys;
		foreach ($this->keyTypes as $i => $keyType) {
			if (!$keyType->equals($offsetType)) {
				continue;
			}

			foreach ($optionalKeys as $j => $key) {
				if ($i === $key) {
					unset($optionalKeys[$j]);
					return new self($this->keyTypes, $this->valueTypes, $this->nextAutoIndex, array_values($optionalKeys));
				}
			}

			break;
		}

		return $this;
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['keyTypes'], $properties['valueTypes'], $properties['nextAutoIndex'], $properties['optionalKeys'] ?? []);
	}

}
