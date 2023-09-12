<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_merge;
use function hexdec;
use function is_int;
use function octdec;
use function preg_match;
use function sprintf;
use const PHP_FLOAT_EPSILON;

final class FilterFunctionReturnTypeHelper
{

	/** All validation filters match 0x100. */
	private const VALIDATION_FILTER_BITMASK = 0x100;

	private ConstantStringType $flagsString;

	/** @var array<int, Type>|null */
	private ?array $filterTypeMap = null;

	/** @var array<int, list<string>>|null */
	private ?array $filterTypeOptions = null;

	private ?Type $supportedFilterInputTypes = null;

	public function __construct(private ReflectionProvider $reflectionProvider, private PhpVersion $phpVersion)
	{
		$this->flagsString = new ConstantStringType('flags');
	}

	public function getOffsetValueType(Type $inputType, Type $offsetType, ?Type $filterType, ?Type $flagsType): ?Type
	{
		$inexistentOffsetType = $this->hasFlag($this->getConstant('FILTER_NULL_ON_FAILURE'), $flagsType)
			? new ConstantBooleanType(false)
			: new NullType();

		$hasOffsetValueType = $inputType->hasOffsetValueType($offsetType);
		if ($hasOffsetValueType->no()) {
			return $inexistentOffsetType;
		}

		$filteredType = $this->getType($inputType->getOffsetValueType($offsetType), $filterType, $flagsType);

		return $hasOffsetValueType->maybe()
			? TypeCombinator::union($filteredType, $inexistentOffsetType)
			: $filteredType;
	}

	public function getInputType(Type $typeType, Type $varNameType, ?Type $filterType, ?Type $flagsType): ?Type
	{
		$this->supportedFilterInputTypes ??= TypeCombinator::union(
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_GET'), null)->getValueType(),
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_POST'), null)->getValueType(),
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_COOKIE'), null)->getValueType(),
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_SERVER'), null)->getValueType(),
			$this->reflectionProvider->getConstant(new Node\Name('INPUT_ENV'), null)->getValueType(),
		);

		if (!$typeType->isInteger()->yes() || $this->supportedFilterInputTypes->isSuperTypeOf($typeType)->no()) {
			if ($this->phpVersion->throwsTypeErrorForInternalFunctions()) {
				return new NeverType();
			}

			// Using a null as input mimics pre PHP 8 behaviour where filter_input
			// would return the same as if the offset does not exist
			$inputType = new NullType();
		} else {
			// Pragmatical solution since global expressions are not passed through the scope for performance reasons
			// See https://github.com/phpstan/phpstan-src/pull/2012 for details
			$inputType = new ArrayType(new StringType(), new MixedType());
		}

		return $this->getOffsetValueType($inputType, $varNameType, $filterType, $flagsType);
	}

