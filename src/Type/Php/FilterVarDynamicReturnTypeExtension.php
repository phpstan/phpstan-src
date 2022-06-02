<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
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
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
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
		$exactType = $this->determineExactType($inputType, $filterValue);
		if ($exactType !== null) {
			$type = $exactType;
		} else {
			$type = $this->getFilterTypeMap()[$filterValue] ?? $mixedType;
			$otherType = $this->getOtherType($flagsArg, $scope);

			if ($inputType->isNonEmptyString()->yes()
				&& $type instanceof StringType
				&& !$this->canStringBeSanitized($filterValue, $flagsArg, $scope)) {
				$type = new IntersectionType([$type, new AccessoryNonEmptyStringType()]);
			}

			if ($otherType->isSuperTypeOf($type)->no()) {
				$type = new UnionType([$type, $otherType]);
			}
		}

		if ($this->hasFlag($this->getConstant('FILTER_FORCE_ARRAY'), $flagsArg, $scope)) {
			return new ArrayType(new MixedType(), $type);
		}

		return $type;
	}

	private function determineExactType(Type $in, int $filterValue): ?Type
	{
		if (($filterValue === $this->getConstant('FILTER_VALIDATE_BOOLEAN') && $in instanceof BooleanType)
			|| ($filterValue === $this->getConstant('FILTER_VALIDATE_INT') && $in instanceof IntegerType)
			|| ($filterValue === $this->getConstant('FILTER_VALIDATE_FLOAT') && $in instanceof FloatType)) {
			return $in;
		}

		if ($filterValue === $this->getConstant('FILTER_VALIDATE_FLOAT') && $in instanceof IntegerType) {
			return $in->toFloat();
		}

		return null;
	}

	private function getOtherType(?Node\Arg $flagsArg, Scope $scope): Type
	{
		$falseType = new ConstantBooleanType(false);
		if ($flagsArg === null) {
			return $falseType;
		}

		$defaultType = $this->getDefault($flagsArg, $scope);
		if ($defaultType !== null) {
			return $defaultType;
		}

		if ($this->hasFlag($this->getConstant('FILTER_NULL_ON_FAILURE'), $flagsArg, $scope)) {
			return new NullType();
		}

		return $falseType;
	}

	private function getDefault(Node\Arg $expression, Scope $scope): ?Type
	{
		$exprType = $scope->getType($expression->value);
		if (!$exprType instanceof ConstantArrayType) {
			return null;
		}

		$optionsType = $exprType->getOffsetValueType(new ConstantStringType('options'));
		if (!$optionsType instanceof ConstantArrayType) {
			return null;
		}

		$defaultType = $optionsType->getOffsetValueType(new ConstantStringType('default'));
		if (!$defaultType instanceof ErrorType) {
			return $defaultType;
		}

		return null;
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
