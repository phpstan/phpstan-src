<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_fill_keys;
use function array_combine;
use function array_map;
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

		$valueTypesBuilder = ConstantArrayTypeBuilder::createEmpty();
		$inputTypesMap = [];
		$optionalKeys = [];

		if ($filterArgType instanceof ConstantIntegerType) {
			if ($inputConstantArrayType === null) {
				$inputArgType = $inputArgType->getArrays()[0] ?? null;
				$valueType = $this->filterFunctionReturnTypeHelper->getTypeFromFunctionCall($inputArgType->getItemType(), $filterArgType, null);

				if ($addEmpty) {
					$valueType = TypeCombinator::addNull($valueType);
				}

				$arrayType = new ArrayType(
					$inputArgType !== null ? $inputArgType->getKeyType() : new MixedType(),
					$valueType,
				);

				return $inputArgType !== null && $inputArgType->isList()->yes()
					? AccessoryArrayListType::intersectWith($arrayType)
					: $arrayType;
			}

			// Override $add_empty option
			$addEmpty = false;

			$keysType = $inputConstantArrayType;
			$inputKeysList = array_map(static fn($type) => $type->getValue(), $inputConstantArrayType->getKeyTypes());
			$filterTypesMap = array_fill_keys($inputKeysList, $filterArgType);
			$inputTypesMap = array_combine($inputKeysList, $inputConstantArrayType->getValueTypes());
			$optionalKeys = [];
			foreach ($inputConstantArrayType->getOptionalKeys() as $index) {
				if (isset($inputKeysList[$index])) {
					$optionalKeys[] = $inputKeysList[$index];
				}
			}
		} elseif ($filterConstantArrayType === null) {
			if ($inputConstantArrayType === null) {
				$inputArgType = $inputArgType->getArrays()[0] ?? null;
				$valueType = $this->filterFunctionReturnTypeHelper->getTypeFromFunctionCall(new MixedType(), $filterArgType, null);

				return new ArrayType(
					$inputArgType !== null ? $inputArgType->getKeyType() : new MixedType(),
					$addEmpty ? TypeCombinator::addNull($valueType) : $valueType,
				);
			}

			return null;
		} else {
			$keysType = $filterConstantArrayType;
			$filterKeyTypes = $filterConstantArrayType->getKeyTypes();
			$filterKeysList = array_map(static fn($type) => $type->getValue(), $filterKeyTypes);
			$filterTypesMap = array_combine($filterKeysList, $keysType->getValueTypes());

			if ($inputConstantArrayType !== null) {
				$inputKeysList = array_map(static fn($type) => $type->getValue(), $inputConstantArrayType->getKeyTypes());
				$inputTypesMap = array_combine($inputKeysList, $inputConstantArrayType->getValueTypes());

				$optionalKeys = [];
				foreach ($inputConstantArrayType->getOptionalKeys() as $index) {
					if (isset($inputKeysList[$index])) {
						$optionalKeys[] = $inputKeysList[$index];
					}
				}
			} else {
				$optionalKeys = $filterKeysList;
				$inputTypesMap = array_combine(
					$optionalKeys,
					array_map(static fn() => new MixedType(), $filterKeyTypes),
				);
			}
		}

		foreach ($keysType->getKeyTypes() as $keyType) {
			$optional = false;
			$key = $keyType->getValue();
			$inputType = $inputTypesMap[$key] ?? null;
			if ($inputType === null) {
				if ($addEmpty) {
					$valueTypesBuilder->setOffsetValueType($keyType, new NullType());
				}

				continue;
			}

			[$filterType, $flagsType] = $this->parseFilter($filterTypesMap[$key] ?? new MixedType());
			$valueType = $this->filterFunctionReturnTypeHelper->getTypeFromFunctionCall($inputType, $filterType, $flagsType);

			if (in_array($key, $optionalKeys, true)) {
				if ($addEmpty) {
					$valueType = TypeCombinator::addNull($valueType);
				} else {
					$optional = true;
				}
			}

			$valueTypesBuilder->setOffsetValueType($keyType, $valueType, $optional);
		}

		return $valueTypesBuilder->getArray();
	}

	/** @return array{?Type, ?Type} */
	public function parseFilter(Type $type): array
	{
		$constantType = $type->getConstantArrays()[0] ?? null;

		if ($constantType === null) {
			return [$type, null];
		}

		$filterType = null;
		foreach ($constantType->getKeyTypes() as $keyType) {
			if ($keyType->getValue() === 'filter') {
				$filterType = $constantType->getOffsetValueType($keyType)->getConstantScalarTypes()[0] ?? null;
				break;
			}
		}

		return [$filterType, $constantType];
	}

}