	public function getType(Type $inputType, ?Type $filterType, ?Type $flagsType): Type
	{
		$mixedType = new MixedType();

		if ($filterType === null) {
			$filterValue = $this->getConstant('FILTER_DEFAULT');
		} else {
			if (!$filterType instanceof ConstantIntegerType) {
				return $mixedType;
			}
			$filterValue = $filterType->getValue();
		}

		if ($flagsType === null) {
			$flagsType = new ConstantIntegerType(0);
		}

		$hasOptions = $this->hasOptions($flagsType);
		$options = $hasOptions->yes() ? $this->getOptions($flagsType, $filterValue) : [];

		$defaultType = $options['default'] ?? ($this->hasFlag($this->getConstant('FILTER_NULL_ON_FAILURE'), $flagsType)
			? new NullType()
			: new ConstantBooleanType(false));

		$inputIsArray = $inputType->isArray();
		$hasRequireArrayFlag = $this->hasFlag($this->getConstant('FILTER_REQUIRE_ARRAY'), $flagsType);
		if ($inputIsArray->no() && $hasRequireArrayFlag) {
			return $defaultType;
		}

		$hasForceArrayFlag = $this->hasFlag($this->getConstant('FILTER_FORCE_ARRAY'), $flagsType);
		if ($inputIsArray->yes() && ($hasRequireArrayFlag || $hasForceArrayFlag)) {
			$inputArrayKeyType = $inputType->getIterableKeyType();
			$inputType = $inputType->getIterableValueType();
		}

		if ($inputType->isScalar()->no() && $inputType->isNull()->no()) {
			return $defaultType;
		}

		$exactType = $this->determineExactType($inputType, $filterValue, $defaultType, $flagsType);
		$type = $exactType ?? $this->getFilterTypeMap()[$filterValue] ?? $mixedType;
		$type = $this->applyRangeOptions($type, $options, $defaultType);

		if ($inputType->isNonEmptyString()->yes()
			&& $type->isString()->yes()
			&& !$this->canStringBeSanitized($filterValue, $flagsType)) {
			$accessory = new AccessoryNonEmptyStringType();
			if ($inputType->isNonFalsyString()->yes()) {
				$accessory = new AccessoryNonFalsyStringType();
			}
			$type = TypeCombinator::intersect($type, $accessory);
		}

		if ($hasRequireArrayFlag) {
			$type = new ArrayType($inputArrayKeyType ?? $mixedType, $type);
		}

		if ($exactType === null || $hasOptions->maybe() || (!$inputType->equals($type) && $inputType->isSuperTypeOf($type)->yes())) {
			if ($defaultType->isSuperTypeOf($type)->no()) {
				$type = TypeCombinator::union($type, $defaultType);
			}
		}

		if (!$hasRequireArrayFlag && $hasForceArrayFlag) {
			return new ArrayType($inputArrayKeyType ?? $mixedType, $type);
		}

		return $type;
	}

	/**
	 * @return array<int, Type>
	 */
	private function getFilterTypeMap(): array
	{
		if ($this->filterTypeMap !== null) {
			return $this->filterTypeMap;
		}

		$booleanType = new BooleanType();
		$floatType = new FloatType();
		$intType = new IntegerType();
		$stringType = new StringType();
		$nonFalsyString = TypeCombinator::intersect($stringType, new AccessoryNonFalsyStringType());

		$this->filterTypeMap = [
			$this->getConstant('FILTER_UNSAFE_RAW') => $stringType,
			$this->getConstant('FILTER_SANITIZE_EMAIL') => $stringType,
			$this->getConstant('FILTER_SANITIZE_ENCODED') => $stringType,
			$this->getConstant('FILTER_SANITIZE_NUMBER_FLOAT') => $stringType,
			$this->getConstant('FILTER_SANITIZE_NUMBER_INT') => $stringType,
			$this->getConstant('FILTER_SANITIZE_SPECIAL_CHARS') => $stringType,
			$this->getConstant('FILTER_SANITIZE_STRING') => $stringType,
			$this->getConstant('FILTER_SANITIZE_URL') => $stringType,
			$this->getConstant('FILTER_VALIDATE_BOOLEAN') => $booleanType,
			$this->getConstant('FILTER_VALIDATE_DOMAIN') => $stringType,
			$this->getConstant('FILTER_VALIDATE_EMAIL') => $nonFalsyString,
			$this->getConstant('FILTER_VALIDATE_FLOAT') => $floatType,
			$this->getConstant('FILTER_VALIDATE_INT') => $intType,
			$this->getConstant('FILTER_VALIDATE_IP') => $nonFalsyString,
			$this->getConstant('FILTER_VALIDATE_MAC') => $nonFalsyString,
			$this->getConstant('FILTER_VALIDATE_REGEXP') => $stringType,
			$this->getConstant('FILTER_VALIDATE_URL') => $nonFalsyString,
		];

		if ($this->reflectionProvider->hasConstant(new Node\Name('FILTER_SANITIZE_MAGIC_QUOTES'), null)) {
			$this->filterTypeMap[$this->getConstant('FILTER_SANITIZE_MAGIC_QUOTES')] = $stringType;
		}

		if ($this->reflectionProvider->hasConstant(new Node\Name('FILTER_SANITIZE_ADD_SLASHES'), null)) {
			$this->filterTypeMap[$this->getConstant('FILTER_SANITIZE_ADD_SLASHES')] = $stringType;
		}

		return $this->filterTypeMap;
	}

