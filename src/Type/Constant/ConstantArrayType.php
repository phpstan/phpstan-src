<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\InaccessibleMethod;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_reverse;
use function array_slice;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function implode;
use function in_array;
use function is_int;
use function is_string;
use function min;
use function pow;
use function sort;
use function sprintf;
use function strpos;

/**
 * @api
 */
class ConstantArrayType extends ArrayType implements ConstantType
{

	private const DESCRIBE_LIMIT = 8;

	/** @var self[]|null */
	private ?array $allArrays = null;

	/** @var non-empty-list<int> */
	private array $nextAutoIndexes;

	/**
	 * @api
	 * @param array<int, ConstantIntegerType|ConstantStringType> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param non-empty-list<int>|int $nextAutoIndexes
	 * @param int[] $optionalKeys
	 */
	public function __construct(
		private array $keyTypes,
		private array $valueTypes,
		int|array $nextAutoIndexes = [0],
		private array $optionalKeys = [],
	)
	{
		assert(count($keyTypes) === count($valueTypes));

		if (is_int($nextAutoIndexes)) {
			$nextAutoIndexes = [$nextAutoIndexes];
		}

		$this->nextAutoIndexes = $nextAutoIndexes;

		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			$keyType = new NeverType(true);
		} elseif ($keyTypesCount === 1) {
			$keyType = $this->keyTypes[0];
		} else {
			$keyType = new UnionType($this->keyTypes);
		}

