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
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class FilterVarDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
	/** @var ConstantStringType */
	private $flagsString;

	/** @var array<int, Type> */
	private $filterTypeMap;

	public function __construct()
	{
		if (!defined('FILTER_SANITIZE_EMAIL')) {
			return;
		}

		$booleanType = new BooleanType();
		$floatType = new FloatType();
		$intType = new IntegerType();
		$stringType = new StringType();

		$this->filterTypeMap = [
			FILTER_SANITIZE_EMAIL => $stringType,
			FILTER_SANITIZE_ENCODED => $stringType,
			FILTER_SANITIZE_NUMBER_FLOAT => $stringType,
			FILTER_SANITIZE_NUMBER_INT => $stringType,
			FILTER_SANITIZE_SPECIAL_CHARS => $stringType,
			FILTER_SANITIZE_STRING => $stringType,
			FILTER_SANITIZE_URL => $stringType,
			FILTER_VALIDATE_BOOLEAN => $booleanType,
			FILTER_VALIDATE_EMAIL => $stringType,
			FILTER_VALIDATE_FLOAT => $floatType,
			FILTER_VALIDATE_INT => $intType,
			FILTER_VALIDATE_IP => $stringType,
			FILTER_VALIDATE_MAC => $stringType,
			FILTER_VALIDATE_REGEXP => $stringType,
			FILTER_VALIDATE_URL => $stringType,
		];

		if (defined('FILTER_SANITIZE_MAGIC_QUOTES')) {
			$this->filterTypeMap[FILTER_SANITIZE_MAGIC_QUOTES] = $stringType;
		}

		if (defined('FILTER_SANITIZE_ADD_SLASHES')) {
			$this->filterTypeMap[FILTER_SANITIZE_ADD_SLASHES] = $stringType;
		}

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

		$type = $this->filterTypeMap[$filterType->getValue()] ?? $mixedType;
		$flagsArg = $functionCall->args[2] ?? null;
		$otherType = $this->getOtherType($flagsArg, $scope);

		if ($otherType->isSuperTypeOf($type)->no()) {
			$type = new UnionType([$type, $otherType]);
		}

		if ($this->hasFlag(FILTER_FORCE_ARRAY, $flagsArg, $scope)) {
			return new ArrayType(new MixedType(), $type);
		}

		return $type;
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

		if ($this->hasFlag(FILTER_NULL_ON_FAILURE, $flagsArg, $scope)) {
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

}