	/**
	 * @return array<int, list<string>>
	 */
	private function getFilterTypeOptions(): array
	{
		if ($this->filterTypeOptions !== null) {
			return $this->filterTypeOptions;
		}

		$this->filterTypeOptions = [
			$this->getConstant('FILTER_VALIDATE_INT') => ['min_range', 'max_range'],
			// PHPStan does not yet support FloatRangeType
			// $this->getConstant('FILTER_VALIDATE_FLOAT') => ['min_range', 'max_range'],
		];

		return $this->filterTypeOptions;
	}

	private function getConstant(string $constantName): int
	{
		$constant = $this->reflectionProvider->getConstant(new Node\Name($constantName), null);
		$valueType = $constant->getValueType();
		if (!$valueType instanceof ConstantIntegerType) {
			throw new ShouldNotHappenException(sprintf('Constant %s does not have integer type.', $constantName));
		}

		return $valueType->getValue();
	}

	private function determineExactType(Type $in, int $filterValue, Type $defaultType, ?Type $flagsType): ?Type
	{
		if ($filterValue === $this->getConstant('FILTER_VALIDATE_BOOLEAN')) {
			if ($in->isBoolean()->yes()) {
				return $in;
			}

			if ($in->isNull()->yes()) {
				return $defaultType;
			}
		}

		if ($filterValue === $this->getConstant('FILTER_VALIDATE_FLOAT')) {
			if ($in->isFloat()->yes()) {
				return $in;
			}

			if ($in->isInteger()->yes()) {
				return $in->toFloat();
			}

			if ($in->isTrue()->yes()) {
				return new ConstantFloatType(1);
			}

			if ($in->isFalse()->yes() || $in->isNull()->yes()) {
				return $defaultType;
			}
		}

		if ($filterValue === $this->getConstant('FILTER_VALIDATE_INT')) {
			if ($in->isInteger()->yes()) {
				return $in;
			}

			if ($in->isTrue()->yes()) {
				return new ConstantIntegerType(1);
			}

			if ($in->isFalse()->yes() || $in->isNull()->yes()) {
				return $defaultType;
			}

			if ($in instanceof ConstantFloatType) {
				return $in->getValue() - (int) $in->getValue() <= PHP_FLOAT_EPSILON
					? $in->toInteger()
					: $defaultType;
			}

			if ($in instanceof ConstantStringType) {
				$value = $in->getValue();
				$allowOctal = $this->hasFlag($this->getConstant('FILTER_FLAG_ALLOW_OCTAL'), $flagsType);
				$allowHex = $this->hasFlag($this->getConstant('FILTER_FLAG_ALLOW_HEX'), $flagsType);

				if ($allowOctal && preg_match('/\A0[oO][0-7]+\z/', $value) === 1) {
					$octalValue = octdec($value);
					return is_int($octalValue) ? new ConstantIntegerType($octalValue) : $defaultType;
				}

				if ($allowHex && preg_match('/\A0[xX][0-9A-Fa-f]+\z/', $value) === 1) {
					$hexValue = hexdec($value);
					return is_int($hexValue) ? new ConstantIntegerType($hexValue) : $defaultType;
				}

				return preg_match('/\A[+-]?(?:0|[1-9][0-9]*)\z/', $value) === 1 ? $in->toInteger() : $defaultType;
			}
		}

		if ($filterValue === $this->getConstant('FILTER_DEFAULT')) {
			if (!$this->canStringBeSanitized($filterValue, $flagsType) && $in->isString()->yes()) {
				return $in;
			}

			if ($in->isBoolean()->yes() || $in->isFloat()->yes() || $in->isInteger()->yes() || $in->isNull()->yes()) {
				return $in->toString();
			}
		}

		return null;
	}