		parent::__construct(
			$keyType,
			count($valueTypes) > 0 ? TypeCombinator::union(...$valueTypes) : new NeverType(true),
		);
	}

	public function isEmpty(): bool
	{
		return count($this->keyTypes) === 0;
	}

	/**
	 * @return non-empty-list<int>
	 */
	public function getNextAutoIndexes(): array
	{
		return $this->nextAutoIndexes;
	}

	/**
	 * @deprecated
	 */
	public function getNextAutoIndex(): int
	{
		return $this->nextAutoIndexes[count($this->nextAutoIndexes) - 1];
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
		if ($this->allArrays !== null) {
			return $this->allArrays;
		}

		if (count($this->optionalKeys) <= 10) {
			$optionalKeysCombinations = $this->powerSet($this->optionalKeys);
		} else {
			$optionalKeysCombinations = [
				[],
				$this->optionalKeys,
			];
		}

		$requiredKeys = [];
		foreach (array_keys($this->keyTypes) as $i) {
			if (in_array($i, $this->optionalKeys, true)) {
				continue;
			}
			$requiredKeys[] = $i;
		}

		$arrays = [];
		foreach ($optionalKeysCombinations as $combination) {
			$keys = array_merge($requiredKeys, $combination);
			$builder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($keys as $i) {
				$builder->setOffsetValueType($this->keyTypes[$i], $this->valueTypes[$i]);
			}

			$array = $builder->getArray();
			if (!$array instanceof ConstantArrayType) {
				throw new ShouldNotHappenException();
			}

			$arrays[] = $array;
		}

		return $this->allArrays = $arrays;
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
					if (count($type->optionalKeys) > 0) {
						return TrinaryLogic::createMaybe();
					}
					return TrinaryLogic::createNo();
				}

				return TrinaryLogic::createYes();
			}

			$results = [];
			foreach ($this->keyTypes as $i => $keyType) {
				$hasOffset = $type->hasOffsetValueType($keyType);
				if ($hasOffset->no()) {
					if (!$this->isOptionalKey($i)) {
						return TrinaryLogic::createNo();
					}

					$results[] = TrinaryLogic::createMaybe();
					continue;
				} elseif ($hasOffset->maybe() && !$this->isOptionalKey($i)) {
					$results[] = TrinaryLogic::createMaybe();
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
				$this->getItemType()->isSuperTypeOf($type->getItemType()),
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
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		$typeAndMethodName = $this->findTypeAndMethodName();
		if ($typeAndMethodName === null) {
			throw new ShouldNotHappenException();
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

	public function findTypeAndMethodName(): ?ConstantArrayTypeAndMethod
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
			$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
			if (!$reflectionProvider->hasClass($classOrObject->getValue())) {
				return null;
			}
			$type = new ObjectType($reflectionProvider->getClass($classOrObject->getValue())->getName());
		} elseif ($classOrObject instanceof GenericClassStringType) {
			$type = $classOrObject->getGenericType();
		} elseif ((new ObjectWithoutClassType())->isSuperTypeOf($classOrObject)->yes()) {
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
			if (
				$keyType instanceof ConstantIntegerType
				&& $offsetType instanceof StringType
				&& !$offsetType instanceof ConstantStringType
			) {
				return TrinaryLogic::createMaybe();
			}

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
		$all = true;
		foreach ($this->keyTypes as $i => $keyType) {
			if ($keyType->isSuperTypeOf($offsetType)->no()) {
				$all = false;
				continue;
			}

			$matchingValueTypes[] = $this->valueTypes[$i];
		}

		if ($all) {
			if (count($this->keyTypes) === 0) {
				return new ErrorType();
			}

			return $this->getIterableValueType();
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

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
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
					$keyTypes = $this->keyTypes;
					unset($keyTypes[$i]);
					$valueTypes = $this->valueTypes;
					unset($valueTypes[$i]);

					$newKeyTypes = [];
					$newValueTypes = [];
					$newOptionalKeys = [];

					$k = 0;
					foreach ($keyTypes as $j => $newKeyType) {
						$newKeyTypes[] = $newKeyType;
						$newValueTypes[] = $valueTypes[$j];
						if (in_array($j, $this->optionalKeys, true)) {
							$newOptionalKeys[] = $k;
						}
						$k++;
					}

					return new self($newKeyTypes, $newValueTypes, $this->nextAutoIndexes, $newOptionalKeys);
				}
			}
		}

		return new ArrayType($this->getKeyType(), $this->getItemType());
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		$keysCount = count($this->keyTypes);
		if ($keysCount === 0) {
			return TrinaryLogic::createNo();
		}

		$optionalKeysCount = count($this->optionalKeys);
		if ($optionalKeysCount < $keysCount) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

	public function removeLast(): self
	{
		return $this->removeLastElements(1);
	}

	/** @param positive-int $length */
	private function removeLastElements(int $length): self
	{
		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			return $this;
		}

		$keyTypes = $this->keyTypes;
		$valueTypes = $this->valueTypes;
		$optionalKeys = $this->optionalKeys;
		$nextAutoindex = $this->nextAutoIndexes;

		$optionalKeysRemoved = 0;
		$newLength = $keyTypesCount - $length;
		for ($i = $keyTypesCount - 1; $i >= 0; $i--) {
			$isOptional = $this->isOptionalKey($i);

			if ($i >= $newLength) {
				if ($isOptional) {
					$optionalKeysRemoved++;
					foreach ($optionalKeys as $key => $value) {
						if ($value === $i) {
							unset($optionalKeys[$key]);
							break;
						}
					}
				}

				$removedKeyType = array_pop($keyTypes);
				array_pop($valueTypes);
				$nextAutoindex = $removedKeyType instanceof ConstantIntegerType
					? $removedKeyType->getValue()
					: $this->getNextAutoIndex(); // @phpstan-ignore-line
				continue;
			}

			if ($isOptional || $optionalKeysRemoved <= 0) {
				continue;
			}

			$optionalKeys[] = $i;
			$optionalKeysRemoved--;
		}

		return new self(
			$keyTypes,
			$valueTypes,
			$nextAutoindex,
			array_values($optionalKeys),
		);
	}

	public function removeFirst(): self
	{
		return $this->removeFirstElements(1);
	}

	/** @param positive-int $length */
	private function removeFirstElements(int $length, bool $reindex = true): self
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		$optionalKeysIgnored = 0;
		foreach ($this->keyTypes as $i => $keyType) {
			$isOptional = $this->isOptionalKey($i);
			if ($i <= $length - 1) {
				if ($isOptional) {
					$optionalKeysIgnored++;
				}
				continue;
			}

			if (!$isOptional && $optionalKeysIgnored > 0) {
				$isOptional = true;
				$optionalKeysIgnored--;
			}

			$valueType = $this->valueTypes[$i];
			if ($reindex && $keyType instanceof ConstantIntegerType) {
				$keyType = null;
			}

			$builder->setOffsetValueType($keyType, $valueType, $isOptional);
		}

		$array = $builder->getArray();
		if (!$array instanceof self) {
			throw new ShouldNotHappenException();
		}

		return $array;
	}

	public function slice(int $offset, ?int $limit, bool $preserveKeys = false): self
	{
		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			return $this;
		}

		$limit ??= $keyTypesCount;
		if ($limit < 0) {
			// Negative limits prevent access to the most right n elements
			return $this->removeLastElements($limit * -1)
				->slice($offset, null, $preserveKeys);
		}

		if ($keyTypesCount + $offset <= 0) {
			// A negative offset cannot reach left outside the array
			$offset = 0;
		}

		if ($offset < 0) {
			/*
			 * Transforms the problem with the negative offset in one with a positive offset using array reversion.
			 * The reason is belows handling of optional keys which works only from left to right.
			 *
			 * e.g.
			 * array{a: 0, b: 1, c: 2, d: 3, e: 4}
			 * with offset -4 and limit 2 (which would be sliced to array{b: 1, c: 2})
			 *
			 * is transformed via reversion to
			 *
			 * array{e: 4, d: 3, c: 2, b: 1, a: 0}
			 * with offset 2 and limit 2 (which will be sliced to array{c: 2, b: 1} and then reversed again)
			 */
			$offset *= -1;
			$reversedLimit = min($limit, $offset);
			$reversedOffset = $offset - $reversedLimit;
			return $this->reverse(true)
				->slice($reversedOffset, $reversedLimit, $preserveKeys)
				->reverse(true);
		}

		if ($offset > 0) {
			return $this->removeFirstElements($offset, false)
				->slice(0, $limit, $preserveKeys);
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();

		$nonOptionalElementsCount = 0;
		$hasOptional = false;
		for ($i = 0; $nonOptionalElementsCount < $limit && $i < $keyTypesCount; $i++) {
			$isOptional = $this->isOptionalKey($i);
			if (!$isOptional) {
				$nonOptionalElementsCount++;
			} else {
				$hasOptional = true;
			}

			$isLastElement = $nonOptionalElementsCount >= $limit || $i + 1 >= $keyTypesCount;
			if ($isLastElement && $limit < $keyTypesCount && $hasOptional) {
				// If the slice is not full yet, but has at least one optional key
				// the last non-optional element is going to be optional.
				// Otherwise, it would not fit into the slice if previous non-optional keys are there.
				$isOptional = true;
			}

			$builder->setOffsetValueType($this->keyTypes[$i], $this->valueTypes[$i], $isOptional);
		}

		$slice = $builder->getArray();
		if (!$slice instanceof self) {
			throw new ShouldNotHappenException();
		}

		return $preserveKeys ? $slice : $slice->reindex();
	}

	public function reverse(bool $preserveKeys = false): self
	{
		$keyTypesReversed = array_reverse($this->keyTypes, true);
		$keyTypes = array_values($keyTypesReversed);
		$keyTypesReversedKeys = array_keys($keyTypesReversed);
		$optionalKeys = array_map(static fn (int $optionalKey): int => $keyTypesReversedKeys[$optionalKey], $this->optionalKeys);

		$reversed = new self($keyTypes, array_reverse($this->valueTypes), $this->nextAutoIndexes, $optionalKeys);

		return $preserveKeys ? $reversed : $reversed->reindex();
	}

	/** @param positive-int $length */
	public function chunk(int $length, bool $preserveKeys = false): self
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		$keyTypesCount = count($this->keyTypes);
		for ($i = 0; $i < $keyTypesCount; $i += $length) {
			$chunk = $this->slice($i, $length, true);
			$builder->setOffsetValueType(null, $preserveKeys ? $chunk : $chunk->getValuesArray());
		}

		$chunks = $builder->getArray();
		if (!$chunks instanceof self) {
			throw new ShouldNotHappenException();
		}

		return $chunks;
	}

	private function reindex(): self
	{
		$keyTypes = [];
		$autoIndex = 0;

		foreach ($this->keyTypes as $keyType) {
			if (!$keyType instanceof ConstantIntegerType) {
				$keyTypes[] = $keyType;
				continue;
			}

			$keyTypes[] = new ConstantIntegerType($autoIndex);
			$autoIndex++;
		}

		return new self($keyTypes, $this->valueTypes, [$autoIndex], $this->optionalKeys);
	}

	public function toBoolean(): BooleanType
	{
		return $this->count()->toBoolean();
	}

	public function toInteger(): Type
	{
		return $this->toBoolean()->toInteger();
	}

	public function toFloat(): Type
	{
		return $this->toBoolean()->toFloat();
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		if (count($this->keyTypes) === 0) {
			return $this;
		}

		if ($precision->isTemplateArgument()) {
			return $this->traverse(static fn (Type $type) => $type->generalize($precision));
		}

		$arrayType = new ArrayType(
			$this->getKeyType()->generalize($precision),
			$this->getItemType()->generalize($precision),
		);

		if (count($this->keyTypes) > count($this->optionalKeys)) {
			return TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		return $arrayType;
	}

	/**
	 * @return self
	 */
	public function generalizeValues(): ArrayType
	{
		$valueTypes = [];
		foreach ($this->valueTypes as $valueType) {
			$valueTypes[] = $valueType->generalize(GeneralizePrecision::lessSpecific());
		}

		return new self($this->keyTypes, $valueTypes, $this->nextAutoIndexes, $this->optionalKeys);
	}

	public function generalizeToArray(): Type
	{
		if ($this->isEmpty()) {
			return $this;
		}

		$arrayType = new ArrayType($this->getKeyType(), $this->getItemType());
		if ($this->isIterableAtLeastOnce()->yes()) {
			return TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		return $arrayType;
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
		if ($optionalKeysCount === 0) {
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

				$keyDescription = $keyType->getValue();
				if (is_string($keyDescription)) {
					if (strpos($keyDescription, '"') !== false) {
						$keyDescription = sprintf('\'%s\'', $keyDescription);
					} elseif (strpos($keyDescription, '\'') !== false) {
						$keyDescription = sprintf('"%s"', $keyDescription);
					}
				}

				$valueTypeDescription = $valueType->describe($level);
				$items[] = sprintf('%s%s: %s', $keyDescription, $isOptional ? '?' : '', $valueTypeDescription);
				$values[] = $valueTypeDescription;
			}

			$append = '';
			if ($truncate && count($items) > self::DESCRIBE_LIMIT) {
				$items = array_slice($items, 0, self::DESCRIBE_LIMIT);
				$values = array_slice($values, 0, self::DESCRIBE_LIMIT);
				$append = ', ...';
			}

			return sprintf(
				'array{%s%s}',
				implode(', ', $exportValuesOnly ? $values : $items),
				$append,
			);
		};
		return $level->handle(
			fn (): string => parent::describe($level),
			static fn (): string => $describeValue(true),
			static fn (): string => $describeValue(false),
		);
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if ($receivedType instanceof self) {
			$typeMap = TemplateTypeMap::createEmpty();
			foreach ($this->keyTypes as $i => $keyType) {
				$valueType = $this->valueTypes[$i];
				if ($receivedType->hasOffsetValueType($keyType)->no()) {
					continue;
				}
				$receivedValueType = $receivedType->getOffsetValueType($keyType);
				$typeMap = $typeMap->union($valueType->inferTemplateTypes($receivedValueType));
			}

			return $typeMap;
		}

		return parent::inferTemplateTypes($receivedType);
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
		$valueTypes = [];

		$stillOriginal = true;
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

		return new self($this->keyTypes, $valueTypes, $this->nextAutoIndexes, $this->optionalKeys);
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

		$nextAutoIndexes = array_unique(array_merge($this->nextAutoIndexes, $otherArray->nextAutoIndexes));
		sort($nextAutoIndexes);

		return new self($this->keyTypes, $valueTypes, $nextAutoIndexes, $optionalKeys);
	}

	/**
	 * @param ConstantIntegerType|ConstantStringType $otherKeyType
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
					return new self($this->keyTypes, $this->valueTypes, $this->nextAutoIndexes, array_values($optionalKeys));
				}
			}

			break;
		}

		return $this;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['keyTypes'], $properties['valueTypes'], $properties['nextAutoIndexes'] ?? $properties['nextAutoIndex'], $properties['optionalKeys'] ?? []);
	}

}
