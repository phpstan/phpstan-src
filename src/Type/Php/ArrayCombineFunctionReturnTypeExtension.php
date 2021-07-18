<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\Accessory\NonEmptyArrayType;

class ArrayCombineFunctionReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	private PhpVersion $phpVersion;

	public function __construct(PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_combine';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->args) < 2) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$firstArg = $functionCall->args[0]->value;
		$secondArg = $functionCall->args[1]->value;

		$keysParamType = $scope->getType($firstArg);
		$valuesParamType = $scope->getType($secondArg);

		if (
			$keysParamType instanceof ConstantArrayType
			&& $valuesParamType instanceof ConstantArrayType
		) {
			$keyTypes = $keysParamType->getValueTypes();
			$valueTypes = $valuesParamType->getValueTypes();

			if (count($keyTypes) !== count($valueTypes)) {
				return new ConstantBooleanType(false);
			}

			$keyTypes = $this->sanitizeConstantArrayKeyTypes($keyTypes);
			if ($keyTypes !== null) {
				return new ConstantArrayType(
					$keyTypes,
					$valueTypes
				);
			}
		}

		$arrayType = new ArrayType(
			$keysParamType instanceof ArrayType ? $keysParamType->getItemType() : new MixedType(),
			$valuesParamType instanceof ArrayType ? $valuesParamType->getItemType() : new MixedType()
		);
		
		if ($keysParamType->isIterableAtLeastOnce() && $valuesParamType->isIterableAtLeastOnce()) {
			$arrayType = TypeCombinator::union($arrayType, new NonEmptyArrayType());
		}

		if ($this->phpVersion->throwsTypeErrorForInternalFunctions()) {
			return $arrayType;
		}

		if ($firstArg instanceof Variable && $secondArg instanceof Variable && $firstArg->name === $secondArg->name) {
			return $arrayType;
		}

		return new UnionType([$arrayType, new ConstantBooleanType(false)]);
	}

	/**
	 * @param array<int, Type> $types
	 *
	 * @return array<int, ConstantIntegerType|ConstantStringType>|null
	 */
	private function sanitizeConstantArrayKeyTypes(array $types): ?array
	{
		$sanitizedTypes = [];

		foreach ($types as $type) {
			if (
				!$type instanceof ConstantIntegerType
				&& !$type instanceof ConstantStringType
			) {
				return null;
			}

			$sanitizedTypes[] = $type;
		}

		return $sanitizedTypes;
	}

}
