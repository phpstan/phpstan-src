<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_combine;
use function array_fill_keys;
use function array_map;
use function array_values;
use function count;
use function in_array;
use function strtolower;

class FilterVarArrayDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'filter_var_array';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$inputArgType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($inputArgType->isArray()->no()) {
			return new NeverType();
		}

		$inputConstantArrayType = $inputArgType->getConstantArrays()[0] ?? null;
		$filterArgType = $scope->getType($functionCall->getArgs()[1]->value);
		$filterConstantArrayType = $filterArgType->getConstantArrays()[0] ?? null;
		$addEmptyType = isset($functionCall->getArgs()[2]) ? $scope->getType($functionCall->getArgs()[2]->value) : null;
		$addEmpty = $addEmptyType === null || $addEmptyType->isTrue()->yes();

		$inputTypesMap = [];
		$optionalKeys = [];

		if ($filterArgType instanceof ConstantIntegerType) {
			if ($inputConstantArrayType === null) {
				$isList = $inputArgType->isList()->yes();
				$inputArgType = $inputArgType->getArrays()[0] ?? null;
				$valueType = $this->filterFunctionReturnTypeHelper->getTypeFromFunctionCall(
					$inputArgType === null ? new MixedType() : $inputArgType->getItemType(),
					$filterArgType,
					null,
				);
				$arrayType = new ArrayType(
					$inputArgType !== null ? $inputArgType->getKeyType() : new MixedType(),
					$valueType,
				);

				return $isList ? AccessoryArrayListType::intersectWith($arrayType) : $arrayType;
			}

			// Override $add_empty option
			$addEmpty = false;

			$keysType = $inputConstantArrayType;
			$inputKeysList = array_map(static fn ($type) => $type->getValue(), $inputConstantArrayType->getKeyTypes());
			$filterTypesMap = array_fill_keys($inputKeysList, $filterArgType);
			$inputTypesMap = array_combine($inputKeysList, $inputConstantArrayType->getValueTypes());
			$optionalKeys = [];
			foreach ($inputConstantArrayType->getOptionalKeys() as $index) {
				if (!isset($inputKeysList[$index])) {
					continue;
				}

				$optionalKeys[] = $inputKeysList[$index];
			}
		} elseif ($filterConstantArrayType === null) {
			if ($inputConstantArrayType === null) {
				$isList = $inputArgType->isList()->yes();
				$inputArgType = $inputArgType->getArrays()[0] ?? null;
				$valueType = $this->filterFunctionReturnTypeHelper->getTypeFromFunctionCall($inputArgType ?? new MixedType(), $filterArgType, null);

				$arrayType = new ArrayType(
					$inputArgType !== null ? $inputArgType->getKeyType() : new MixedType(),
					$addEmpty ? TypeCombinator::addNull($valueType) : $valueType,
				);

				return $isList ? AccessoryArrayListType::intersectWith($arrayType) : $arrayType;
			}

			return null;
		} else {
			$keysType = $filterConstantArrayType;
			$filterKeyTypes = array_values($filterConstantArrayType->getKeyTypes());
			$filterKeysList = array_map(static fn ($type) => $type->getValue(), $filterKeyTypes);
			$filterTypesMap = array_combine($filterKeysList, $keysType->getValueTypes());

			if ($inputConstantArrayType !== null) {
				$inputKeysList = array_map(static fn ($type) => $type->getValue(), $inputConstantArrayType->getKeyTypes());
				$inputTypesMap = array_combine($inputKeysList, $inputConstantArrayType->getValueTypes());

				$optionalKeys = [];
				foreach ($inputConstantArrayType->getOptionalKeys() as $index) {
					if (!isset($inputKeysList[$index])) {
						continue;
					}

					$optionalKeys[] = $inputKeysList[$index];
				}
			} else {
				$optionalKeys = $filterKeysList;
				$inputTypesMap = array_fill_keys($optionalKeys, $inputArgType->getArrays()[0]->getItemType());
			}
		}

		return $this->filterFunctionReturnTypeHelper->buildTypeForArray($keysType, $inputTypesMap, $filterTypesMap, $addEmpty, $optionalKeys);
	}

}
