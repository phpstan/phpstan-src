<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
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

	/** @var array<string, Type> */
	private $filterTypesHashMaps;

	/** @var array<string, Type> */
	private $nullableTypes;

	public function __construct()
	{
		$booleanType = new BooleanType();
		$nullableBooleanType = new UnionType([new BooleanType(), new NullType()]);
		$floatOrFalseType = new UnionType([new FloatType(), new ConstantBooleanType(false)]);
		$nullableFloatType = new UnionType([new FloatType(), new NullType()]);
		$intOrFalseType = new UnionType([new IntegerType(), new ConstantBooleanType(false)]);
		$nullableIntType = new UnionType([new IntegerType(), new NullType()]);
		$stringOrFalseType = new UnionType([new StringType(), new ConstantBooleanType(false)]);
		$nullableStringType = new UnionType([new StringType(), new NullType()]);

		$this->filterTypesHashMaps = [
			'FILTER_SANITIZE_EMAIL' => $stringOrFalseType,
			'FILTER_SANITIZE_ENCODED' => $stringOrFalseType,
			'FILTER_SANITIZE_MAGIC_QUOTES' => $stringOrFalseType,
			'FILTER_SANITIZE_NUMBER_FLOAT' => $stringOrFalseType,
			'FILTER_SANITIZE_NUMBER_INT' => $stringOrFalseType,
			'FILTER_SANITIZE_SPECIAL_CHARS' => $stringOrFalseType,
			'FILTER_SANITIZE_STRING' => $stringOrFalseType,
			'FILTER_SANITIZE_URL' => $stringOrFalseType,
			'FILTER_VALIDATE_BOOLEAN' => $booleanType,
			'FILTER_VALIDATE_EMAIL' => $stringOrFalseType,
			'FILTER_VALIDATE_FLOAT' => $floatOrFalseType,
			'FILTER_VALIDATE_INT' => $intOrFalseType,
			'FILTER_VALIDATE_IP' => $stringOrFalseType,
			'FILTER_VALIDATE_MAC' => $stringOrFalseType,
			'FILTER_VALIDATE_REGEXP' => $stringOrFalseType,
			'FILTER_VALIDATE_URL' => $stringOrFalseType,
		];

		$this->nullableTypes = [
			'FILTER_VALIDATE_BOOLEAN' => $nullableBooleanType,
			'FILTER_VALIDATE_EMAIL' => $nullableStringType,
			'FILTER_VALIDATE_FLOAT' => $nullableFloatType,
			'FILTER_VALIDATE_INT' => $nullableIntType,
			'FILTER_VALIDATE_IP' => $nullableStringType,
			'FILTER_VALIDATE_MAC' => $nullableStringType,
			'FILTER_VALIDATE_REGEXP' => $nullableStringType,
			'FILTER_VALIDATE_URL' => $nullableStringType,
		];
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'filter_var';
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

		$filterExpr = $filterArg->value;
		if (!$filterExpr instanceof ConstFetch) {
			return $mixedType;
		}

		$filterName = (string) $filterExpr->name;

		if ($this->isNullableType($filterName, $functionCall->args[2] ?? null)) {
			return $this->nullableTypes[$filterName];
		}

		return $this->filterTypesHashMaps[$filterName] ?? $mixedType;
	}

	private function isNullableType(string $filterName, ?Node\Arg $thirdArg): bool
	{
		if ($thirdArg === null || !array_key_exists($filterName, $this->nullableTypes)) {
			return false;
		}

		$expr = $thirdArg->value;
		if ($expr instanceof Node\Expr\Array_) {
			foreach ($expr->items as $item) {
				if ($item->key instanceof Node\Scalar\String_ && $item->key->value === 'flags') {
					$expr = $item->value;
					break;
				}
			}
		}
		return $expr instanceof ConstFetch && (string) $expr->name === 'FILTER_NULL_ON_FAILURE';
	}

}
