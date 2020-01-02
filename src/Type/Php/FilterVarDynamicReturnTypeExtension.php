<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class FilterVarDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @var array<int, Type> */
	private $filterTypesHashMaps;

	/** @var array<int, Type> */
	private $nullableTypes;

	/** @var ConstantStringType */
	private $flagsString;

	public function __construct()
	{
		if (!defined('FILTER_SANITIZE_EMAIL')) {
			return;
		}

		$booleanType = new BooleanType();
		$floatType = new FloatType();
		$intType = new IntegerType();
		$stringType = new StringType();

		$nullType = new NullType();
		$falseType = new ConstantBooleanType(false);

		$nullableBooleanType = new UnionType([$booleanType, $nullType]);
		$floatOrFalseType = new UnionType([$floatType, $falseType]);
		$nullableFloatType = new UnionType([$floatType, $nullType]);
		$intOrFalseType = new UnionType([$intType, $falseType]);
		$nullableIntType = new UnionType([$intType, $nullType]);
		$stringOrFalseType = new UnionType([$stringType, $falseType]);
		$nullableStringType = new UnionType([$stringType, $nullType]);

		$this->filterTypesHashMaps = [
			FILTER_SANITIZE_EMAIL => $stringOrFalseType,
			FILTER_SANITIZE_ENCODED => $stringOrFalseType,
			FILTER_SANITIZE_MAGIC_QUOTES => $stringOrFalseType,
			FILTER_SANITIZE_NUMBER_FLOAT => $stringOrFalseType,
			FILTER_SANITIZE_NUMBER_INT => $stringOrFalseType,
			FILTER_SANITIZE_SPECIAL_CHARS => $stringOrFalseType,
			FILTER_SANITIZE_STRING => $stringOrFalseType,
			FILTER_SANITIZE_URL => $stringOrFalseType,
			FILTER_VALIDATE_BOOLEAN => $booleanType,
			FILTER_VALIDATE_EMAIL => $stringOrFalseType,
			FILTER_VALIDATE_FLOAT => $floatOrFalseType,
			FILTER_VALIDATE_INT => $intOrFalseType,
			FILTER_VALIDATE_IP => $stringOrFalseType,
			FILTER_VALIDATE_MAC => $stringOrFalseType,
			FILTER_VALIDATE_REGEXP => $stringOrFalseType,
			FILTER_VALIDATE_URL => $stringOrFalseType,
		];

		$this->nullableTypes = [
			FILTER_VALIDATE_BOOLEAN => $nullableBooleanType,
			FILTER_VALIDATE_EMAIL => $nullableStringType,
			FILTER_VALIDATE_FLOAT => $nullableFloatType,
			FILTER_VALIDATE_INT => $nullableIntType,
			FILTER_VALIDATE_IP => $nullableStringType,
			FILTER_VALIDATE_MAC => $nullableStringType,
			FILTER_VALIDATE_REGEXP => $nullableStringType,
			FILTER_VALIDATE_URL => $nullableStringType,
		];

		$this->flagsString = new ConstantStringType('flags');
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return defined('FILTER_SANITIZE_EMAIL') && strtolower($functionReflection->getName()) === 'filter_var';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$mixedType = new MixedType();

		$filterArg = $functionCall->args[1] ?? null;
		if ($filterArg === null) {
			return $mixedType;
		}

		$filterType = $scope->getType($filterArg->value);
		if (!$filterType instanceof ConstantIntegerType) {
			return $mixedType;
		}

		$filterValue = $filterType->getValue();

		$flagsArg = $functionCall->args[2] ?? null;
		if ($this->isNullableType($filterValue, $flagsArg, $scope)) {
			$type = $this->nullableTypes[$filterValue];
		} else {
			$type = $this->filterTypesHashMaps[$filterValue] ?? $mixedType;
		}

		if ($this->isForcedArrayType($flagsArg, $scope)) {
			return new ArrayType(new MixedType(), $type);
		}

		return $type;
	}

	private function isNullableType(int $filterValue, ?Node\Arg $flagsArg, Scope $scope): bool
	{
		if ($flagsArg === null || !array_key_exists($filterValue, $this->nullableTypes)) {
			return false;
		}

		return $this->hasFlag(FILTER_NULL_ON_FAILURE, $flagsArg, $scope);
	}

	private function isForcedArrayType(?Node\Arg $flagsArg, Scope $scope): bool
	{
		if ($flagsArg === null) {
			return false;
		}

		return $this->hasFlag(FILTER_FORCE_ARRAY, $flagsArg, $scope);
	}

	private function hasFlag(int $flag, Node\Arg $expression, Scope $scope): bool
	{
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

}
