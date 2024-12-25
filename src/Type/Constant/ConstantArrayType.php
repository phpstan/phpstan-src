<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use Nette\Utils\Strings;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Internal\CombinationsHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\Callables\FunctionCallableVariant;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\InaccessibleMethod;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Traits\ArrayTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_push;
use function array_slice;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function implode;
use function in_array;
use function is_string;
use function min;
use function pow;
use function range;
use function sort;
use function sprintf;
use function str_contains;

/**
 * @api
 */
class ConstantArrayType implements Type
{

	use ArrayTypeTrait {
		chunkArray as traitChunkArray;
	}
	use NonObjectTypeTrait;
	use UndecidedComparisonTypeTrait;

	private const DESCRIBE_LIMIT = 8;
	private const CHUNK_FINITE_TYPES_LIMIT = 5;

	private TrinaryLogic $isList;

	/** @var self[]|null */
	private ?array $allArrays = null;

	private ?Type $iterableKeyType = null;

	private ?Type $iterableValueType = null;

	/**
	 * @api
	 * @param array<int, ConstantIntegerType|ConstantStringType> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param non-empty-list<int> $nextAutoIndexes
	 * @param int[] $optionalKeys
	 */
	public function __construct(
		private array $keyTypes,
		private array $valueTypes,
		private array $nextAutoIndexes = [0],
		private array $optionalKeys = [],
		?TrinaryLogic $isList = null,
	)
	{
		assert(count($keyTypes) === count($valueTypes));

		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			$isList = TrinaryLogic::createYes();
		}

