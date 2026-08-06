<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use Nette\Utils\Strings;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\DependencyInjection\BleedingEdgeToggle;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeUnsealedTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\Callables\FunctionCallableVariant;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\InaccessibleMethod;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Rules\Arrays\AllowedArrayKeysTypes;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateStrictMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\RecursionGuard;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\StrictMixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Traits\ArrayTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use PHPStan\Type\Traverser\UnsafeArrayStringKeyCastingTraverser;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
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
use function is_int;
use function is_string;
use function max;
use function min;
use function pow;
use function range;
use function sort;
use function sprintf;
use function str_contains;
use function strtolower;
use function strtoupper;
use function usort;
use const CASE_LOWER;
use const CASE_UPPER;

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
	private const UNSEALED_ARRAY_SHAPES_LINK = 'https://phpstan.org/blog/phpstan-2-2-unsealed-array-shapes-safer-array-keys';

	private TrinaryLogic $isList;

	/** @var array{Type, Type}|null */
	private ?array $unsealed; // phpcs:ignore

	/** @var self[]|null */
	private ?array $allArrays = null;

	private ?Type $iterableKeyType = null;

	private ?Type $iterableValueType = null;

	private ?Type $keyTypesUnion = null;

	/** @var array<int|string, int>|null */
	private ?array $keyIndexMap = null;

	/**
	 * @api
	 * @param list<ConstantIntegerType|ConstantStringType> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param list<int> $nextAutoIndexes
	 * @param int[] $optionalKeys
	 * @param array{Type, Type}|null $unsealed
	 */
	public function __construct(
		private array $keyTypes,
		private array $valueTypes,
		private array $nextAutoIndexes = [0],
		private array $optionalKeys = [],
		?TrinaryLogic $isList = null,
		?array $unsealed = null,
	)
	{
		assert(count($keyTypes) === count($valueTypes));

		// Fill in `$isList` from the shape when the caller didn't pass one.
		// For empty CATs the answer derives from the unsealed key type
		// (no explicit keys to inspect); for non-empty ones the default
		// is `No` and the caller is expected to assert list-ness via
		// `makeList()` if appropriate.
		if ($isList === null) {
			if (count($this->keyTypes) === 0) {
				if ($unsealed === null) {
					$isList = TrinaryLogic::createYes();
				} else {
					[$unsealedKeyType] = $unsealed;
					if ($unsealedKeyType instanceof NeverType && $unsealedKeyType->isExplicit()) {
						$isList = TrinaryLogic::createYes();
					} elseif ($unsealedKeyType->isInteger()->yes()) {
						$isList = TrinaryLogic::createMaybe();
					} else {
						$isList = TrinaryLogic::createNo();
					}
				}
			} else {
				$isList = TrinaryLogic::createNo();
			}
		}
		$this->isList = $isList;

		if ($unsealed !== null) {
			// Only a BenevolentUnionType describes with the surrounding parentheses of
			// '(int|string)' / '(int|non-decimal-int-string)', so skip the describe() call
			// for every other key type.
			if ($unsealed[0] instanceof BenevolentUnionType && in_array($unsealed[0]->describe(VerbosityLevel::value()), ['(int|string)', '(int|non-decimal-int-string)'], true)) {
				$unsealed[0] = new MixedType();
			}
			if ($unsealed[0] instanceof StrictMixedType && !$unsealed[0] instanceof TemplateStrictMixedType) {
				$unsealed[0] = (new UnionType([new StringType(), new IntegerType()]))->toArrayKey();
			}
			if ($unsealed[0] instanceof NeverType && $unsealed[0]->isExplicit()) {
				$unsealed[1] = new NeverType(true);
			}
		} elseif (BleedingEdgeToggle::isBleedingEdge()) {
			$never = new NeverType(true);
			$unsealed = [$never, $never];
		}
		$this->unsealed = $unsealed;
	}

	public function isSealed(): TrinaryLogic
	{
		return $this->isUnsealed()->negate();
	}

	public function isUnsealed(): TrinaryLogic
	{
		$unsealed = $this->unsealed;
		if ($unsealed === null) {
			return TrinaryLogic::createMaybe();
		}

		[$keyType] = $unsealed;

		return TrinaryLogic::createFromBoolean(!$keyType instanceof NeverType || !$keyType->isExplicit());
	}

	/**
	 * @phpstan-pure
	 * @return array{Type, Type}|null
	 */
	public function getUnsealedTypes(): ?array
	{
		return $this->unsealed;
	}

	/**
	 * @internal
	 */
	public function dropUnsealedTypes(): self
	{
		return $this->recreate(
			$this->keyTypes,
			$this->valueTypes,
			$this->nextAutoIndexes,
			$this->optionalKeys,
			$this->isList,
			null,
		);
	}

	/**
	 * @param list<ConstantIntegerType|ConstantStringType> $keyTypes
	 * @param array<int, Type> $valueTypes
	 * @param list<int> $nextAutoIndexes
	 * @param int[] $optionalKeys
	 * @param array{Type, Type}|null $unsealed
	 */
	protected function recreate(
		array $keyTypes,
		array $valueTypes,
		array $nextAutoIndexes,
		array $optionalKeys,
		?TrinaryLogic $isList,
		?array $unsealed,
	): self
	{
		return new self($keyTypes, $valueTypes, $nextAutoIndexes, $optionalKeys, $isList, $unsealed);
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

		if ($this->unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $this->unsealed;
			foreach ($unsealedKeyType->getReferencedClasses() as $referencedClass) {
				$referencedClasses[] = $referencedClass;
			}
			foreach ($unsealedValueType->getReferencedClasses() as $referencedClass) {
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

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			$unsealedKeyType = $this->unsealed[0];
			if ($unsealedKeyType instanceof MixedType && !$unsealedKeyType instanceof TemplateMixedType) {
				$unsealedKeyType = (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey();
			} elseif ($unsealedKeyType instanceof StrictMixedType && !$unsealedKeyType instanceof TemplateStrictMixedType) {
				$unsealedKeyType = (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey();
			}
			$keyType = TypeCombinator::union($keyType, $unsealedKeyType);
		}

		return $this->iterableKeyType = UnsafeArrayStringKeyCastingTraverser::castKeyType($keyType);
	}

	public function getIterableValueType(): Type
	{
		if ($this->iterableValueType !== null) {
			return $this->iterableValueType;
		}

		$valueType = count($this->valueTypes) > 0 ? TypeCombinator::union(...$this->valueTypes) : new NeverType(true);
		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			$valueType = TypeCombinator::union($valueType, $this->unsealed[1]);
		}

		return $this->iterableValueType = $valueType;
	}

	private function getKeyTypesUnion(): Type
	{
		return $this->keyTypesUnion ??= count($this->keyTypes) > 0
			? TypeCombinator::union(...$this->keyTypes)
			: new NeverType();
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
		if ($this->isUnsealed()->yes()) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createYes();
	}

	/**
	 * @return list<int>
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
				array_slice($this->optionalKeys, 0, 1, true),
				array_slice($this->optionalKeys, -1, 1, true),
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

			if (count($keys) === 0 && $this->isUnsealed()->yes() && $this->unsealed !== null) {
				// Variant with no explicit keys but real unsealed extras: the
				// builder's getArray() would degrade this to a general
				// ArrayType. Construct the CAT directly so the variant keeps
				// its extras for downstream consumers (e.g. flattenTypes).
				$arrays[] = new ConstantArrayType([], [], unsealed: $this->unsealed);
				continue;
			}

			$builder = ConstantArrayTypeBuilder::createEmpty();
			$builder->disableArrayDegradation();
			foreach ($keys as $i) {
				$builder->setOffsetValueType($this->keyTypes[$i], $this->valueTypes[$i]);
			}
			if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
				$builder->makeUnsealed($this->unsealed[0], $this->unsealed[1]);
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
	 * @return list<ConstantIntegerType|ConstantStringType>
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

	public function sortKeys(): self
	{
		$indices = array_keys($this->keyTypes);
		usort($indices, fn (int $a, int $b): int => $this->keyTypes[$a]->getValue() <=> $this->keyTypes[$b]->getValue());

		$newKeyTypes = [];
		$newValueTypes = [];
		$indexMap = [];
		foreach ($indices as $newIdx => $oldIdx) {
			$newKeyTypes[] = $this->keyTypes[$oldIdx];
			$newValueTypes[] = $this->valueTypes[$oldIdx];
			$indexMap[$oldIdx] = $newIdx;
		}

		$newOptionalKeys = [];
		foreach ($this->optionalKeys as $oldIdx) {
			$newOptionalKeys[] = $indexMap[$oldIdx];
		}
		sort($newOptionalKeys);

		return $this->recreate(
			$newKeyTypes,
			$newValueTypes,
			$this->nextAutoIndexes,
			$newOptionalKeys,
			$this->isList,
			$this->unsealed,
		);
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType && !$type instanceof IntersectionType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		$isUnsealed = $this->isUnsealed();
		if (!$isUnsealed->yes()) {
			if ($type instanceof self && count($this->keyTypes) === 0) {
				return AcceptsResult::createFromBoolean(count($type->keyTypes) === 0);
			}
		}

		$result = $this->checkOurKeys($type, $strictTypes)->and(new AcceptsResult($type->isArray(), []));
		if ($this->unsealed === null) {
			if ($type->isOversizedArray()->yes()) {
				if (!$result->no()) {
					return AcceptsResult::createYes();
				}
			}

			return $result;
		}

		if ($result->no()) {
			return $result;
		}

		[$unsealedKeyType, $unsealedValueType] = $this->unsealed;

		if ($isUnsealed->no()) {
			if (!$type->isConstantArray()->yes()) {
				return $result->and(AcceptsResult::createNo([
					'Sealed array shape can only accept a constant array. Extra keys are not allowed.',
				]));
			}

			$constantArrays = $type->getConstantArrays();
			if (count($constantArrays) !== 1) {
				throw new ShouldNotHappenException('Type with more than one constant array occurred, should have been eliminated with `instanceof CompoundType` above.');
			}

			$keys = [];
			foreach ($constantArrays[0]->getKeyTypes() as $otherKeyType) {
				$keys[$otherKeyType->getValue()] = $otherKeyType;
			}

			foreach ($this->keyTypes as $keyType) {
				unset($keys[$keyType->getValue()]);
			}

			foreach ($keys as $extraKey) {
				$result = $result->and(AcceptsResult::createNo([
					sprintf('Sealed array shape does not accept array with extra key %s.', $extraKey->describe(VerbosityLevel::precise())),
				]));
			}

			if (!$constantArrays[0]->isUnsealed()->no()) {
				$result = $result->and(AcceptsResult::createNo([
					'Sealed array shape does not accept unsealed array shape.',
				]));
			}

			return $result;
		}

		if (!$type->isConstantArray()->yes()) {
			return $result->and($unsealedKeyType->accepts($type->getIterableKeyType(), $strictTypes))
				->and($unsealedValueType->accepts($type->getIterableValueType(), $strictTypes));
		}

		$constantArrays = $type->getConstantArrays();
		if (count($constantArrays) !== 1) {
			throw new ShouldNotHappenException('Type with more than one constant array occurred, should have been eliminated with `instanceof CompoundType` above.');
		}

		$keys = [];
		$constantArray = $constantArrays[0];
		foreach ($constantArray->getKeyTypes() as $i => $otherKeyType) {
			$keys[$otherKeyType->getValue()] = [$i, $otherKeyType];
		}

		foreach ($this->keyTypes as $keyType) {
			unset($keys[$keyType->getValue()]);
		}

		foreach ($keys as [$i, $extraKeyType]) {
			$acceptsKey = $unsealedKeyType->accepts($extraKeyType, $strictTypes)->decorateReasons(
				static fn (string $reason) => sprintf(
					'Unsealed array key type %s does not accept extra key type %s: %s',
					$unsealedKeyType->describe(VerbosityLevel::value()),
					$extraKeyType->describe(VerbosityLevel::value()),
					$reason,
				),
			);
			if (!$acceptsKey->yes() && count($acceptsKey->reasons) === 0) {
				$acceptsKey = new AcceptsResult($acceptsKey->result, [
					sprintf(
						'Unsealed array key type %s does not accept extra key type %s.',
						$unsealedKeyType->describe(VerbosityLevel::value()),
						$extraKeyType->describe(VerbosityLevel::value()),
					),
				]);
			}
			$result = $result->and($acceptsKey);

			$extraValueType = $constantArray->getValueTypes()[$i];
			$acceptsValue = $unsealedValueType->accepts($extraValueType, $strictTypes)->decorateReasons(
				static fn (string $reason) => sprintf(
					'Unsealed array value type %s does not accept extra offset %s with value type %s: %s',
					$unsealedValueType->describe(VerbosityLevel::value()),
					$extraKeyType->describe(VerbosityLevel::value()),
					$extraValueType->describe(VerbosityLevel::value()),
					$reason,
				),
			);
			if (!$acceptsValue->yes() && count($acceptsValue->reasons) === 0) {
				$acceptsValue = new AcceptsResult($acceptsValue->result, [
					sprintf(
						'Unsealed array value type %s does not accept extra offset %s with value type %s.',
						$unsealedValueType->describe(VerbosityLevel::value()),
						$extraKeyType->describe(VerbosityLevel::value()),
						$extraValueType->describe(VerbosityLevel::value()),
					),
				]);
			}
			$result = $result->and($acceptsValue);
		}

		$otherUnsealed = $constantArray->unsealed;
		if ($otherUnsealed !== null && !$constantArray->isUnsealed()->no()) {
			[$otherUnsealedKeyType, $otherUnsealedValueType] = $otherUnsealed;

			$acceptsUnsealedKey = $unsealedKeyType->accepts($otherUnsealedKeyType, $strictTypes)->decorateReasons(
				static fn (string $reason) => sprintf(
					'Unsealed array key type %s does not accept unsealed array key type %s: %s',
					$unsealedKeyType->describe(VerbosityLevel::value()),
					$otherUnsealedKeyType->describe(VerbosityLevel::value()),
					$reason,
				),
			);
			if (!$acceptsUnsealedKey->yes() && count($acceptsUnsealedKey->reasons) === 0) {
				$acceptsUnsealedKey = new AcceptsResult($acceptsUnsealedKey->result, [
					sprintf(
						'Unsealed array key type %s does not accept unsealed array key type %s.',
						$unsealedKeyType->describe(VerbosityLevel::value()),
						$otherUnsealedKeyType->describe(VerbosityLevel::value()),
					),
				]);
			}
			$result = $result->and($acceptsUnsealedKey);

			$acceptsUnsealedValue = $unsealedValueType->accepts($otherUnsealedValueType, $strictTypes)->decorateReasons(
				static fn (string $reason) => sprintf(
					'Unsealed array value type %s does not accept unsealed array value type %s: %s',
					$unsealedValueType->describe(VerbosityLevel::value()),
					$otherUnsealedValueType->describe(VerbosityLevel::value()),
					$reason,
				),
			);
			if (!$acceptsUnsealedValue->yes() && count($acceptsUnsealedValue->reasons) === 0) {
				$acceptsUnsealedValue = new AcceptsResult($acceptsUnsealedValue->result, [
					sprintf(
						'Unsealed array value type %s does not accept unsealed array value type %s.',
						$unsealedValueType->describe(VerbosityLevel::value()),
						$otherUnsealedValueType->describe(VerbosityLevel::value()),
					),
				]);
			}
			$result = $result->and($acceptsUnsealedValue);
		}

		return $result;
	}

	private function checkOurKeys(Type $type, bool $strictTypes): AcceptsResult
	{
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

		return $result;
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			$thisUnsealedness = $this->isUnsealed();
			$typeUnsealedness = $type->isUnsealed();
			$bothDefinite = $this->unsealed !== null && $type->unsealed !== null;

			if (count($this->keyTypes) === 0) {
				if (!$bothDefinite) {
					return new IsSuperTypeOfResult($type->isIterableAtLeastOnce()->negate(), []);
				}
				if ($thisUnsealedness->no()) {
					return new IsSuperTypeOfResult($type->isIterableAtLeastOnce()->negate(), []);
				}
				// $this is unsealed with no known keys — fall through to extras/unsealed-part checks below
			}

			$results = [];
			foreach ($this->keyTypes as $i => $keyType) {
				$hasOffset = $type->hasOffsetValueType($keyType);
				if ($bothDefinite && $hasOffset->no() && $typeUnsealedness->yes()) {
					[$typeUnsealedKey] = $type->unsealed;
					if (!$typeUnsealedKey->isSuperTypeOf($keyType)->no()) {
						$hasOffset = TrinaryLogic::createMaybe();
					}
				}
				if ($hasOffset->no()) {
					if (!$this->isOptionalKey($i)) {
						if ($thisUnsealedness->no() && $typeUnsealedness->no()) {
							return IsSuperTypeOfResult::createNo(lazyReasons: [fn (): string => $this->sealedArrayShapesCannotBeIntersectedReason($type)]);
						}
						return IsSuperTypeOfResult::createNo();
					}

					$results[] = IsSuperTypeOfResult::createYes();
					continue;
				} elseif ($hasOffset->maybe() && !$this->isOptionalKey($i)) {
					$results[] = IsSuperTypeOfResult::createMaybe();
				}

				$otherValueType = $type->getOffsetValueType($keyType);
				if ($otherValueType instanceof ErrorType && $bothDefinite && $typeUnsealedness->yes()) {
					[, $typeUnsealedValue] = $type->unsealed;
					$otherValueType = $typeUnsealedValue;
				}
				$isValueSuperType = $this->valueTypes[$i]->isSuperTypeOf($otherValueType);
				if ($isValueSuperType->no()) {
					return $isValueSuperType->decorateReasons(static fn (string $reason) => sprintf('Offset %s: %s', $keyType->describe(VerbosityLevel::value()), $reason));
				}
				$results[] = $isValueSuperType;
			}

			if ($bothDefinite) {
				$thisKeyValues = [];
				foreach ($this->keyTypes as $thisKeyType) {
					$thisKeyValues[$thisKeyType->getValue()] = true;
				}

				foreach ($type->getKeyTypes() as $i => $typeKey) {
					if (array_key_exists($typeKey->getValue(), $thisKeyValues)) {
						continue;
					}

					if ($thisUnsealedness->no()) {
						if (!$type->isOptionalKey($i)) {
							if ($typeUnsealedness->no()) {
								return IsSuperTypeOfResult::createNo(lazyReasons: [fn (): string => $this->sealedArrayShapesCannotBeIntersectedReason($type)]);
							}
							return IsSuperTypeOfResult::createNo();
						}
						$results[] = IsSuperTypeOfResult::createMaybe();
						continue;
					}

					[$thisUnsealedKey, $thisUnsealedValue] = $this->unsealed;
					$keyCheck = $thisUnsealedKey->isSuperTypeOf($typeKey);
					if ($keyCheck->no()) {
						if ($type->isOptionalKey($i)) {
							$results[] = IsSuperTypeOfResult::createMaybe();
							continue;
						}
						return IsSuperTypeOfResult::createNo();
					}
					$valueCheck = $thisUnsealedValue->isSuperTypeOf($type->getValueTypes()[$i]);
					if ($valueCheck->no()) {
						if ($type->isOptionalKey($i)) {
							$results[] = IsSuperTypeOfResult::createMaybe();
							continue;
						}
						return IsSuperTypeOfResult::createNo();
					}
					$results[] = $keyCheck->and($valueCheck);
				}

				if ($typeUnsealedness->yes()) {
					if ($thisUnsealedness->no()) {
						$results[] = IsSuperTypeOfResult::createMaybe();
					} else {
						[$thisUnsealedKey, $thisUnsealedValue] = $this->unsealed;
						[$typeUnsealedKey, $typeUnsealedValue] = $type->unsealed;
						$results[] = $thisUnsealedKey->isSuperTypeOf($typeUnsealedKey);
						$results[] = $thisUnsealedValue->isSuperTypeOf($typeUnsealedValue);
					}
				}
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

	/**
	 * Passed as a lazy reason to IsSuperTypeOfResult so the expensive describe() calls only
	 * run when the reason is actually rendered, never during the hot isSuperTypeOf()
	 * comparisons whose reasons are discarded.
	 */
	private function sealedArrayShapesCannotBeIntersectedReason(self $type): string
	{
		return sprintf(
			'Sealed array shapes %s and %s cannot be intersected. Unseal at least one of them with ... syntax. Learn more: %s',
			$this->describe(VerbosityLevel::value()),
			$type->describe(VerbosityLevel::value()),
			self::UNSEALED_ARRAY_SHAPES_LINK,
		);
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

		// Both `unsealed === null` (legacy / pre-bleeding-edge, where
		// `isUnsealed()` answers `Maybe`) and `unsealed === [explicitNever,
		// explicitNever]` (the fresh bleeding-edge sealed marker, where
		// `isUnsealed()` answers `No`) mean "no real extras". Treat them as
		// equivalent here — use `!isUnsealed()->yes()` rather than
		// `isUnsealed()->no()`, otherwise a legacy-null shape and a
		// marker-sealed shape compare unequal. Only compare the actual
		// extras when both sides genuinely have them.
		$thisHasExtras = $this->isUnsealed()->yes();
		$otherHasExtras = $type->isUnsealed()->yes();
		if ($thisHasExtras !== $otherHasExtras) {
			return false;
		}

		if ($thisHasExtras && $this->unsealed !== null && $type->unsealed !== null) {
			if (!$this->unsealed[0]->equals($type->unsealed[0])) {
				return false;
			}
			if (!$this->unsealed[1]->equals($type->unsealed[1])) {
				return false;
			}
		}

		return true;
	}

	public function isCallable(): TrinaryLogic
	{
		$result = RecursionGuard::run($this, function (): TrinaryLogic {
			$hasNonExistentMethod = false;
			$typeAndMethods = $this->doFindTypeAndMethodNames($hasNonExistentMethod);
			if ($typeAndMethods === []) {
				return TrinaryLogic::createNo();
			}

			$results = array_map(
				static fn (ConstantArrayTypeAndMethod $typeAndMethod): TrinaryLogic => $typeAndMethod->getCertainty(),
				$typeAndMethods,
			);

			$result = TrinaryLogic::createYes()->and(...$results);

			if ($hasNonExistentMethod) {
				$result = $result->and(TrinaryLogic::createMaybe());
			}

			return $result;
		});

		if ($result instanceof ErrorType) {
			return TrinaryLogic::createNo();
		}

		return $result;
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

	/** @return ConstantArrayTypeAndMethod[] */
	public function findTypeAndMethodNames(): array
	{
		return $this->doFindTypeAndMethodNames();
	}

	/** @return ConstantArrayTypeAndMethod[] */
	private function doFindTypeAndMethodNames(bool &$hasNonExistentMethod = false): array
	{
		$isUnsealed = $this->isUnsealed()->yes();

		// Sealed: must have exactly the two callable slots, no more, no less.
		// Unsealed: explicit keys may cover 0, 1, both, or neither — but any
		// explicit key outside {0, 1} immediately disqualifies, because the
		// callable shape `[classOrObject, method]` has no room for other
		// keys.
		if (!$isUnsealed && count($this->keyTypes) !== 2) {
			return [];
		}
		if (count($this->keyTypes) > 2) {
			return [];
		}

		$classOrObject = null;
		$method = null;
		foreach ($this->keyTypes as $i => $keyType) {
			if ($keyType->isSuperTypeOf(new ConstantIntegerType(0))->yes()) {
				$classOrObject = $this->valueTypes[$i];
				continue;
			}

			if ($keyType->isSuperTypeOf(new ConstantIntegerType(1))->yes()) {
				$method = $this->valueTypes[$i];
				continue;
			}

			// Explicit key is something other than 0 or 1 — not callable.
			return [];
		}

		// Try to fill missing callable slots from the unsealed extras: an
		// unsealed array `array{0: object, ...<int, string>}` *might* turn
		// into a callable if the actual value carries a `1 => 'method'`
		// extra. Require that the unsealed key range covers the missing
		// slot and that the unsealed value type can overlap with the
		// type required for that slot (object|class-string for key 0,
		// non-falsy-string for key 1) — otherwise no concrete value of
		// this CAT can ever be callable.
		if ($isUnsealed && $this->unsealed !== null) {
			[$unsealedKey, $unsealedValue] = $this->unsealed;

			if ($classOrObject === null) {
				if ($unsealedKey->isSuperTypeOf(new ConstantIntegerType(0))->no()) {
					return [];
				}
				$expected = TypeCombinator::union(new ObjectWithoutClassType(), new ClassStringType());
				if ($expected->isSuperTypeOf($unsealedValue)->no()) {
					return [];
				}
				$classOrObject = $unsealedValue;
			}

			if ($method === null) {
				if ($unsealedKey->isSuperTypeOf(new ConstantIntegerType(1))->no()) {
					return [];
				}
				$expected = TypeCombinator::intersect(new StringType(), new AccessoryNonFalsyStringType());
				if ($expected->isSuperTypeOf($unsealedValue)->no()) {
					return [];
				}
				$method = $unsealedValue;
			}
		}

		if ($classOrObject === null || $method === null) {
			return [];
		}

		$callableArray = [$classOrObject, $method];

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
		foreach ($methods->getConstantStrings() as $methodName) {
			$has = $type->hasMethod($methodName->getValue());
			if ($has->no()) {
				$hasNonExistentMethod = true;
				continue;
			}

			if (
				$has->yes()
				&& !$phpVersion->supportsCallableInstanceMethods()
			) {
				$isString = $classOrObject->isString();
				if ($isString->yes()) {
					$methodReflection = $type->getMethod($methodName->getValue(), new OutOfClassScope());

					if (!$methodReflection->isStatic()) {
						continue;
					}
				} elseif ($isString->maybe()) {
					$has = $has->and(TrinaryLogic::createMaybe());
				}
			}

			if ($this->isOptionalKey(0) || $this->isOptionalKey(1)) {
				$has = $has->and(TrinaryLogic::createMaybe());
			}

			// Unsealed: the actual value may carry extras beyond keys 0/1,
			// which would void the callable shape. The CAT itself describes
			// "zero or more extras", so callable-ness is uncertain.
			if ($isUnsealed) {
				$has = $has->and(TrinaryLogic::createMaybe());
			}

			$typeAndMethods[] = ConstantArrayTypeAndMethod::createConcrete($type, $methodName->getValue(), $has);
		}

		return $typeAndMethods;
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		$offsetArrayKeyType = $offsetType->toArrayKey();
		if ($offsetArrayKeyType instanceof ErrorType) {
			$allowedArrayKeys = AllowedArrayKeysTypes::getType();
			$offsetArrayKeyType = TypeCombinator::intersect($allowedArrayKeys, $offsetType)->toArrayKey();
			if ($offsetArrayKeyType instanceof NeverType) {
				return TrinaryLogic::createNo();
			}
		}

		return $this->recursiveHasOffsetValueType($offsetArrayKeyType);
	}

	private function recursiveHasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		if ($offsetType instanceof UnionType) {
			$results = [];
			foreach ($offsetType->getTypes() as $innerType) {
				$results[] = $this->recursiveHasOffsetValueType($innerType);
			}

			return TrinaryLogic::extremeIdentity(...$results);
		}
		if ($offsetType instanceof IntegerRangeType) {
			$finiteTypes = $offsetType->getFiniteTypes();
			if ($finiteTypes !== []) {
				$results = [];
				foreach ($finiteTypes as $innerType) {
					$results[] = $this->recursiveHasOffsetValueType($innerType);
				}

				return TrinaryLogic::extremeIdentity(...$results);
			}
		}

		$result = TrinaryLogic::createNo();
		foreach ($this->keyTypes as $i => $keyType) {
			// PHP coerces decimal-integer strings to int when used as array
			// keys ("123" → 123), so a non-constant string offset *could* hit
			// a constant-integer slot. Skip the upgrade when the offset is
			// definitely a non-decimal-integer string — those stay as strings
			// and can never collide with an int key.
			if (
				$keyType instanceof ConstantIntegerType
				&& !$offsetType->isString()->no()
				&& $offsetType->isConstantScalarValue()->no()
				&& !$offsetType->isDecimalIntegerString()->no()
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

		// Unsealed extras (zero-or-more additional entries) can never make a
		// hit definite — they're uncertain by construction. They only matter
		// when no explicit key matched ($result is No): if the unsealed key
		// range overlaps the offset, upgrade No → Maybe. Explicit keys take
		// precedence at any slot they cover (PHP keys are unique), so a
		// non-No $result already reflects the strongest answer the unsealed
		// extras could contribute.
		if ($result->no() && $this->isUnsealed()->yes() && $this->unsealed !== null) {
			[$unsealedKeyType] = $this->unsealed;
			if (!$unsealedKeyType->isSuperTypeOf($offsetType)->no()) {
				$result = TrinaryLogic::createMaybe();
			}
		}

		return $result;
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		if (count($this->keyTypes) === 0 && !$this->isUnsealed()->yes()) {
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

		// Unsealed extras describe entries at keys NOT in the explicit set —
		// PHP array keys are unique, so an explicit key fully owns its slot.
		// Only include the unsealed value when the offset has parts not
		// covered by any explicit key AND those parts overlap the unsealed
		// key range.
		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $this->unsealed;
			if (!$this->getKeyTypesUnion()->isSuperTypeOf($offsetType)->yes() && !$unsealedKeyType->isSuperTypeOf($offsetType)->no()) {
				$matchingValueTypes[] = $unsealedValueType;
			}
		}

		if ($all && !$this->isUnsealed()->yes()) {
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
		if ($offsetType === null && count($this->nextAutoIndexes) === 0) {
			return new ErrorType();
		}

		$builder = ConstantArrayTypeBuilder::createFromConstantArray($this);
		$builder->setOffsetValueType($offsetType, $valueType);

		return $builder->getArray();
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		$builder = ConstantArrayTypeBuilder::createFromConstantArray($this);
		$builder->setOffsetValueType($offsetType, $valueType);

		return $builder->getArray();
	}

	/**
	 * Removes or marks as optional the key(s) matching the given offset type from this constant array.
	 *
	 * By default, the method assumes an actual `unset()` call was made, which actively modifies the
	 * array and weakens its list certainty to "maybe". However, in some contexts, such as the else
	 * branch of an array_key_exists() check, the key is statically known to be absent without any
	 * modification, so list certainty should be preserved as-is.
	 */
	public function unsetOffset(Type $offsetType, bool $preserveListCertainty = false): Type
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

				$newIsList = self::isListAfterUnset(
					$newKeyTypes,
					$newOptionalKeys,
					$this->isList,
					in_array($i, $this->optionalKeys, true),
				);
				if (!$preserveListCertainty) {
					$newIsList = $newIsList->and(TrinaryLogic::createMaybe());
				} elseif ($this->isList->yes() && $newIsList->no()) {
					return new NeverType();
				}

				return $this->recreate($newKeyTypes, $newValueTypes, $this->nextAutoIndexes, $newOptionalKeys, $newIsList, $this->unsealed);
			}

			return $this;
		}

		$constantScalars = $offsetType->getConstantScalarTypes();
		if (count($constantScalars) > 0) {
			$optionalKeys = $this->optionalKeys;

			$arrayHasChanged = false;
			foreach ($constantScalars as $constantScalar) {
				$constantScalar = $constantScalar->toArrayKey();
				if (!$constantScalar instanceof ConstantIntegerType && !$constantScalar instanceof ConstantStringType) {
					continue;
				}

				foreach ($this->keyTypes as $i => $keyType) {
					if ($keyType->getValue() !== $constantScalar->getValue()) {
						continue;
					}

					$arrayHasChanged = true;
					if (in_array($i, $optionalKeys, true)) {
						continue 2;
					}

					$optionalKeys[] = $i;
				}
			}

			if (!$arrayHasChanged) {
				return $this;
			}

			$newIsList = self::isListAfterUnset(
				$this->keyTypes,
				$optionalKeys,
				$this->isList,
				count($optionalKeys) === count($this->optionalKeys),
			);
			if (!$preserveListCertainty) {
				$newIsList = $newIsList->and(TrinaryLogic::createMaybe());
			}

			return $this->recreate($this->keyTypes, $this->valueTypes, $this->nextAutoIndexes, $optionalKeys, $newIsList, $this->unsealed);
		}

		$optionalKeys = $this->optionalKeys;
		$arrayHasChanged = false;
		foreach ($this->keyTypes as $i => $keyType) {
			if (!$offsetType->isSuperTypeOf($keyType)->yes()) {
				continue;
			}
			$arrayHasChanged = true;
			$optionalKeys[] = $i;
		}
		$optionalKeys = array_values(array_unique($optionalKeys));

		if (!$arrayHasChanged) {
			return $this;
		}

		$newIsList = self::isListAfterUnset(
			$this->keyTypes,
			$optionalKeys,
			$this->isList,
			count($optionalKeys) === count($this->optionalKeys),
		);
		if (!$preserveListCertainty) {
			$newIsList = $newIsList->and(TrinaryLogic::createMaybe());
		} elseif ($this->isList->yes() && $newIsList->no()) {
			return new NeverType();
		}

		return $this->recreate($this->keyTypes, $this->valueTypes, $this->nextAutoIndexes, $optionalKeys, $newIsList, $this->unsealed);
	}

	/**
	 * Compute the list-ness trinary of a sealed array shape purely from its keys
	 * and their optionality: `yes` if every realization (choice of which optional
	 * keys are present) is a list, `no` if none is, `maybe` otherwise. An optional
	 * key that breaks list-ness only degrades the answer to `maybe`, because the
	 * realization where that key is absent may still be a list.
	 *
	 * Keys are normalized with `toArrayKey()` first, so an integer-like string key
	 * such as `'1'` counts as the integer key `1` it becomes at runtime.
	 *
	 * @param list<ConstantIntegerType|ConstantStringType> $keyTypes
	 * @param int[] $optionalKeys
	 */
	private static function inferIsListFromShape(array $keyTypes, array $optionalKeys): TrinaryLogic
	{
		$optional = [];
		foreach ($optionalKeys as $optionalKey) {
			$optional[$optionalKey] = true;
		}

		// Prefix lengths reachable by realizations that are still a valid list.
		$validLengths = [0 => true];
		$existsInvalid = false;

		foreach ($keyTypes as $i => $keyType) {
			$isOptional = array_key_exists($i, $optional);
			$arrayKeyType = $keyType->toArrayKey();
			$value = $arrayKeyType instanceof ConstantIntegerType ? $arrayKeyType->getValue() : null;

			$newValidLengths = [];
			foreach (array_keys($validLengths) as $length) {
				if ($isOptional) {
					// Skipping the key keeps the realization a valid list prefix.
					$newValidLengths[$length] = true;
				}

				if ($value === $length) {
					// Including the key extends the list into the next slot.
					$newValidLengths[$length + 1] = true;
				} else {
					// Including a non-sequential key yields a non-list realization.
					$existsInvalid = true;
				}
			}

			$validLengths = $newValidLengths;
			if ($validLengths === []) {
				// No realization can be a list from here on.
				return TrinaryLogic::createNo();
			}
		}

		return $existsInvalid ? TrinaryLogic::createMaybe() : TrinaryLogic::createYes();
	}

	/**
	 * When we're unsetting something not on the array, it will be untouched,
	 * So the nextAutoIndexes won't change, and the array might still be a list even with PHPStan definition.
	 *
	 * @param list<ConstantIntegerType|ConstantStringType> $newKeyTypes
	 * @param int[] $newOptionalKeys
	 */
	private static function isListAfterUnset(array $newKeyTypes, array $newOptionalKeys, TrinaryLogic $arrayIsList, bool $unsetOptionalKey): TrinaryLogic
	{
		if (!$unsetOptionalKey || $arrayIsList->no()) {
			return TrinaryLogic::createNo();
		}

		$isListOnlyIfKeysAreOptional = false;
		foreach ($newKeyTypes as $k2 => $newKeyType2) {
			// An integer-like string key such as '1' is the integer key it becomes at
			// runtime, so normalize before deciding whether it continues the list.
			$newKeyType2 = $newKeyType2->toArrayKey();
			if (!$newKeyType2 instanceof ConstantIntegerType || $newKeyType2->getValue() !== $k2) {
				// We found a non-optional key that implies that the array is never a list.
				if (!in_array($k2, $newOptionalKeys, true)) {
					return TrinaryLogic::createNo();
				}

				// The array can still be a list if all the following keys are also optional.
				$isListOnlyIfKeysAreOptional = true;
				continue;
			}

			if ($isListOnlyIfKeysAreOptional && !in_array($k2, $newOptionalKeys, true)) {
				return TrinaryLogic::createNo();
			}
		}

		return $arrayIsList;
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		// With real unsealed extras, we can't precisely enumerate the
		// chunks — the source has an unknown number of extras that
		// could form additional partial or full chunks. Fall back to
		// the general `list<chunk<sourceValues>>` shape produced by
		// the trait, which is correct (just less precise).
		if ($this->isUnsealed()->yes()) {
			return $this->traitChunkArray($lengthType, $preserveKeys);
		}

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

				$builder->setOffsetValueType($stringKeyType, $valueType, $this->isOptionalKey($i) || count($stringKeyType->getConstantScalarTypes()) > 1);
			} else {
				$builder->setOffsetValueType($keyType, $valueType, $this->isOptionalKey($i) || count($keyType->getConstantScalarTypes()) > 1);
			}
		}

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			[, $unsealedValue] = $this->unsealed;
			$tailKey = $unsealedValue->toArrayKey();
			// See flipArray() for the rationale: install the unsealed
			// tail only when its key type is non-finite; otherwise let
			// setOffsetValueType expand it into optional explicit slots
			// (merged with any matching existing keys).
			if (count($tailKey->getFiniteTypes()) === 0) {
				$builder->makeUnsealed($tailKey, $valueType);
			}
			$builder->setOffsetValueType($tailKey, $valueType, true);
		}

		return $builder->getArray();
	}

	public function flipArray(): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		foreach ($this->keyTypes as $i => $keyType) {
			$valueType = $this->valueTypes[$i];
			$offsetType = $valueType->toArrayKey();
			$builder->setOffsetValueType(
				$offsetType,
				$keyType,
				$this->isOptionalKey($i) || count($offsetType->getConstantScalarTypes()) > 1,
			);
		}

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			[$unsealedKey, $unsealedValue] = $this->unsealed;
			$flippedKey = $unsealedValue->toArrayKey();
			$flippedValue = $unsealedKey;
			// For a non-finite tail key (e.g. `string`), install the
			// unsealed extras first; setOffsetValueType then widens any
			// overlapping explicit values with the tail's value type.
			// For a finite tail key (e.g. `0|1`), setOffsetValueType
			// expands the tail into optional explicit slots that fully
			// cover the tail's domain, so no residual unsealed tail is
			// needed.
			if (count($flippedKey->getFiniteTypes()) === 0) {
				$builder->makeUnsealed($flippedKey, $flippedValue);
			}
			$builder->setOffsetValueType($flippedKey, $flippedValue, true);
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

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			[$unsealedKey, $unsealedValue] = $this->unsealed;
			// An unsealed extra at key K survives only if `$other` can
			// also have key K. Narrow the unsealed key to the intersection
			// of our extras-range and `$other`'s key type. If they don't
			// overlap, the unsealed slot is dropped.
			$narrowedKey = TypeCombinator::intersect($unsealedKey, $otherArraysType->getIterableKeyType());
			if (!$narrowedKey instanceof NeverType) {
				$builder->makeUnsealed($narrowedKey, $unsealedValue);
			}
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

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			// `array_reverse` only permutes positions; the unsealed slot
			// is "zero or more extras at unspecified positions" both
			// before and after.
			[$unsealedKey, $unsealedValue] = $this->unsealed;
			$builder->makeUnsealed($unsealedKey, $unsealedValue);
		}

		return $builder->getArray();
	}

	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type
	{
		$strict ??= TrinaryLogic::createMaybe();
		$matches = [];
		$hasIdenticalValue = false;

		foreach ($this->valueTypes as $index => $valueType) {
			if ($strict->yes()) {
				$isNeedleSuperType = $valueType->isSuperTypeOf($needleType);
				if ($isNeedleSuperType->no()) {
					continue;
				}
			}

			if ($needleType instanceof ConstantScalarType && $valueType instanceof ConstantScalarType) {
				// @phpstan-ignore equal.notAllowed
				$isLooseEqual = $needleType->getValue() == $valueType->getValue(); // phpcs:ignore
				if (!$isLooseEqual) {
					continue;
				}
				if (
					($strict->no() || $needleType->getValue() === $valueType->getValue())
					&& !$this->isOptionalKey($index)
				) {
					$hasIdenticalValue = true;
				}
			}

			$matches[] = $this->keyTypes[$index];
		}

		// Unsealed extras can host additional entries beyond the explicit
		// keys, so the search may also find the needle there. The unsealed
		// extras' presence is uncertain by definition (zero or more
		// entries), so they can never make the needle "definitely found"
		// (`hasIdenticalValue` stays false) — `false` always remains a
		// possible result.
		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $this->unsealed;
			$considerUnsealed = true;
			if ($strict->yes()) {
				$considerUnsealed = !$unsealedValueType->isSuperTypeOf($needleType)->no();
			}
			if ($considerUnsealed) {
				$matches[] = $unsealedKeyType;
			}
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
		return $this->getValuesArray()->degradeToGeneralArray();
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			return $this;
		}

		$offset = $offsetType instanceof ConstantIntegerType ? $offsetType->getValue() : null;

		if ($lengthType instanceof ConstantIntegerType) {
			$length = $lengthType->getValue();
		} elseif ($lengthType->isNull()->yes()) {
			$length = $keyTypesCount;
		} else {
			$length = null;
		}

		if ($offset === null || $length === null) {
			return $this->degradeToGeneralArray()
				->sliceArray($offsetType, $lengthType, $preserveKeys);
		}

		if ($keyTypesCount + $offset <= 0) {
			// A negative offset cannot reach left outside the array twice
			$offset = 0;
		}

		if ($keyTypesCount + $length <= 0) {
			// A negative length cannot reach left outside the array twice
			$length = 0;
		}

		if ($length === 0 || ($offset < 0 && $length < 0 && $offset - $length >= 0)) {
			// 0 / 0, 3 / 0 or e.g. -3 / -3 or -3 / -4 and so on never extract anything
			return $this->recreate([], [], [0], [], null, [new NeverType(true), new NeverType(true)]);
		}

		if ($length < 0) {
			// Negative lengths prevent access to the most right n elements
			return $this->removeLastElements($length * -1)
				->sliceArray($offsetType, new NullType(), $preserveKeys);
		}

		if ($offset < 0) {
			/*
			 * Transforms the problem with the negative offset in one with a positive offset using array reversion.
			 * The reason is below handling of optional keys which works only from left to right.
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

		// When the requested length runs past the explicit keys, the
		// missing trailing slots could be filled by the source's
		// unsealed extras (or be absent). Carry the unsealed slot
		// through so the result still describes those potential extras.
		if (
			$this->isUnsealed()->yes()
			&& $this->unsealed !== null
			&& $nonOptionalElementsCount < $length
		) {
			[$unsealedKey, $unsealedValue] = $this->unsealed;
			$builder->makeUnsealed($unsealedKey, $unsealedValue);
		}

		return $builder->getArray();
	}

	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type
	{
		$keyTypesCount = count($this->keyTypes);
		if ($keyTypesCount === 0) {
			return $this;
		}

		$offset = $offsetType instanceof ConstantIntegerType ? $offsetType->getValue() : null;

		if ($lengthType instanceof ConstantIntegerType) {
			$length = $lengthType->getValue();
		} elseif ($lengthType->isNull()->yes()) {
			$length = $keyTypesCount;
		} else {
			$length = null;
		}

		if ($offset === null || $length === null) {
			return $this->degradeToGeneralArray()
				->spliceArray($offsetType, $lengthType, $replacementType);
		}

		$allKeysInteger = $this->getIterableKeyType()->isInteger()->yes();

		if ($keyTypesCount + $offset <= 0) {
			// A negative offset cannot reach left outside the array twice
			$offset = 0;
		}

		if ($keyTypesCount + $length <= 0) {
			// A negative length cannot reach left outside the array twice
			$length = 0;
		}

		$offsetWasNegative = false;
		if ($offset < 0) {
			$offsetWasNegative = true;
			$offset = $keyTypesCount + $offset;
		}

		if ($length < 0) {
			$length = $keyTypesCount - $offset + $length;
		}

		$extractType = $this->sliceArray($offsetType, $lengthType, TrinaryLogic::createYes());

		$types = [];
		foreach ($replacementType->toArray()->getArrays() as $replacementArrayType) {
			$removeKeysCount = 0;
			$optionalKeysBeforeReplacement = 0;

			$builder = ConstantArrayTypeBuilder::createEmpty();
			for ($i = 0;; $i++) {
				$isOptional = $this->isOptionalKey($i);

				if (!$offsetWasNegative && $i < $offset && $isOptional) {
					$optionalKeysBeforeReplacement++;
				}

				if ($i === $offset + $optionalKeysBeforeReplacement) {
					// When the offset is reached we have to a) put the replacement array in and b) remove $length elements
					$removeKeysCount = $length;

					if ($replacementArrayType instanceof self) {
						$valuesArray = $replacementArrayType->getValuesArray();
						for ($j = 0, $jMax = count($valuesArray->keyTypes); $j < $jMax; $j++) {
							$builder->setOffsetValueType(null, $valuesArray->valueTypes[$j], $valuesArray->isOptionalKey($j));
						}
					} else {
						$builder->degradeToGeneralArray();
						$builder->setOffsetValueType($replacementArrayType->getValuesArray()->getIterableKeyType(), $replacementArrayType->getIterableValueType(), true);
					}
				}

				if (!isset($this->keyTypes[$i])) {
					break;
				}

				if ($removeKeysCount > 0) {
					$extractTypeHasOffsetValueType = $extractType->hasOffsetValueType($this->keyTypes[$i]);

					if (
						(!$isOptional && $extractTypeHasOffsetValueType->yes())
						|| ($isOptional && $extractTypeHasOffsetValueType->maybe())
					) {
						$removeKeysCount--;
						continue;
					}
				}

				if (!$isOptional && $extractType->hasOffsetValueType($this->keyTypes[$i])->maybe()) {
					$isOptional = true;
				}

				$builder->setOffsetValueType(
					$this->keyTypes[$i]->isInteger()->no() ? $this->keyTypes[$i] : null,
					$this->valueTypes[$i],
					$isOptional,
				);
			}

			// `array_splice` removes a slice at an explicit offset and
			// inserts a replacement there. Real unsealed extras live at
			// positions past the explicit keys, so they're unaffected
			// by the operation (re-indexing of int keys keeps the
			// `<int, V>` range intact). Carry the slot through.
			if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
				[$unsealedKey, $unsealedValue] = $this->unsealed;
				$builder->makeUnsealed($unsealedKey, $unsealedValue);
			}

			$builtType = $builder->getArray();
			if ($allKeysInteger && !$builtType->isList()->yes()) {
				$builtType = TypeCombinator::intersect($builtType, new AccessoryArrayListType());
			}
			$types[] = $builtType;
		}

		return TypeCombinator::union(...$types);
	}

	public function truncateListToSize(Type $sizeType): Type
	{
		[$min, $max] = self::extractTruncateListBounds($sizeType);

		// `getMin() === null` ↔ unbounded below; the narrowing has no anchor
		// to start from. Also bail out when the required prefix would exceed
		// the array-shape limit — we can't enumerate that many keys.
		// `isList()` is intentionally NOT checked here: the call site
		// (`TypeSpecifier`) only invokes this when the *outer* aggregate is
		// already a list, but a CAT inside a `non-empty-list` intersection
		// may have its own `isList()` weakened to `Maybe`.
		if (
			$min === null
			|| $min >= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT
			|| !$this->getKeyType()->isSuperTypeOf(IntegerRangeType::fromInterval(0, ($max ?? $min) - 1))->yes()
		) {
			return TypeCombinator::intersect($this, new NonEmptyArrayType());
		}

		// Required prefix `[0, $min)`: every value definitely present.
		$builderData = [];
		for ($i = 0; $i < $min; $i++) {
			$offsetType = new ConstantIntegerType($i);
			$builderData[] = [$offsetType, $this->getOffsetValueType($offsetType), false];
		}

		if ($max !== null) {
			// Optional middle `[$min, $max)`.
			if ($max - $min > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
				return TypeCombinator::intersect($this, new NonEmptyArrayType());
			}
			for ($i = $min; $i < $max; $i++) {
				$offsetType = new ConstantIntegerType($i);
				$builderData[] = [$offsetType, $this->getOffsetValueType($offsetType), true];
			}
		} else {
			// Unbounded max: probe explicit keys from `$min` onward until
			// `hasOffsetValueType` answers `no`. Each probe contributes one
			// optional (or required, when `hasOffsetValueType` is `yes`) slot.
			$isUnsealed = $this->isUnsealed()->yes();
			for ($i = $min;; $i++) {
				$offsetType = new ConstantIntegerType($i);
				$hasOffset = $this->hasOffsetValueType($offsetType);
				if ($hasOffset->no()) {
					break;
				}
				// Real unsealed extras make `hasOffsetValueType` answer
				// `Maybe` for *any* in-range key, so the probe would
				// otherwise run until `ARRAY_COUNT_LIMIT` bails (slow +
				// lossy). Stop once the explicit keys are exhausted; the
				// unsealed slot attached below covers further entries.
				if ($isUnsealed && !$hasOffset->yes()) {
					break;
				}
				$builderData[] = [$offsetType, $this->getOffsetValueType($offsetType), !$hasOffset->yes()];
			}
		}

		if (count($builderData) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
			return TypeCombinator::intersect($this, new NonEmptyArrayType());
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($builderData as [$offsetType, $valueType, $optional]) {
			$builder->setOffsetValueType($offsetType, $valueType, $optional);
		}

		// Carry the unsealed slot through only for the unbounded-max
		// branch — a bounded-max range caps the result size and the
		// unsealed extras can't fit.
		if ($max === null && $this->isUnsealed()->yes() && $this->unsealed !== null) {
			$builder->makeUnsealed($this->unsealed[0], $this->unsealed[1]);
		}

		$builtArray = $builder->getArray();
		// `setOffsetValueType` on a brand-new builder produces a list when
		// the resulting offsets are sequential ints — but it may not preserve
		// list-ness in every shape. Reattach it for the single-CAT case.
		if (!$builder->isList()) {
			$constantArrays = $builtArray->getConstantArrays();
			if (count($constantArrays) === 1) {
				$builtArray = $constantArrays[0]->makeList();
			}
		}

		return $builtArray;
	}

	/**
	 * Extracts (min, max) bounds from a size type for `truncateListToSize`.
	 * `ConstantIntegerType(N)` → `[N, N]`. `IntegerRangeType` →
	 * `[$min, $max]`. Anything else returns `[null, null]` and the caller
	 * falls back to the non-precise path.
	 *
	 * @return array{?int, ?int}
	 */
	public static function extractTruncateListBounds(Type $sizeType): array
	{
		if ($sizeType instanceof ConstantIntegerType) {
			return [$sizeType->getValue(), $sizeType->getValue()];
		}

		if ($sizeType instanceof IntegerRangeType) {
			return [$sizeType->getMin(), $sizeType->getMax()];
		}

		return [null, null];
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		$keysCount = count($this->keyTypes);
		if ($keysCount === 0) {
			if (!$this->isUnsealed()->yes()) {
				return TrinaryLogic::createNo();
			}
			return TrinaryLogic::createMaybe();
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
		if (!$this->isUnsealed()->yes()) {
			if ($optionalKeysCount === 0) {
				return new ConstantIntegerType($totalKeysCount);
			}
			$max = $totalKeysCount;
		} else {
			$max = null;
		}

		return IntegerRangeType::fromInterval($totalKeysCount - $optionalKeysCount, $max);
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

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			$unsealedKeyType = $this->unsealed[0];
			if ($unsealedKeyType instanceof MixedType && !$unsealedKeyType instanceof TemplateMixedType) {
				$unsealedKeyType = (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey();
			} elseif ($unsealedKeyType instanceof StrictMixedType && !$unsealedKeyType instanceof TemplateStrictMixedType) {
				$unsealedKeyType = (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey();
			}
			$keyTypes[] = $unsealedKeyType;
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

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			$unsealedKeyType = $this->unsealed[0];
			if ($unsealedKeyType instanceof MixedType && !$unsealedKeyType instanceof TemplateMixedType) {
				$unsealedKeyType = (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey();
			} elseif ($unsealedKeyType instanceof StrictMixedType && !$unsealedKeyType instanceof TemplateStrictMixedType) {
				$unsealedKeyType = (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey();
			}
			$keyTypes[] = $unsealedKeyType;
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

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			$valueTypes[] = $this->unsealed[1];
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

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			$valueTypes[] = $this->unsealed[1];
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

		// With real unsealed extras on the source, the elements being
		// "removed" might come from the unsealed range rather than from
		// the trailing explicit keys — the array might have zero extras
		// (so the trailing explicit keys are popped) or one+ extras (so
		// they're popped instead, leaving the explicit keys intact).
		// Encode this by marking the trailing keys as optional and
		// keeping the unsealed slot in place.
		if ($this->isUnsealed()->yes()) {
			$optionalKeys = $this->optionalKeys;
			$newLength = $keyTypesCount - $length;
			for ($i = $keyTypesCount - 1; $i >= max($newLength, 0); $i--) {
				if (in_array($i, $optionalKeys, true)) {
					continue;
				}
				$optionalKeys[] = $i;
			}

			return $this->recreate(
				$this->keyTypes,
				$this->valueTypes,
				$this->nextAutoIndexes,
				array_values($optionalKeys),
				$this->isList,
				$this->unsealed,
			);
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

		return $this->recreate(
			$keyTypes,
			$valueTypes,
			$nextAutoindexes,
			array_values($optionalKeys),
			$this->isList,
			$this->unsealed,
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

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			// `array_shift` removes the *first* element. The explicit
			// keys precede the unsealed extras in insertion order, so
			// the shift always lands on an explicit key (when there is
			// one); the unsealed slot is unaffected. Re-indexing of int
			// keys doesn't change the unsealed range — it stays `<int, V>`.
			[$unsealedKey, $unsealedValue] = $this->unsealed;
			$builder->makeUnsealed($unsealedKey, $unsealedValue);
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
		// No explicit keys and no real extras — actually empty, return as-is.
		if (count($this->keyTypes) === 0 && !$this->isUnsealed()->yes()) {
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
		} elseif ($this->isIterableAtLeastOnce()->yes()) {
			// Previously gated on `keyTypesCount > optionalKeysCount`,
			// which mishandles "no explicit keys + real unsealed
			// extras" (`isIterableAtLeastOnce()` answers `Maybe` —
			// extras might be empty — and correctly skips
			// `NonEmptyArrayType`). The new gate also covers the
			// usual sealed-with-required-keys case, so behaviour for
			// existing CAT shapes is unchanged.
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

		$unsealed = $this->unsealed;
		if ($unsealed !== null) {
			[$unsealedKey, $unsealedValue] = $unsealed;
			$unsealed = [$unsealedKey, $unsealedValue->generalize(GeneralizePrecision::lessSpecific())];
		}

		return $this->recreate($this->keyTypes, $valueTypes, $this->nextAutoIndexes, $this->optionalKeys, $this->isList, $unsealed);
	}

	private function degradeToGeneralArray(): Type
	{
		$builder = ConstantArrayTypeBuilder::createFromConstantArray($this);
		$builder->degradeToGeneralArray();

		return $builder->getArray();
	}

	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type
	{
		$keysArray = $this->getKeysOrValuesArray($this->keyTypes, $this->unsealed[0] ?? null);

		return new IntersectionType([
			new ArrayType(
				IntegerRangeType::createAllGreaterThanOrEqualTo(0),
				$keysArray->getIterableValueType(),
			),
			new AccessoryArrayListType(),
		]);
	}

	public function getKeysArray(): self
	{
		return $this->getKeysOrValuesArray($this->keyTypes, $this->unsealed[0] ?? null);
	}

	public function getValuesArray(): self
	{
		return $this->getKeysOrValuesArray($this->valueTypes, $this->unsealed[1] ?? null);
	}

	/**
	 * @param array<int, Type> $types
	 */
	private function getKeysOrValuesArray(array $types, ?Type $unsealedSourceType): self
	{
		$count = count($types);
		$autoIndexes = range($count - count($this->optionalKeys), $count);

		// The result is always a list — the source's keys/values are
		// numbered sequentially. The new unsealed slot (if the source
		// has real extras) describes "zero or more extras at int
		// positions >= 0 whose values are the source's unsealed
		// key/value type". `int<0, max>` is the conventional unsealed
		// key for list-shaped extras; it also enables the short-form
		// `<value>` describe.
		$resultUnsealed = null;
		if ($this->isUnsealed()->yes() && $unsealedSourceType !== null) {
			$resultUnsealed = [IntegerRangeType::createAllGreaterThanOrEqualTo(0), $unsealedSourceType];
		}

		if ($this->isList->yes()) {
			// Optimized version for lists: Assume that if a later key exists, then earlier keys also exist.
			$keyTypes = array_map(
				static fn (int $i): ConstantIntegerType => new ConstantIntegerType($i),
				array_keys($types),
			);
			return $this->recreate($keyTypes, $types, $autoIndexes, $this->optionalKeys, TrinaryLogic::createYes(), $resultUnsealed);
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

		return $this->recreate($keyTypes, $valueTypes, $autoIndexes, $optionalKeys, TrinaryLogic::createYes(), $resultUnsealed);
	}

	public function describe(VerbosityLevel $level): string
	{
		$arrayName = $this->shouldBeDescribedAsAList() ? 'list' : 'array';

		$describeValue = function (bool $truncate) use ($level, $arrayName): string {
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
					} elseif (!self::isValidIdentifier($keyDescription)) {
						$keyDescription = sprintf('\'%s\'', $keyDescription);
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

			if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
				if (count($items) > 0) {
					$append .= ', ';
				}
				$append .= '...';
				$keyDescription = $this->unsealed[0]->describe(VerbosityLevel::precise());
				$isMixedKeyType = $this->unsealed[0] instanceof MixedType && $keyDescription === 'mixed' && !$this->unsealed[0]->isExplicitMixed();
				$isMixedItemType = $this->unsealed[1] instanceof MixedType && $this->unsealed[1]->describe(VerbosityLevel::precise()) === 'mixed' && !$this->unsealed[1]->isExplicitMixed();
				if ($isMixedKeyType || ($this->isList()->yes() && $keyDescription === 'int<0, max>')) {
					if (!$isMixedItemType) {
						$append .= sprintf('<%s>', $this->unsealed[1]->describe($level));
					}
				} else {
					$append .= sprintf('<%s, %s>', $this->unsealed[0]->describe($level), $this->unsealed[1]->describe($level));
				}
			}

			return sprintf(
				'%s{%s%s}',
				$arrayName,
				implode(', ', $exportValuesOnly ? $values : $items),
				$append,
			);
		};
		return $level->handle(
			function () use ($arrayName, $level): string {
				if ($this->isIterableAtLeastOnce()->no()) {
					return $arrayName;
				}
				$keyType = $this->getIterableKeyType();
				// Only a BenevolentUnionType describes with the surrounding parentheses of
				// '(int|string)' / '(int|non-decimal-int-string)', so skip the describe()
				// call for every other key type.
				if ($keyType instanceof BenevolentUnionType && in_array($keyType->describe(VerbosityLevel::value()), ['(int|string)', '(int|non-decimal-int-string)'], true)) {
					return sprintf('%s<%s>', $arrayName, $this->getIterableValueType()->describe($level));
				}
				return sprintf('%s<%s, %s>', $arrayName, $keyType->describe($level), $this->getIterableValueType()->describe($level));
			},
			static fn (): string => $describeValue(true),
			static fn (): string => $describeValue(false),
		);
	}

	private function shouldBeDescribedAsAList(): bool
	{
		if (!$this->isList->yes()) {
			return false;
		}

		if (count($this->optionalKeys) === 0) {
			return false;
		}

		if (count($this->optionalKeys) > 1) {
			return true;
		}

		return $this->optionalKeys[0] !== count($this->keyTypes) - 1;
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

			$unsealed = $this->getUnsealedTypes();
			if ($unsealed !== null) {
				[$unsealedKeyType, $unsealedValueType] = $unsealed;

				// Received's explicit keys not in $this's explicit keys are
				// candidates for matching $this's unsealed extras pattern.
				// Only contribute when the key type matches; mismatched explicit
				// keys are extra entries the parameter wouldn't accept anyway,
				// surfaced by the regular argument-type check.
				$receivedKeyTypes = $receivedType->getKeyTypes();
				$receivedValueTypes = $receivedType->getValueTypes();
				foreach ($receivedKeyTypes as $j => $receivedKeyType) {
					if ($this->hasOffsetValueType($receivedKeyType)->yes()) {
						continue;
					}
					if (!$unsealedKeyType->isSuperTypeOf($receivedKeyType)->yes()) {
						continue;
					}
					$typeMap = $typeMap->union($unsealedKeyType->inferTemplateTypes($receivedKeyType));
					$typeMap = $typeMap->union($unsealedValueType->inferTemplateTypes($receivedValueTypes[$j]));
				}

				// Received's own unsealed extras describe "all the rest" — when
				// the key type doesn't fit $this's unsealed key pattern there
				// is no valid template assignment, so force NEVER.
				$receivedUnsealed = $receivedType->getUnsealedTypes();
				if ($receivedUnsealed !== null) {
					[$receivedUnsealedKey, $receivedUnsealedValue] = $receivedUnsealed;
					if ($unsealedKeyType->isSuperTypeOf($receivedUnsealedKey)->no()) {
						$typeMap = $typeMap->union($unsealedValueType->inferTemplateTypes(new NeverType()));
					} else {
						$typeMap = $typeMap->union($unsealedKeyType->inferTemplateTypes($receivedUnsealedKey));
						$typeMap = $typeMap->union($unsealedValueType->inferTemplateTypes($receivedUnsealedValue));
					}
				}
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

		if ($this->unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $this->unsealed;
			foreach ($unsealedKeyType->getReferencedTemplateTypes($variance) as $reference) {
				$references[] = $reference;
			}
			foreach ($unsealedValueType->getReferencedTemplateTypes($variance) as $reference) {
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

		if ($typeToRemove instanceof HasOffsetValueType) {
			$offsetType = $typeToRemove->getOffsetType();
			$valueTypeToRemove = $typeToRemove->getValueType();

			foreach ($this->keyTypes as $i => $keyType) {
				if ($keyType->getValue() !== $offsetType->getValue()) {
					continue;
				}

				$currentValueType = $this->valueTypes[$i];
				$valueIsSuperType = $valueTypeToRemove->isSuperTypeOf($currentValueType);

				if ($valueIsSuperType->no()) {
					return null;
				}

				if ($valueIsSuperType->yes()) {
					$unsetResult = $this->unsetOffset($offsetType, true);
					// When the source was definitely a list but the post-unset shape
					// definitely isn't (e.g. unsetting a non-optional leading key
					// creates a hole), no value of $this could have lacked the
					// removed key — the subtraction yields the empty set.
					if ($this->isList->yes() && $unsetResult->isList()->no()) {
						return new NeverType();
					}
					return $unsetResult;
				}

				$newValueType = TypeCombinator::remove($currentValueType, $valueTypeToRemove);
				$valueTypes = $this->valueTypes;
				$valueTypes[$i] = $newValueType;

				return $this->recreate(
					$this->keyTypes,
					$valueTypes,
					$this->nextAutoIndexes,
					$this->optionalKeys,
					$this->isList,
					$this->unsealed,
				);
			}

			return null;
		}

		if ($typeToRemove instanceof HasOffsetType) {
			$unsetResult = $this->unsetOffset($typeToRemove->getOffsetType(), true);
			// When the source was definitely a list but the post-unset shape
			// definitely isn't (e.g. unsetting a non-optional leading key
			// creates a hole), no value of $this could have lacked the
			// removed key — the subtraction yields the empty set.
			if ($this->isList->yes() && $unsetResult->isList()->no()) {
				return new NeverType();
			}
			return $unsetResult;
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

		$unsealed = $this->unsealed;
		if ($unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $unsealed;
			$transformedUnsealedValueType = $cb($unsealedValueType);
			if ($transformedUnsealedValueType !== $unsealedValueType) {
				$stillOriginal = false;
				$unsealed = [$unsealedKeyType, $transformedUnsealedValueType];
			}
		}

		if ($stillOriginal) {
			return $this;
		}

		return $this->recreate($this->keyTypes, $valueTypes, $this->nextAutoIndexes, $this->optionalKeys, $this->isList, $unsealed);
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

		$unsealed = $this->unsealed;
		if ($unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $unsealed;
			$transformedUnsealedValueType = $cb($unsealedValueType, $right->getIterableValueType());
			if ($transformedUnsealedValueType !== $unsealedValueType) {
				$stillOriginal = false;
				$unsealed = [$unsealedKeyType, $transformedUnsealedValueType];
			}
		}

		if ($stillOriginal) {
			return $this;
		}

		return $this->recreate($this->keyTypes, $valueTypes, $this->nextAutoIndexes, $this->optionalKeys, $this->isList, $unsealed);
	}

	public function isKeysSupersetOf(self $otherArray): bool
	{
		if ($this->unsealed === null || $otherArray->unsealed === null) {
			return $this->legacyIsKeysSupersetOf($otherArray);
		}

		[$thisUnsealedKey, $thisUnsealedValue] = $this->unsealed;
		[$otherUnsealedKey, $otherUnsealedValue] = $otherArray->unsealed;
		$thisHasExtras = $this->isUnsealed()->yes();
		$otherHasExtras = $otherArray->isUnsealed()->yes();

		$otherHasRequiredKeys = false;
		foreach ($otherArray->keyTypes as $j => $keyType) {
			if ($otherArray->isOptionalKey($j)) {
				continue;
			}
			$otherHasRequiredKeys = true;
			break;
		}

		// Sealed empty $other (no keys, no extras): absorbing it is lossless iff $this
		// already accepts []. i.e., all of $this's known keys are optional. Otherwise
		// merge would add [] as a new instance.
		if (!$otherHasRequiredKeys && !$otherHasExtras && count($otherArray->keyTypes) === 0) {
			foreach ($this->keyTypes as $i => $keyType) {
				if (!$this->isOptionalKey($i)) {
					return false;
				}
			}
			return true;
		}

		// With real unsealed extras on both sides that can absorb each other's
		// required keys, merging is acceptable regardless of which keys overlap.
		if ($thisHasExtras && $otherHasExtras) {
			return true;
		}

		// Asymmetric extras: one side has real extras that can absorb the other's keys.
		if ($thisHasExtras) {
			if ($this->legacyIsKeysSupersetOf($otherArray)) {
				return true;
			}
			foreach ($otherArray->keyTypes as $j => $keyType) {
				if ($otherArray->isOptionalKey($j)) {
					continue;
				}
				if ($thisUnsealedKey->isSuperTypeOf($keyType)->no()) {
					return false;
				}
				if ($thisUnsealedValue->isSuperTypeOf($otherArray->valueTypes[$j])->no()) {
					return false;
				}
			}
			return true;
		}

		if ($otherHasExtras) {
			if ($this->legacyIsKeysSupersetOf($otherArray)) {
				return true;
			}
			foreach ($this->keyTypes as $i => $keyType) {
				if ($this->isOptionalKey($i)) {
					continue;
				}
				if ($otherUnsealedKey->isSuperTypeOf($keyType)->no()) {
					return false;
				}
				if ($otherUnsealedValue->isSuperTypeOf($this->valueTypes[$i])->no()) {
					return false;
				}
			}
			return true;
		}

		// Both sealed: fall back to the legacy key/value shape check.
		return $this->legacyIsKeysSupersetOf($otherArray);
	}

	private function legacyIsKeysSupersetOf(self $otherArray): bool
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

		$keyIndexMap = $this->getKeyIndexMap();
		$otherKeyValues = [];

		foreach ($otherArray->keyTypes as $j => $keyType) {
			$keyValue = $keyType->getValue();
			$i = $keyIndexMap[$keyValue] ?? null;
			if ($i === null) {
				return false;
			}

			$otherKeyValues[$keyValue] = true;

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
		foreach ($this->keyTypes as $i => $keyType) {
			if (isset($otherKeyValues[$keyType->getValue()])) {
				continue;
			}
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
		if ($this->unsealed === null || $otherArray->unsealed === null) {
			return $this->legacyMergeWith($otherArray);
		}

		[$thisUnsealedKey, $thisUnsealedValue] = $this->unsealed;
		[$otherUnsealedKey, $otherUnsealedValue] = $otherArray->unsealed;

		$mergedUnsealedKey = TypeCombinator::union($thisUnsealedKey, $otherUnsealedKey);
		$mergedUnsealedValue = TypeCombinator::union($thisUnsealedValue, $otherUnsealedValue);

		$absorbIntoExtras = static function (Type $keyType, Type $valueType) use (&$mergedUnsealedKey, &$mergedUnsealedValue): void {
			$mergedUnsealedKey = TypeCombinator::union($mergedUnsealedKey, $keyType);
			$mergedUnsealedValue = TypeCombinator::union($mergedUnsealedValue, $valueType);
		};

		$canAbsorb = static function (self $side, Type $keyType, Type $valueType): bool {
			if (!$side->isUnsealed()->yes()) {
				return false;
			}
			if ($side->unsealed === null) {
				return false;
			}
			[$sideUnsealedKey, $sideUnsealedValue] = $side->unsealed;
			if ($sideUnsealedKey->isSuperTypeOf($keyType)->no()) {
				return false;
			}
			if ($sideUnsealedValue->isSuperTypeOf($valueType)->no()) {
				return false;
			}
			return true;
		};

		$keyTypes = [];
		$valueTypes = [];
		$optionalKeys = [];
		$nextAutoIndexes = [0];

		$otherKeyIndexMap = $otherArray->getKeyIndexMap();
		$processed = [];

		foreach ($this->keyTypes as $i => $keyType) {
			$keyValue = $keyType->getValue();
			$processed[$keyValue] = true;
			$valueType = $this->valueTypes[$i];

			if (array_key_exists($keyValue, $otherKeyIndexMap)) {
				$j = $otherKeyIndexMap[$keyValue];
				$otherValueType = $otherArray->valueTypes[$j];
				$mergedValue = TypeCombinator::union($valueType, $otherValueType);
				$optional = $this->isOptionalKey($i) || $otherArray->isOptionalKey($j);

				$keyTypes[] = $keyType;
				$valueTypes[] = $mergedValue;
				if ($optional) {
					$optionalKeys[] = count($keyTypes) - 1;
				}
				continue;
			}

			if ($canAbsorb($otherArray, $keyType, $valueType)) {
				$absorbIntoExtras($keyType, $valueType);
				continue;
			}

			$keyTypes[] = $keyType;
			$valueTypes[] = $valueType;
			$optionalKeys[] = count($keyTypes) - 1;
		}

		foreach ($otherArray->keyTypes as $j => $keyType) {
			$keyValue = $keyType->getValue();
			if (array_key_exists($keyValue, $processed)) {
				continue;
			}
			$valueType = $otherArray->valueTypes[$j];

			if ($canAbsorb($this, $keyType, $valueType)) {
				$absorbIntoExtras($keyType, $valueType);
				continue;
			}

			$keyTypes[] = $keyType;
			$valueTypes[] = $valueType;
			$optionalKeys[] = count($keyTypes) - 1;
		}

		$resultUnsealed = [$mergedUnsealedKey, $mergedUnsealedValue];

		$nextAutoIndexes = array_values(array_unique(array_merge($this->nextAutoIndexes, $otherArray->nextAutoIndexes)));
		sort($nextAutoIndexes);

		$optionalKeys = array_values(array_unique($optionalKeys));

		/** @var list<ConstantIntegerType|ConstantStringType> $keyTypes */
		$keyTypes = $keyTypes;

		// Merging widens keys present in only one side into optional keys, so the
		// result can admit list realizations that neither input did. When the merged
		// extras are the explicit-never sentinel (i.e. no real extras), the result is
		// sealed and its list-ness follows purely from the merged shape. Two pure
		// lists merge into a list (their optional keys are suffix-constrained), so
		// keep `yes` in that case rather than degrading it from the shape.
		$naiveIsList = $this->isList->and($otherArray->isList);
		$mergedIsSealed = $mergedUnsealedKey instanceof NeverType && $mergedUnsealedKey->isExplicit();
		$isList = $mergedIsSealed && !$naiveIsList->yes()
			? self::inferIsListFromShape($keyTypes, $optionalKeys)
			: $naiveIsList;

		return $this->recreate(
			$keyTypes,
			$valueTypes,
			$nextAutoIndexes,
			$optionalKeys,
			$isList,
			$resultUnsealed,
		);
	}

	private function legacyMergeWith(self $otherArray): self
	{
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

		// Merging widens keys present in only one side into optional keys, so the
		// result can admit list realizations that neither input did (e.g. the empty
		// array). When the result carries no real extras it is sealed and its
		// list-ness follows purely from the merged shape, instead of the too-strict
		// `$this->isList->and($otherArray->isList)`. Two pure lists merge into a list
		// (their optional keys are suffix-constrained), so keep `yes` in that case.
		$naiveIsList = $this->isList->and($otherArray->isList);
		$mergedIsSealed = $this->unsealed === null
			|| ($this->unsealed[0] instanceof NeverType && $this->unsealed[0]->isExplicit());
		$isList = $mergedIsSealed && !$naiveIsList->yes()
			? self::inferIsListFromShape($this->keyTypes, $optionalKeys)
			: $naiveIsList;

		return $this->recreate($this->keyTypes, $valueTypes, $nextAutoIndexes, $optionalKeys, $isList, $this->unsealed);
	}

	/**
	 * @return array<int|string, int>
	 */
	private function getKeyIndexMap(): array
	{
		if ($this->keyIndexMap !== null) {
			return $this->keyIndexMap;
		}

		$map = [];
		foreach ($this->keyTypes as $i => $keyType) {
			$map[$keyType->getValue()] = $i;
		}

		return $this->keyIndexMap = $map;
	}

	/**
	 * @param ConstantIntegerType|ConstantStringType $otherKeyType
	 */
	private function getKeyIndex($otherKeyType): ?int
	{
		return $this->getKeyIndexMap()[$otherKeyType->getValue()] ?? null;
	}

	public function makeOffsetRequired(Type $offsetType): self
	{
		$offsetType = $offsetType->toArrayKey();
		$optionalKeys = $this->optionalKeys;
		$isList = $this->isList->yes();
		foreach ($this->keyTypes as $i => $keyType) {
			if (!$keyType->equals($offsetType)) {
				continue;
			}

			$keyValue = $keyType->getValue();
			foreach ($optionalKeys as $j => $key) {
				if (
					$i !== $key
					&& (
						!$isList
						|| !is_int($keyValue)
						|| !is_int($this->keyTypes[$key]->getValue())
						|| $this->keyTypes[$key]->getValue() >= $keyValue
					)
				) {
					continue;
				}

				unset($optionalKeys[$j]);
			}

			if (count($this->optionalKeys) !== count($optionalKeys)) {
				return $this->recreate($this->keyTypes, $this->valueTypes, $this->nextAutoIndexes, array_values($optionalKeys), $this->isList, $this->unsealed);
			}

			return $this;
		}

		// Offset isn't in the explicit set. If the unsealed extras' key range
		// covers it (e.g. `array{a: int, ...<string, float>}` narrowing on
		// `array_key_exists('b', $arr)`), promote it into the explicit set as
		// a required slot with the unsealed value type. The unsealed extras
		// stay around — additional entries at other matching keys are still
		// possible.
		if (
			$this->isUnsealed()->yes()
			&& $this->unsealed !== null
			&& ($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType)
		) {
			[$unsealedKeyType, $unsealedValueType] = $this->unsealed;
			if (!$unsealedKeyType->isSuperTypeOf($offsetType)->no()) {
				$keyTypes = $this->keyTypes;
				$valueTypes = $this->valueTypes;
				$keyTypes[] = $offsetType;
				$valueTypes[] = $unsealedValueType;

				return $this->recreate(
					$keyTypes,
					$valueTypes,
					$this->nextAutoIndexes,
					$this->optionalKeys,
					TrinaryLogic::createNo(),
					$this->unsealed,
				);
			}
		}

		return $this;
	}

	public function makeList(): Type
	{
		if ($this->isList->yes()) {
			return $this;
		}

		if ($this->isList->no()) {
			return new NeverType();
		}

		return $this->recreate($this->keyTypes, $this->valueTypes, $this->nextAutoIndexes, $this->optionalKeys, TrinaryLogic::createYes(), $this->unsealed);
	}

	public function makeListMaybe(): Type
	{
		if (!$this->isList->yes()) {
			return $this;
		}

		return $this->recreate(
			$this->keyTypes,
			$this->valueTypes,
			$this->nextAutoIndexes,
			$this->optionalKeys,
			TrinaryLogic::createMaybe(),
			$this->unsealed,
		);
	}

	public function mapValueType(callable $cb): Type
	{
		$newValueTypes = [];
		foreach ($this->valueTypes as $valueType) {
			$newValueTypes[] = $cb($valueType);
		}

		$newUnsealed = $this->unsealed === null
			? null
			: [$this->unsealed[0], $cb($this->unsealed[1])];

		return $this->recreate(
			$this->keyTypes,
			$newValueTypes,
			$this->nextAutoIndexes,
			$this->optionalKeys,
			$this->isList,
			$newUnsealed,
		);
	}

	public function mapKeyType(callable $cb): Type
	{
		// Constant array shapes already encode precise per-slot keys; a
		// blanket key-type rewrite (the prior `TypeTraverser`-based pattern
		// in `NodeScopeResolver`) would coerce constants into a broader
		// type and lose precision. Pass through unchanged.
		return $this;
	}

	public function makeAllArrayKeysOptional(): Type
	{
		$keyCount = count($this->keyTypes);
		if ($keyCount === 0) {
			return $this;
		}

		return $this->recreate(
			$this->keyTypes,
			$this->valueTypes,
			$this->nextAutoIndexes,
			range(0, $keyCount - 1),
			$this->isList,
			$this->unsealed,
		);
	}

	public function changeKeyCaseArray(?int $case): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($this->keyTypes as $i => $keyType) {
			if ($keyType instanceof ConstantStringType) {
				$newKeyType = self::foldConstantStringKeyCase($keyType, $case);
			} else {
				$newKeyType = $keyType;
			}
			$builder->setOffsetValueType($newKeyType, $this->valueTypes[$i], $this->isOptionalKey($i));
		}

		if ($this->unsealed !== null) {
			$builder->makeUnsealed(self::foldUnsealedKeyCase($this->unsealed[0], $case), $this->unsealed[1]);
		}

		$result = $builder->getArray();
		if ($this->isList()->yes()) {
			$result = TypeCombinator::intersect($result, new AccessoryArrayListType());
		}
		return $result;
	}

	public function filterArrayRemovingFalsey(): Type
	{
		$falseyTypes = StaticTypeFactory::falsey();
		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($this->keyTypes as $i => $keyType) {
			$value = $this->valueTypes[$i];
			$isFalsey = $falseyTypes->isSuperTypeOf($value);
			if ($isFalsey->yes()) {
				continue;
			}
			if ($isFalsey->maybe()) {
				$builder->setOffsetValueType($keyType, TypeCombinator::remove($value, $falseyTypes), true);
				continue;
			}
			$builder->setOffsetValueType($keyType, $value, $this->isOptionalKey($i));
		}

		if ($this->unsealed !== null) {
			$unsealedValue = TypeCombinator::remove($this->unsealed[1], $falseyTypes);
			if (!$unsealedValue instanceof NeverType) {
				$builder->makeUnsealed($this->unsealed[0], $unsealedValue);
			}
		}

		return $builder->getArray();
	}

	private static function foldConstantStringKeyCase(ConstantStringType $type, ?int $case): Type
	{
		if ($case === CASE_LOWER) {
			return new ConstantStringType(strtolower($type->getValue()));
		}
		if ($case === CASE_UPPER) {
			return new ConstantStringType(strtoupper($type->getValue()));
		}

		return TypeCombinator::union(
			new ConstantStringType(strtolower($type->getValue())),
			new ConstantStringType(strtoupper($type->getValue())),
		);
	}

	private static function foldUnsealedKeyCase(Type $key, ?int $case): Type
	{
		if ($key instanceof ConstantStringType) {
			return self::foldConstantStringKeyCase($key, $case);
		}

		if ($key instanceof UnionType) {
			$folded = [];
			foreach ($key->getTypes() as $innerKey) {
				$folded[] = self::foldUnsealedKeyCase($innerKey, $case);
			}

			return TypeCombinator::union(...$folded);
		}

		// `array_change_key_case` only folds string keys — int keys
		// (e.g. `...<int, ...>`) pass through unchanged.
		if (!$key->isString()->yes()) {
			return $key;
		}

		// Rebuild from a clean `string` plus the non-case accessories that
		// case-folding preserves (length is unchanged, so numeric / non-
		// falsy / non-empty all survive). Any prior lowercase/uppercase
		// accessory is dropped — matches the `ArrayType::changeKeyCaseArray`
		// behavior where `strtoupper(lowercase-string)` reads as
		// `uppercase-string`, not the contradictory intersection.
		$preserved = [new StringType()];
		if ($key->isNumericString()->yes()) {
			$preserved[] = new AccessoryNumericStringType();
		} elseif ($key->isNonFalsyString()->yes()) {
			$preserved[] = new AccessoryNonFalsyStringType();
		} elseif ($key->isNonEmptyString()->yes()) {
			$preserved[] = new AccessoryNonEmptyStringType();
		}

		if ($case === CASE_LOWER) {
			return new IntersectionType([...$preserved, new AccessoryLowercaseStringType()]);
		}
		if ($case === CASE_UPPER) {
			return new IntersectionType([...$preserved, new AccessoryUppercaseStringType()]);
		}

		// `null` (PHP <8.4 / unspecified) yields lower- or upper-case
		// keys; record both as a union.
		return TypeCombinator::union(
			new IntersectionType([...$preserved, new AccessoryLowercaseStringType()]),
			new IntersectionType([...$preserved, new AccessoryUppercaseStringType()]),
		);
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

		if ($this->isUnsealed()->yes() && $this->unsealed !== null) {
			$unsealedKeyTypeDescription = $this->unsealed[0]->describe(VerbosityLevel::precise());
			$isMixedUnsealedKeyType = $this->unsealed[0] instanceof MixedType && $unsealedKeyTypeDescription === 'mixed' && !$this->unsealed[0]->isExplicitMixed();
			$isMixedUnsealedItemType = $this->unsealed[1] instanceof MixedType && $this->unsealed[1]->describe(VerbosityLevel::precise()) === 'mixed' && !$this->unsealed[1]->isExplicitMixed();
			if ($isMixedUnsealedKeyType || ($this->isList()->yes() && $unsealedKeyTypeDescription === 'int<0, max>')) {
				if ($isMixedUnsealedItemType) {
					return ArrayShapeNode::createUnsealed(
						$exportValuesOnly ? $values : $items,
						null,
						$this->shouldBeDescribedAsAList() ? ArrayShapeNode::KIND_LIST : ArrayShapeNode::KIND_ARRAY,
					);
				}

				return ArrayShapeNode::createUnsealed(
					$exportValuesOnly ? $values : $items,
					new ArrayShapeUnsealedTypeNode($this->unsealed[1]->toPhpDocNode(), null),
					$this->shouldBeDescribedAsAList() ? ArrayShapeNode::KIND_LIST : ArrayShapeNode::KIND_ARRAY,
				);
			}

			return ArrayShapeNode::createUnsealed(
				$exportValuesOnly ? $values : $items,
				new ArrayShapeUnsealedTypeNode($this->unsealed[1]->toPhpDocNode(), $this->unsealed[0]->toPhpDocNode()),
				ArrayShapeNode::KIND_ARRAY,
			);
		}

		return ArrayShapeNode::createSealed(
			$exportValuesOnly ? $values : $items,
			$this->shouldBeDescribedAsAList() ? ArrayShapeNode::KIND_LIST : ArrayShapeNode::KIND_ARRAY,
		);
	}

	public static function isValidIdentifier(string $value): bool
	{
		$result = Strings::match($value, '~^(?:[\\\\]?+[a-z_\\x80-\\xFF][0-9a-z_\\x80-\\xFF-]*+)++$~si');

		return $result !== null;
	}

	public function getFiniteTypes(): array
	{
		if ($this->isUnsealed()->yes()) {
			return [];
		}

		$limit = InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;

		// Build finite array types incrementally, processing one key at a time.
		// For optional keys, fork each partial result into with/without variants.
		// This avoids generating 2^N ConstantArrayType objects via getAllArrays().
		/** @var list<ConstantArrayTypeBuilder> $partials */
		$partials = [ConstantArrayTypeBuilder::createEmpty()];

		foreach ($this->keyTypes as $i => $keyType) {
			$finiteValueTypes = $this->valueTypes[$i]->getFiniteTypes();
			if ($finiteValueTypes === []) {
				return [];
			}

			$isOptional = $this->isOptionalKey($i);
			$newPartials = [];

			foreach ($partials as $partial) {
				if ($isOptional) {
					$newPartials[] = clone $partial;
				}
				foreach ($finiteValueTypes as $finiteValueType) {
					$newPartial = clone $partial;
					$newPartial->setOffsetValueType($keyType, $finiteValueType);
					$newPartials[] = $newPartial;
				}
			}

			$partials = $newPartials;
			if (count($partials) > $limit) {
				return [];
			}
		}

		$finiteTypes = [];
		foreach ($partials as $partial) {
			$finiteTypes[] = $partial->getArray();
		}

		return $finiteTypes;
	}

	public function hasTemplateOrLateResolvableType(): bool
	{
		foreach ($this->valueTypes as $valueType) {
			if (!$valueType->hasTemplateOrLateResolvableType()) {
				continue;
			}

			return true;
		}

		foreach ($this->keyTypes as $keyType) {
			if (!$keyType instanceof TemplateType) {
				continue;
			}

			return true;
		}

		if ($this->unsealed !== null) {
			if ($this->unsealed[0]->hasTemplateOrLateResolvableType()) {
				return true;
			}
			if ($this->unsealed[1]->hasTemplateOrLateResolvableType()) {
				return true;
			}
		}

		return false;
	}

}
