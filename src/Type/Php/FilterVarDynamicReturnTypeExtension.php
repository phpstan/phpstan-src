<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function hexdec;
use function is_int;
use function octdec;
use function preg_match;
use function sprintf;
use function strtolower;

class FilterVarDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/**
	 * All validation filters match 0x100.
	 */
	private const VALIDATION_FILTER_BITMASK = 0x100;

	private ConstantStringType $flagsString;

	/** @var array<int, Type>|null */
	private ?array $filterTypeMap = null;

	/** @var array<int, list<string>>|null */
	private ?array $filterTypeOptions = null;

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
		$this->flagsString = new ConstantStringType('flags');
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
			$this->getConstant('FILTER_VALIDATE_EMAIL') => $stringType,
			$this->getConstant('FILTER_VALIDATE_FLOAT') => $floatType,
			$this->getConstant('FILTER_VALIDATE_INT') => $intType,
			$this->getConstant('FILTER_VALIDATE_IP') => $stringType,
			$this->getConstant('FILTER_VALIDATE_MAC') => $stringType,
			$this->getConstant('FILTER_VALIDATE_REGEXP') => $stringType,
			$this->getConstant('FILTER_VALIDATE_URL') => $stringType,
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

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'filter_var';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$mixedType = new MixedType();

		$filterArg = $functionCall->getArgs()[1] ?? null;
		if ($filterArg === null) {
			$filterValue = $this->getConstant('FILTER_DEFAULT');
		} else {
			$filterType = $scope->getType($filterArg->value);
			if (!$filterType instanceof ConstantIntegerType) {
				return $mixedType;
			}
			$filterValue = $filterType->getValue();
		}

		$flagsArg = $functionCall->getArgs()[2] ?? null;
		$inputType = $scope->getType($functionCall->getArgs()[0]->value);

		$defaultType = $this->hasFlag($this->getConstant('FILTER_NULL_ON_FAILURE'), $flagsArg, $scope)
			? new NullType()
			: new ConstantBooleanType(false);
		$exactType = $this->determineExactType($inputType, $filterValue, $defaultType, $flagsArg, $scope);
		$type = $exactType ?? $this->getFilterTypeMap()[$filterValue] ?? $mixedType;

		$typeOptionNames = $this->getFilterTypeOptions()[$filterValue] ?? [];
		$otherTypes = $this->getOtherTypes($flagsArg, $scope, $typeOptionNames, $defaultType);

		if ($inputType->isNonEmptyString()->yes()
			&& $type->isString()->yes()
			&& !$this->canStringBeSanitized($filterValue, $flagsArg, $scope)) {
			$type = TypeCombinator::intersect($type, new AccessoryNonEmptyStringType());
		}

		if (isset($otherTypes['range'])) {
			if ($type instanceof ConstantScalarType) {
				if ($otherTypes['range']->isSuperTypeOf($type)->no()) {
					$type = $otherTypes['default'];
				}

				unset($otherTypes['default']);
			} else {
				$type = $otherTypes['range'];
			}
		}

		if ($exactType !== null) {
			unset($otherTypes['default']);
		}

		if (isset($otherTypes['default']) && $otherTypes['default']->isSuperTypeOf($type)->no()) {
			$type = TypeCombinator::union($type, $otherTypes['default']);
		}

		if ($this->hasFlag($this->getConstant('FILTER_FORCE_ARRAY'), $flagsArg, $scope)) {
			return new ArrayType(new MixedType(), $type);
		}

		return $type;
	}

	private function determineExactType(Type $in, int $filterValue, Type $defaultType, ?Arg $flagsArg, Scope $scope): ?Type
	{
		if (($filterValue === $this->getConstant('FILTER_VALIDATE_BOOLEAN') && $in instanceof BooleanType)
			|| ($filterValue === $this->getConstant('FILTER_VALIDATE_INT') && $in instanceof IntegerType)
			|| ($filterValue === $this->getConstant('FILTER_VALIDATE_FLOAT') && $in instanceof FloatType)) {
			return $in;
		}

		if ($filterValue === $this->getConstant('FILTER_VALIDATE_INT') && $in instanceof ConstantStringType) {
			$value = $in->getValue();
			$allowOctal = $this->hasFlag($this->getConstant('FILTER_FLAG_ALLOW_OCTAL'), $flagsArg, $scope);
			$allowHex = $this->hasFlag($this->getConstant('FILTER_FLAG_ALLOW_HEX'), $flagsArg, $scope);

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

		if ($filterValue === $this->getConstant('FILTER_VALIDATE_FLOAT') && $in instanceof IntegerType) {
			return $in->toFloat();
		}

		return null;
	}

	/**
	 * @param list<string> $typeOptionNames
	 * @return array{default: Type, range?: Type}
	 */
	private function getOtherTypes(?Node\Arg $flagsArg, Scope $scope, array $typeOptionNames, Type $defaultType): array
	{
		$falseType = new ConstantBooleanType(false);
		if ($flagsArg === null) {
			return ['default' => $falseType];
		}

		$typeOptions = $this->getOptions($flagsArg, $scope, 'default', ...$typeOptionNames);
		$defaultType = $typeOptions['default'] ?? $defaultType;
		$otherTypes = ['default' => $defaultType];
		$range = [];
		if (isset($typeOptions['min_range'])) {
			if ($typeOptions['min_range'] instanceof ConstantScalarType) {
				$range['min'] = $typeOptions['min_range']->getValue();
			} elseif ($typeOptions['min_range'] instanceof IntegerRangeType) {
				$range['min'] = $typeOptions['min_range']->getMin();
			}
		}
		if (isset($typeOptions['max_range'])) {
			if ($typeOptions['max_range'] instanceof ConstantScalarType) {
				$range['max'] = $typeOptions['max_range']->getValue();
			} elseif ($typeOptions['max_range'] instanceof IntegerRangeType) {
				$range['max'] = $typeOptions['max_range']->getMax();
			}
		}

		if (isset($range['min']) || isset($range['max'])) {
			$min = isset($range['min']) && is_int($range['min']) ? $range['min'] : null;
			$max = isset($range['max']) && is_int($range['max']) ? $range['max'] : null;
			$otherTypes['range'] = IntegerRangeType::fromInterval($min, $max);
		}

		return $otherTypes;
	}

	/**
	 * @return array<string, ?Type>
	 */
	private function getOptions(Node\Arg $expression, Scope $scope, string ...$optionNames): array
	{
		$options = [];

		$exprType = $scope->getType($expression->value);
		if (!$exprType instanceof ConstantArrayType) {
			return $options;
		}

		$optionsType = $exprType->getOffsetValueType(new ConstantStringType('options'));
		if (!$optionsType instanceof ConstantArrayType) {
			return $options;
		}

		foreach ($optionNames as $optionName) {
			$type = $optionsType->getOffsetValueType(new ConstantStringType($optionName));
			$options[$optionName] = $type instanceof ErrorType ? null : $type;
		}

		return $options;
	}

	private function hasFlag(int $flag, ?Node\Arg $expression, Scope $scope): bool
	{
		if ($expression === null) {
			return false;
		}

		$type = $this->getFlagsValue($scope->getType($expression->value));

		return $type instanceof ConstantIntegerType && ($type->getValue() & $flag) === $flag;
	}

	private function getFlagsValue(Type $exprType): Type
	{
		if (!$exprType instanceof ConstantArrayType) {
			return $exprType;
		}

		return $exprType->getOffsetValueType($this->flagsString);
	}

	private function canStringBeSanitized(int $filterValue, ?Node\Arg $flagsArg, Scope $scope): bool
	{
		// If it is a validation filter, the string will not be changed
		if (($filterValue & self::VALIDATION_FILTER_BITMASK) !== 0) {
			return false;
		}

		// FILTER_DEFAULT will not sanitize, unless it has FILTER_FLAG_STRIP_LOW,
		// FILTER_FLAG_STRIP_HIGH, or FILTER_FLAG_STRIP_BACKTICK
		if ($filterValue === $this->getConstant('FILTER_DEFAULT')) {
			return $this->hasFlag($this->getConstant('FILTER_FLAG_STRIP_LOW'), $flagsArg, $scope)
				|| $this->hasFlag($this->getConstant('FILTER_FLAG_STRIP_HIGH'), $flagsArg, $scope)
				|| $this->hasFlag($this->getConstant('FILTER_FLAG_STRIP_BACKTICK'), $flagsArg, $scope);
		}

		return true;
	}

}