		if ($isList === null) {
			$isList = TrinaryLogic::createNo();
		}
		$this->isList = $isList;
	}

	public function getConstantArrays(): array
	{
		return [$this];
	}

	public function getReferencedClasses(): array
	{
		$referencedClasses = [];
		foreach ($this->getKeyTypes() as $keyType) {
			foreach ($keyType->getReferencedClasses() as $referencedClass) {
				$referencedClasses[] = $referencedClass;
			}
		}

		foreach ($this->getValueTypes() as $valueType) {
			foreach ($valueType->getReferencedClasses() as $referencedClass) {
				$referencedClasses[] = $referencedClass;
			}
		}

		return $referencedClasses;
	}

	public function getIterableKeyType(): Type
	{
		if ($this->iterableKeyType !== null) {
			return $this->iterableKeyType;
		}

		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			$keyType = new NeverType(true);
		} elseif ($keyTypesCount === 1) {
			$keyType = $this->keyTypes[0];
		} else {
			$keyType = new UnionType($this->keyTypes);
		}

		return $this->iterableKeyType = $keyType;
	}

	public function getIterableValueType(): Type
	{
		if ($this->iterableValueType !== null) {
			return $this->iterableValueType;
		}

		return $this->iterableValueType = count($this->valueTypes) > 0 ? TypeCombinator::union(...$this->valueTypes) : new NeverType(true);
	}

	public function getKeyType(): Type
	{
		return $this->getIterableKeyType();
	}

	public function getItemType(): Type
	{
		return $this->getIterableValueType();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	/**
	 * @return non-empty-list<int>
	 */
	public function getNextAutoIndexes(): array
	{
		return $this->nextAutoIndexes;
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
			sort($keys);

			if ($this->isList->yes() && array_keys($keys) !== $keys) {
				continue;
			}

			$builder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($keys as $i) {
				$builder->setOffsetValueType($this->keyTypes[$i], $this->valueTypes[$i]);
			}

			$array = $builder->getArray();
			if (!$array instanceof self) {
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

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType && !$type instanceof IntersectionType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		if ($type instanceof self && count($this->keyTypes) === 0) {
			return AcceptsResult::createFromBoolean(count($type->keyTypes) === 0);
		}

		$result = AcceptsResult::createYes();
		foreach ($this->keyTypes as $i => $keyType) {
			$valueType = $this->valueTypes[$i];
			$hasOffsetValueType = $type->hasOffsetValueType($keyType);
			$hasOffset = new AcceptsResult(
				$hasOffsetValueType,
				$hasOffsetValueType->yes() || !$type->isConstantArray()->yes() ? [] : [sprintf('Array %s have offset %s.', $hasOffsetValueType->no() ? 'does not' : 'might not', $keyType->describe(VerbosityLevel::value()))],
			);
			if ($hasOffset->no()) {
				if ($this->isOptionalKey($i)) {
					continue;
				}
				return $hasOffset;
			}
			if ($hasOffset->maybe() && $this->isOptionalKey($i)) {
				$hasOffset = AcceptsResult::createYes();
			}

			$result = $result->and($hasOffset);
			$otherValueType = $type->getOffsetValueType($keyType);
			$verbosity = VerbosityLevel::getRecommendedLevelByType($valueType, $otherValueType);
			$acceptsValue = $valueType->accepts($otherValueType, $strictTypes)->decorateReasons(
				static fn (string $reason) => sprintf(
					'Offset %s (%s) does not accept type %s: %s',
					$keyType->describe(VerbosityLevel::precise()),
					$valueType->describe($verbosity),
					$otherValueType->describe($verbosity),
					$reason,
				),
			);
			if (!$acceptsValue->yes() && count($acceptsValue->reasons) === 0 && $type->isConstantArray()->yes()) {
				$acceptsValue = new AcceptsResult($acceptsValue->result, [
					sprintf(
						'Offset %s (%s) does not accept type %s.',
						$keyType->describe(VerbosityLevel::precise()),
						$valueType->describe($verbosity),
						$otherValueType->describe($verbosity),
					),
				]);
			}
			if ($acceptsValue->no()) {
				return $acceptsValue;
			}
			$result = $result->and($acceptsValue);
		}

		$result = $result->and(new AcceptsResult($type->isArray(), []));
		if ($type->isOversizedArray()->yes()) {
			if (!$result->no()) {
				return AcceptsResult::createYes();
			}
		}

		return $result;
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			if (count($this->keyTypes) === 0) {
				return new IsSuperTypeOfResult($type->isIterableAtLeastOnce()->negate(), []);
			}

			$results = [];
			foreach ($this->keyTypes as $i => $keyType) {
				$hasOffset = $type->hasOffsetValueType($keyType);
				if ($hasOffset->no()) {
					if (!$this->isOptionalKey($i)) {
						return IsSuperTypeOfResult::createNo();
					}

					$results[] = IsSuperTypeOfResult::createYes();
					continue;
				} elseif ($hasOffset->maybe() && !$this->isOptionalKey($i)) {
					$results[] = IsSuperTypeOfResult::createMaybe();
				}

				$isValueSuperType = $this->valueTypes[$i]->isSuperTypeOf($type->getOffsetValueType($keyType));
				if ($isValueSuperType->no()) {
					return $isValueSuperType->decorateReasons(static fn (string $reason) => sprintf('Offset %s: %s', $keyType->describe(VerbosityLevel::value()), $reason));
				}
				$results[] = $isValueSuperType;
			}

			return IsSuperTypeOfResult::createYes()->and(...$results);
		}

		if ($type instanceof ArrayType) {
			$result = IsSuperTypeOfResult::createMaybe();
			if (count($this->keyTypes) === 0) {
				return $result;
			}

			$isKeySuperType = $this->getKeyType()->isSuperTypeOf($type->getKeyType());
			if ($isKeySuperType->no()) {
				return $isKeySuperType;
			}

			return $result->and($isKeySuperType, $this->getItemType()->isSuperTypeOf($type->getItemType()));
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return IsSuperTypeOfResult::createNo();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		if ($type->isInteger()->yes()) {
			return new ConstantBooleanType(false);
		}

		if ($this->isIterableAtLeastOnce()->no()) {
			if ($type->isIterableAtLeastOnce()->yes()) {
				return new ConstantBooleanType(false);
			}

			$constantScalarValues = $type->getConstantScalarValues();
			if (count($constantScalarValues) > 0) {
				$results = [];
				foreach ($constantScalarValues as $constantScalarValue) {
					// @phpstan-ignore equal.invalid, equal.notAllowed
					$results[] = TrinaryLogic::createFromBoolean($constantScalarValue == []); // phpcs:ignore
				}

				return TrinaryLogic::extremeIdentity(...$results)->toBooleanType();
			}
		}

		return new BooleanType();
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
		$typeAndMethods = $this->findTypeAndMethodNames();
		if ($typeAndMethods === []) {
			return TrinaryLogic::createNo();
		}

		$results = array_map(
			static fn (ConstantArrayTypeAndMethod $typeAndMethod): TrinaryLogic => $typeAndMethod->getCertainty(),
			$typeAndMethods,
		);

		return TrinaryLogic::createYes()->and(...$results);
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		$typeAndMethodNames = $this->findTypeAndMethodNames();
		if ($typeAndMethodNames === []) {
			throw new ShouldNotHappenException();
		}

		$acceptors = [];
		foreach ($typeAndMethodNames as $typeAndMethodName) {
			if ($typeAndMethodName->isUnknown() || !$typeAndMethodName->getCertainty()->yes()) {
				$acceptors[] = new TrivialParametersAcceptor();
				continue;
			}

			$method = $typeAndMethodName->getType()
				->getMethod($typeAndMethodName->getMethod(), $scope);

			if (!$scope->canCallMethod($method)) {
				$acceptors[] = new InaccessibleMethod($method);
				continue;
			}

			array_push($acceptors, ...FunctionCallableVariant::createFromVariants($method, $method->getVariants()));
		}

		return $acceptors;
	}

	/**
	 * @return array{Type, Type}|array{}
	 */
	private function getClassOrObjectAndMethods(): array
	{
		if (count($this->keyTypes) !== 2) {
			return [];
		}

		$classOrObject = null;
		$method = null;
		foreach ($this->keyTypes as $i => $keyType) {
			if ($keyType->isSuperTypeOf(new ConstantIntegerType(0))->yes()) {
				$classOrObject = $this->valueTypes[$i];
				continue;
			}

			if (!$keyType->isSuperTypeOf(new ConstantIntegerType(1))->yes()) {
				continue;
			}

			$method = $this->valueTypes[$i];
		}

		if ($classOrObject === null || $method === null) {
			return [];
		}

		return [$classOrObject, $method];
	}

	/** @return ConstantArrayTypeAndMethod[] */
	public function findTypeAndMethodNames(): array
	{
		$callableArray = $this->getClassOrObjectAndMethods();
		if ($callableArray === []) {
			return [];
		}

		[$classOrObject, $methods] = $callableArray;
		if (count($methods->getConstantStrings()) === 0) {
			return [ConstantArrayTypeAndMethod::createUnknown()];
		}

		$type = $classOrObject->getObjectTypeOrClassStringObjectType();
		if (!$type->isObject()->yes()) {
			return [ConstantArrayTypeAndMethod::createUnknown()];
		}

		$typeAndMethods = [];
		$phpVersion = PhpVersionStaticAccessor::getInstance();
		foreach ($methods->getConstantStrings() as $method) {
			$has = $type->hasMethod($method->getValue());
			if ($has->no()) {
				continue;
			}

			if (
				$has->yes()
				&& !$phpVersion->supportsCallableInstanceMethods()
			) {
				$methodReflection = $type->getMethod($method->getValue(), new OutOfClassScope());
				if ($classOrObject->isString()->yes() && !$methodReflection->isStatic()) {
					continue;
				}
			}

			if ($this->isOptionalKey(0) || $this->isOptionalKey(1)) {
				$has = $has->and(TrinaryLogic::createMaybe());
			}

			$typeAndMethods[] = ConstantArrayTypeAndMethod::createConcrete($type, $method->getValue(), $has);
		}

		return $typeAndMethods;
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		$offsetType = $offsetType->toArrayKey();
		if ($offsetType instanceof UnionType) {
			$results = [];
			foreach ($offsetType->getTypes() as $innerType) {
				$results[] = $this->hasOffsetValueType($innerType);
			}

			return TrinaryLogic::extremeIdentity(...$results);
		}
		if ($offsetType instanceof IntegerRangeType) {
			$finiteTypes = $offsetType->getFiniteTypes();
			if ($finiteTypes !== []) {
				$results = [];
				foreach ($finiteTypes as $innerType) {
					$results[] = $this->hasOffsetValueType($innerType);
				}

				return TrinaryLogic::extremeIdentity(...$results);
			}
		}

		$result = TrinaryLogic::createNo();
		foreach ($this->keyTypes as $i => $keyType) {
			if (
				$keyType instanceof ConstantIntegerType
				&& !$offsetType->isString()->no()
				&& $offsetType->isConstantScalarValue()->no()
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
		if (count($this->keyTypes) === 0) {
			return new ErrorType();
		}

		$offsetType = $offsetType->toArrayKey();
		$matchingValueTypes = [];
		$all = true;
		$maybeAll = true;
		foreach ($this->keyTypes as $i => $keyType) {
			if ($keyType->isSuperTypeOf($offsetType)->no()) {
				$all = false;

				if (
					$keyType instanceof ConstantIntegerType
					&& !$offsetType->isString()->no()
					&& $offsetType->isConstantScalarValue()->no()
				) {
					continue;
				}
				$maybeAll = false;
				continue;
			}

			$matchingValueTypes[] = $this->valueTypes[$i];
		}

		if ($all) {
			return $this->getIterableValueType();
		}

		if (count($matchingValueTypes) > 0) {
			$type = TypeCombinator::union(...$matchingValueTypes);
			if ($type instanceof ErrorType) {
				return new MixedType();
			}

			return $type;
		}

		if ($maybeAll) {
			return $this->getIterableValueType();
		}

		return new ErrorType(); // undefined offset
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		$builder = ConstantArrayTypeBuilder::createFromConstantArray($this);
		$builder->setOffsetValueType($offsetType, $valueType);

		return $builder->getArray();
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		$offsetType = $offsetType->toArrayKey();
		$builder = ConstantArrayTypeBuilder::createFromConstantArray($this);
		foreach ($this->keyTypes as $keyType) {
			if ($offsetType->isSuperTypeOf($keyType)->no()) {
				continue;
			}

			$builder->setOffsetValueType($keyType, $valueType);
		}

		return $builder->getArray();
	}

	public function unsetOffset(Type $offsetType): Type
	{
		$offsetType = $offsetType->toArrayKey();
		if ($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType) {
			foreach ($this->keyTypes as $i => $keyType) {
				if ($keyType->getValue() !== $offsetType->getValue()) {
					continue;
				}

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

				return new self($newKeyTypes, $newValueTypes, $this->nextAutoIndexes, $newOptionalKeys, TrinaryLogic::createNo());
			}

			return $this;
		}

		$constantScalars = $offsetType->getConstantScalarTypes();
		if (count($constantScalars) > 0) {
			$optionalKeys = $this->optionalKeys;

			foreach ($constantScalars as $constantScalar) {
				$constantScalar = $constantScalar->toArrayKey();
				if (!$constantScalar instanceof ConstantIntegerType && !$constantScalar instanceof ConstantStringType) {
					continue;
				}

				foreach ($this->keyTypes as $i => $keyType) {
					if ($keyType->getValue() !== $constantScalar->getValue()) {
						continue;
					}

					if (in_array($i, $optionalKeys, true)) {
						continue 2;
					}

					$optionalKeys[] = $i;
				}
			}

			return new self($this->keyTypes, $this->valueTypes, $this->nextAutoIndexes, $optionalKeys, TrinaryLogic::createNo());
		}

		$optionalKeys = $this->optionalKeys;
		$isList = $this->isList;
		foreach ($this->keyTypes as $i => $keyType) {
			if (!$offsetType->isSuperTypeOf($keyType)->yes()) {
				continue;
			}
			$optionalKeys[] = $i;
			$isList = TrinaryLogic::createNo();
		}
		$optionalKeys = array_values(array_unique($optionalKeys));

		return new self($this->keyTypes, $this->valueTypes, $this->nextAutoIndexes, $optionalKeys, $isList);
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		$biggerOne = IntegerRangeType::fromInterval(1, null);
		$finiteTypes = $lengthType->getFiniteTypes();
		if ($biggerOne->isSuperTypeOf($lengthType)->yes() && count($finiteTypes) < self::CHUNK_FINITE_TYPES_LIMIT) {
			$results = [];
			foreach ($finiteTypes as $finiteType) {
				if (!$finiteType instanceof ConstantIntegerType || $finiteType->getValue() < 1) {
					return $this->traitChunkArray($lengthType, $preserveKeys);
				}

				$length = $finiteType->getValue();

				$builder = ConstantArrayTypeBuilder::createEmpty();

				$keyTypesCount = count($this->keyTypes);
				for ($i = 0; $i < $keyTypesCount; $i += $length) {
					$chunk = $this->sliceArray(new ConstantIntegerType($i), new ConstantIntegerType($length), TrinaryLogic::createYes());
					$builder->setOffsetValueType(null, $preserveKeys->yes() ? $chunk : $chunk->getValuesArray());
				}

				$results[] = $builder->getArray();
			}

			return TypeCombinator::union(...$results);
		}

		return $this->traitChunkArray($lengthType, $preserveKeys);
	}

	public function fillKeysArray(Type $valueType): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		foreach ($this->valueTypes as $i => $keyType) {
			if ($keyType->isInteger()->no()) {
				$stringKeyType = $keyType->toString();
				if ($stringKeyType instanceof ErrorType) {
					return $stringKeyType;
				}

				$builder->setOffsetValueType($stringKeyType, $valueType, $this->isOptionalKey($i));
			} else {
				$builder->setOffsetValueType($keyType, $valueType, $this->isOptionalKey($i));
			}
		}

		return $builder->getArray();
	}

	public function flipArray(): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		foreach ($this->keyTypes as $i => $keyType) {
			$valueType = $this->valueTypes[$i];
			$builder->setOffsetValueType(
				$valueType->toArrayKey(),
				$keyType,
				$this->isOptionalKey($i),
			);
		}

		return $builder->getArray();
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		foreach ($this->keyTypes as $i => $keyType) {
			$valueType = $this->valueTypes[$i];
			$has = $otherArraysType->hasOffsetValueType($keyType);
			if ($has->no()) {
				continue;
			}
			$builder->setOffsetValueType($keyType, $valueType, $this->isOptionalKey($i) || !$has->yes());
		}

		return $builder->getArray();
	}

	public function popArray(): Type
	{
		return $this->removeLastElements(1);
	}

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		for ($i = count($this->keyTypes) - 1; $i >= 0; $i--) {
			$offsetType = $preserveKeys->yes() || $this->keyTypes[$i]->isInteger()->no()
				? $this->keyTypes[$i]
				: null;
			$builder->setOffsetValueType($offsetType, $this->valueTypes[$i], $this->isOptionalKey($i));
		}

		return $builder->getArray();
	}

	public function searchArray(Type $needleType): Type
	{
		$matches = [];
		$hasIdenticalValue = false;

		foreach ($this->valueTypes as $index => $valueType) {
			$isNeedleSuperType = $valueType->isSuperTypeOf($needleType);
			if ($isNeedleSuperType->no()) {
				continue;
			}

			if ($needleType instanceof ConstantScalarType && $valueType instanceof ConstantScalarType
				&& $needleType->getValue() === $valueType->getValue()
				&& !$this->isOptionalKey($index)
			) {
				$hasIdenticalValue = true;
			}

			$matches[] = $this->keyTypes[$index];
		}

		if (count($matches) > 0) {
			if ($hasIdenticalValue) {
				return TypeCombinator::union(...$matches);
			}

			return TypeCombinator::union(new ConstantBooleanType(false), ...$matches);
		}

		return new ConstantBooleanType(false);
	}

	public function shiftArray(): Type
	{
		return $this->removeFirstElements(1);
	}

	public function shuffleArray(): Type
	{
		$valuesArray = $this->getValuesArray();

		$isIterableAtLeastOnce = $valuesArray->isIterableAtLeastOnce();
		if ($isIterableAtLeastOnce->no()) {
			return $valuesArray;
		}

		$generalizedArray = new ArrayType($valuesArray->getIterableKeyType(), $valuesArray->getIterableValueType());

		if ($isIterableAtLeastOnce->yes()) {
			$generalizedArray = TypeCombinator::intersect($generalizedArray, new NonEmptyArrayType());
		}
		if ($valuesArray->isList->yes()) {
			$generalizedArray = TypeCombinator::intersect($generalizedArray, new AccessoryArrayListType());
		}

		return $generalizedArray;
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			return $this;
		}

		$offset = $offsetType instanceof ConstantIntegerType ? $offsetType->getValue() : 0;
		$length = $lengthType instanceof ConstantIntegerType ? $lengthType->getValue() : $keyTypesCount;

		if ($length < 0) {
			// Negative lengths prevent access to the most right n elements
			return $this->removeLastElements($length * -1)
				->sliceArray($offsetType, new NullType(), $preserveKeys);
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
			 * with offset -4 and length 2 (which would be sliced to array{b: 1, c: 2})
			 *
			 * is transformed via reversion to
			 *
			 * array{e: 4, d: 3, c: 2, b: 1, a: 0}
			 * with offset 2 and length 2 (which will be sliced to array{c: 2, b: 1} and then reversed again)
			 */
			$offset *= -1;
			$reversedLength = min($length, $offset);
			$reversedOffset = $offset - $reversedLength;
			return $this->reverseArray(TrinaryLogic::createYes())
				->sliceArray(new ConstantIntegerType($reversedOffset), new ConstantIntegerType($reversedLength), $preserveKeys)
				->reverseArray(TrinaryLogic::createYes());
		}

		if ($offset > 0) {
			return $this->removeFirstElements($offset, false)
				->sliceArray(new ConstantIntegerType(0), $lengthType, $preserveKeys);
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();

		$nonOptionalElementsCount = 0;
		$hasOptional = false;
		for ($i = 0; $nonOptionalElementsCount < $length && $i < $keyTypesCount; $i++) {
			$isOptional = $this->isOptionalKey($i);
			if (!$isOptional) {
				$nonOptionalElementsCount++;
			} else {
				$hasOptional = true;
			}

			$isLastElement = $nonOptionalElementsCount >= $length || $i + 1 >= $keyTypesCount;
			if ($isLastElement && $length < $keyTypesCount && $hasOptional) {
				// If the slice is not full yet, but has at least one optional key
				// the last non-optional element is going to be optional.
				// Otherwise, it would not fit into the slice if previous non-optional keys are there.
				$isOptional = true;
			}

			$offsetType = $preserveKeys->yes() || $this->keyTypes[$i]->isInteger()->no()
				? $this->keyTypes[$i]
				: null;

			$builder->setOffsetValueType($offsetType, $this->valueTypes[$i], $isOptional);
		}

		return $builder->getArray();
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

	public function getArraySize(): Type
	{
		$optionalKeysCount = count($this->optionalKeys);
		$totalKeysCount = count($this->getKeyTypes());
		if ($optionalKeysCount === 0) {
			return new ConstantIntegerType($totalKeysCount);
		}

		return IntegerRangeType::fromInterval($totalKeysCount - $optionalKeysCount, $totalKeysCount);
	}

	public function getFirstIterableKeyType(): Type
	{
		$keyTypes = [];
		foreach ($this->keyTypes as $i => $keyType) {
			$keyTypes[] = $keyType;
			if (!$this->isOptionalKey($i)) {
				break;
			}
		}

		return TypeCombinator::union(...$keyTypes);
	}

	public function getLastIterableKeyType(): Type
	{
		$keyTypes = [];
		for ($i = count($this->keyTypes) - 1; $i >= 0; $i--) {
			$keyTypes[] = $this->keyTypes[$i];
			if (!$this->isOptionalKey($i)) {
				break;
			}
		}

		return TypeCombinator::union(...$keyTypes);
	}

	public function getFirstIterableValueType(): Type
	{
		$valueTypes = [];
		foreach ($this->valueTypes as $i => $valueType) {
			$valueTypes[] = $valueType;
			if (!$this->isOptionalKey($i)) {
				break;
			}
		}

		return TypeCombinator::union(...$valueTypes);
	}

	public function getLastIterableValueType(): Type
	{
		$valueTypes = [];
		for ($i = count($this->keyTypes) - 1; $i >= 0; $i--) {
			$valueTypes[] = $this->valueTypes[$i];
			if (!$this->isOptionalKey($i)) {
				break;
			}
		}

		return TypeCombinator::union(...$valueTypes);
	}

	public function isConstantArray(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isList(): TrinaryLogic
	{
		return $this->isList;
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
		$nextAutoindexes = $this->nextAutoIndexes;

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
				$nextAutoindexes = $removedKeyType instanceof ConstantIntegerType
					? [$removedKeyType->getValue()]
					: $this->nextAutoIndexes;
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
			$nextAutoindexes,
			array_values($optionalKeys),
			$this->isList,
		);
	}

	/** @param positive-int $length */
	private function removeFirstElements(int $length, bool $reindex = true): Type
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

		return $builder->getArray();
	}

	public function toBoolean(): BooleanType
	{
		return $this->getArraySize()->toBoolean();
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
			$this->getIterableKeyType()->generalize($precision),
			$this->getIterableValueType()->generalize($precision),
		);

		$keyTypesCount = count($this->keyTypes);
		$optionalKeysCount = count($this->optionalKeys);

		$accessoryTypes = [];
		if ($precision->isMoreSpecific() && ($keyTypesCount - $optionalKeysCount) < 32) {
			foreach ($this->keyTypes as $i => $keyType) {
				if ($this->isOptionalKey($i)) {
					continue;
				}

				$accessoryTypes[] = new HasOffsetValueType($keyType, $this->valueTypes[$i]->generalize($precision));
			}
		} elseif ($keyTypesCount > $optionalKeysCount) {
			$accessoryTypes[] = new NonEmptyArrayType();
		}

		if ($this->isList()->yes()) {
			$arrayType = TypeCombinator::intersect($arrayType, new AccessoryArrayListType());
		}

		if (count($accessoryTypes) > 0) {
			return TypeCombinator::intersect($arrayType, ...$accessoryTypes);
		}

		return $arrayType;
	}

	public function generalizeValues(): self
	{
		$valueTypes = [];
		foreach ($this->valueTypes as $valueType) {
			$valueTypes[] = $valueType->generalize(GeneralizePrecision::lessSpecific());
		}

		return new self($this->keyTypes, $valueTypes, $this->nextAutoIndexes, $this->optionalKeys, $this->isList);
	}

	public function getKeysArray(): self
	{
		return $this->getKeysOrValuesArray($this->keyTypes);
	}

	public function getValuesArray(): self
	{
		return $this->getKeysOrValuesArray($this->valueTypes);
	}

	/**
	 * @param array<int, Type> $types
	 */
	private function getKeysOrValuesArray(array $types): self
	{
		$count = count($types);
		$autoIndexes = range($count - count($this->optionalKeys), $count);
		assert($autoIndexes !== []);

		if ($this->isList->yes()) {
			// Optimized version for lists: Assume that if a later key exists, then earlier keys also exist.
			$keyTypes = array_map(
				static fn (int $i): ConstantIntegerType => new ConstantIntegerType($i),
				array_keys($types),
			);
			return new self($keyTypes, $types, $autoIndexes, $this->optionalKeys, TrinaryLogic::createYes());
		}

		$keyTypes = [];
		$valueTypes = [];
		$optionalKeys = [];
		$maxIndex = 0;

		foreach ($types as $i => $type) {
			$keyTypes[] = new ConstantIntegerType($i);

			if ($this->isOptionalKey($maxIndex)) {
				// move $maxIndex to next non-optional key
				do {
					$maxIndex++;
				} while ($maxIndex < $count && $this->isOptionalKey($maxIndex));
			}

			if ($i === $maxIndex) {
				$valueTypes[] = $type;
			} else {
				$valueTypes[] = TypeCombinator::union(...array_slice($types, $i, $maxIndex - $i + 1));
				if ($maxIndex >= $count) {
					$optionalKeys[] = $i;
				}
			}
			$maxIndex++;
		}

		return new self($keyTypes, $valueTypes, $autoIndexes, $optionalKeys, TrinaryLogic::createYes());
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
					if (str_contains($keyDescription, '"')) {
						$keyDescription = sprintf('\'%s\'', $keyDescription);
					} elseif (str_contains($keyDescription, '\'')) {
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
			fn (): string => $this->isIterableAtLeastOnce()->no() ? 'array' : sprintf('array<%s, %s>', $this->getIterableKeyType()->describe($level), $this->getIterableValueType()->describe($level)),
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

		if ($receivedType->isArray()->yes()) {
			$keyTypeMap = $this->getIterableKeyType()->inferTemplateTypes($receivedType->getIterableKeyType());
			$itemTypeMap = $this->getIterableValueType()->inferTemplateTypes($receivedType->getIterableValueType());

			return $keyTypeMap->union($itemTypeMap);
		}

		return TemplateTypeMap::createEmpty();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());
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

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove->isConstantArray()->yes() && $typeToRemove->isIterableAtLeastOnce()->no()) {
			return TypeCombinator::intersect($this, new NonEmptyArrayType());
		}

		if ($typeToRemove instanceof NonEmptyArrayType) {
			return new ConstantArrayType([], []);
		}

		if ($typeToRemove instanceof HasOffsetType) {
			return $this->unsetOffset($typeToRemove->getOffsetType());
		}

		if ($typeToRemove instanceof HasOffsetValueType) {
			return $this->unsetOffset($typeToRemove->getOffsetType());
		}

		return null;
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

		return new self($this->keyTypes, $valueTypes, $this->nextAutoIndexes, $this->optionalKeys, $this->isList);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right->isArray()->yes()) {
			return $this;
		}

		$valueTypes = [];

		$stillOriginal = true;
		foreach ($this->valueTypes as $i => $valueType) {
			$keyType = $this->keyTypes[$i];
			$transformedValueType = $cb($valueType, $right->getOffsetValueType($keyType));
			if ($transformedValueType !== $valueType) {
				$stillOriginal = false;
			}

			$valueTypes[] = $transformedValueType;
		}

		if ($stillOriginal) {
			return $this;
		}

		return new self($this->keyTypes, $valueTypes, $this->nextAutoIndexes, $this->optionalKeys, $this->isList);
	}

	public function isKeysSupersetOf(self $otherArray): bool
	{
		$keyTypesCount = count($this->keyTypes);
		$otherKeyTypesCount = count($otherArray->keyTypes);

		if ($keyTypesCount < $otherKeyTypesCount) {
			return false;
		}

		if ($otherKeyTypesCount === 0) {
			return $keyTypesCount === 0;
		}

		$failOnDifferentValueType = $keyTypesCount !== $otherKeyTypesCount || $keyTypesCount < 2;

		$keyTypes = $this->keyTypes;

		foreach ($otherArray->keyTypes as $j => $keyType) {
			$i = self::findKeyIndex($keyType, $keyTypes);
			if ($i === null) {
				return false;
			}

			unset($keyTypes[$i]);

			$valueType = $this->valueTypes[$i];
			$otherValueType = $otherArray->valueTypes[$j];
			if (!$otherValueType->isSuperTypeOf($valueType)->no()) {
				continue;
			}

			if ($failOnDifferentValueType) {
				return false;
			}
			$failOnDifferentValueType = true;
		}

		$requiredKeyCount = 0;
		foreach (array_keys($keyTypes) as $i) {
			if ($this->isOptionalKey($i)) {
				continue;
			}

			$requiredKeyCount++;
			if ($requiredKeyCount > 1) {
				return false;
			}
		}

		return true;
	}

	public function mergeWith(self $otherArray): self
	{
		// only call this after verifying isKeysSupersetOf, or if losing tagged unions is not an issue
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

		$nextAutoIndexes = array_values(array_unique(array_merge($this->nextAutoIndexes, $otherArray->nextAutoIndexes)));
		sort($nextAutoIndexes);

		return new self($this->keyTypes, $valueTypes, $nextAutoIndexes, $optionalKeys, $this->isList->and($otherArray->isList));
	}

	/**
	 * @param ConstantIntegerType|ConstantStringType $otherKeyType
	 */
	private function getKeyIndex($otherKeyType): ?int
	{
		return self::findKeyIndex($otherKeyType, $this->keyTypes);
	}

	/**
	 * @param ConstantIntegerType|ConstantStringType $otherKeyType
	 * @param array<int, ConstantIntegerType|ConstantStringType> $keyTypes
	 */
	private static function findKeyIndex($otherKeyType, array $keyTypes): ?int
	{
		foreach ($keyTypes as $i => $keyType) {
			if ($keyType->equals($otherKeyType)) {
				return $i;
			}
		}

		return null;
	}

	public function makeOffsetRequired(Type $offsetType): self
	{
		$offsetType = $offsetType->toArrayKey();
		$optionalKeys = $this->optionalKeys;
		foreach ($this->keyTypes as $i => $keyType) {
			if (!$keyType->equals($offsetType)) {
				continue;
			}

			foreach ($optionalKeys as $j => $key) {
				if ($i === $key) {
					unset($optionalKeys[$j]);
					return new self($this->keyTypes, $this->valueTypes, $this->nextAutoIndexes, array_values($optionalKeys), $this->isList);
				}
			}

			break;
		}

		return $this;
	}

	public function toPhpDocNode(): TypeNode
	{
		$items = [];
		$values = [];
		$exportValuesOnly = true;
		foreach ($this->keyTypes as $i => $keyType) {
			if ($keyType->getValue() !== $i) {
				$exportValuesOnly = false;
			}
			$keyPhpDocNode = $keyType->toPhpDocNode();
			if (!$keyPhpDocNode instanceof ConstTypeNode) {
				continue;
			}
			$valueType = $this->valueTypes[$i];

			/** @var ConstExprStringNode|ConstExprIntegerNode $keyNode */
			$keyNode = $keyPhpDocNode->constExpr;
			if ($keyNode instanceof ConstExprStringNode) {
				$value = $keyNode->value;
				if (self::isValidIdentifier($value)) {
					$keyNode = new IdentifierTypeNode($value);
				}
			}

			$isOptional = $this->isOptionalKey($i);
			if ($isOptional) {
				$exportValuesOnly = false;
			}
			$items[] = new ArrayShapeItemNode(
				$keyNode,
				$isOptional,
				$valueType->toPhpDocNode(),
			);
			$values[] = new ArrayShapeItemNode(
				null,
				$isOptional,
				$valueType->toPhpDocNode(),
			);
		}

		return ArrayShapeNode::createSealed($exportValuesOnly ? $values : $items);
	}

	public static function isValidIdentifier(string $value): bool
	{
		$result = Strings::match($value, '~^(?:[\\\\]?+[a-z_\\x80-\\xFF][0-9a-z_\\x80-\\xFF-]*+)++$~si');

		return $result !== null;
	}

	public function getFiniteTypes(): array
	{
		$arraysArraysForCombinations = [];
		$count = 0;
		foreach ($this->getAllArrays() as $array) {
			$values = $array->getValueTypes();
			$arraysForCombinations = [];
			$combinationCount = 1;
			foreach ($values as $valueType) {
				$finiteTypes = $valueType->getFiniteTypes();
				if ($finiteTypes === []) {
					return [];
				}
				$arraysForCombinations[] = $finiteTypes;
				$combinationCount *= count($finiteTypes);
			}
			$arraysArraysForCombinations[] = $arraysForCombinations;
			$count += $combinationCount;
		}

		if ($count > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return [];
		}

		$finiteTypes = [];
		foreach ($arraysArraysForCombinations as $arraysForCombinations) {
			$combinations = CombinationsHelper::combinations($arraysForCombinations);
			foreach ($combinations as $combination) {
				$builder = ConstantArrayTypeBuilder::createEmpty();
				foreach ($combination as $i => $v) {
					$builder->setOffsetValueType($this->keyTypes[$i], $v);
				}
				$finiteTypes[] = $builder->getArray();
			}
		}

		return $finiteTypes;
	}

}