	/** @param array<string, ?Type> $typeOptions */
	private function applyRangeOptions(Type $type, array $typeOptions, Type $defaultType): Type
	{
		if (!$type->isInteger()->yes()) {
			return $type;
		}

		$range = [];
		if (isset($typeOptions['min_range'])) {
			if ($typeOptions['min_range'] instanceof ConstantScalarType) {
				$range['min'] = (int) $typeOptions['min_range']->getValue();
			} elseif ($typeOptions['min_range'] instanceof IntegerRangeType) {
				$range['min'] = $typeOptions['min_range']->getMin();
			} else {
				$range['min'] = null;
			}
		}
		if (isset($typeOptions['max_range'])) {
			if ($typeOptions['max_range'] instanceof ConstantScalarType) {
				$range['max'] = (int) $typeOptions['max_range']->getValue();
			} elseif ($typeOptions['max_range'] instanceof IntegerRangeType) {
				$range['max'] = $typeOptions['max_range']->getMax();
			} else {
				$range['max'] = null;
			}
		}

		if (array_key_exists('min', $range) || array_key_exists('max', $range)) {
			$min = $range['min'] ?? null;
			$max = $range['max'] ?? null;
			$rangeType = IntegerRangeType::fromInterval($min, $max);
			$rangeTypeIsSuperType = $rangeType->isSuperTypeOf($type);

			if ($rangeTypeIsSuperType->no()) {
				// e.g. if 9 is filtered with a range of int<17, 19>
				return $defaultType;
			}

			if ($rangeTypeIsSuperType->yes() && !$rangeType->equals($type)) {
				// e.g. if 18 or int<18, 19> are filtered with a range of int<17, 19>
				return $type;
			}

			// Open ranges on either side means that the input is potentially not part of the range
			return $min === null || $max === null ? TypeCombinator::union($rangeType, $defaultType) : $rangeType;
		}

		return $type;
	}

	private function hasOptions(Type $flagsType): TrinaryLogic
	{
		return $flagsType->isArray()
			->and($flagsType->hasOffsetValueType(new ConstantStringType('options')));
	}

	/** @return array<string, ?Type> */
	private function getOptions(Type $flagsType, int $filterValue): array
	{
		$options = [];

		$optionsType = $flagsType->getOffsetValueType(new ConstantStringType('options'));
		if (!$optionsType->isConstantArray()->yes()) {
			return $options;
		}

		$optionNames = array_merge(['default'], $this->getFilterTypeOptions()[$filterValue] ?? []);
		foreach ($optionNames as $optionName) {
			$optionaNameType = new ConstantStringType($optionName);
			if (!$optionsType->hasOffsetValueType($optionaNameType)->yes()) {
				$options[$optionName] = null;
				continue;
			}

			$options[$optionName] = $optionsType->getOffsetValueType($optionaNameType);
		}

		return $options;
	}

	private function hasFlag(int $flag, ?Type $flagsType): bool
	{
		if ($flagsType === null) {
			return false;
		}

		$type = $this->getFlagsValue($flagsType);

		return $type instanceof ConstantIntegerType && ($type->getValue() & $flag) === $flag;
	}

	private function getFlagsValue(Type $exprType): Type
	{
		if (!$exprType->isConstantArray()->yes()) {
			return $exprType;
		}

		return $exprType->getOffsetValueType($this->flagsString);
	}

	private function canStringBeSanitized(int $filterValue, ?Type $flagsType): bool
	{
		// If it is a validation filter, the string will not be changed
		if (($filterValue & self::VALIDATION_FILTER_BITMASK) !== 0) {
			return false;
		}

		// FILTER_DEFAULT will not sanitize, unless it has FILTER_FLAG_STRIP_LOW,
		// FILTER_FLAG_STRIP_HIGH, or FILTER_FLAG_STRIP_BACKTICK
		if ($filterValue === $this->getConstant('FILTER_DEFAULT')) {
			return $this->hasFlag($this->getConstant('FILTER_FLAG_STRIP_LOW'), $flagsType)
				|| $this->hasFlag($this->getConstant('FILTER_FLAG_STRIP_HIGH'), $flagsType)
				|| $this->hasFlag($this->getConstant('FILTER_FLAG_STRIP_BACKTICK'), $flagsType);
		}

		return true;
	}

}
