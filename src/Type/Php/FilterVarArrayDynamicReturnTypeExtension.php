<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_combine;
use function array_fill_keys;
use function array_map;
use function count;
use function in_array;
use function strtolower;

final class FilterVarArrayDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper, private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array(strtolower($functionReflection->getName()), ['filter_var_array', 'filter_input_array'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$functionName = strtolower($functionReflection->getName());
		$inputArgType = $scope->getType($functionCall->getArgs()[0]->value);
		$inputConstantArrayType = null;
		if ($functionName === 'filter_var_array') {
			if ($inputArgType->isArray()->no()) {
				return new NeverType();
			}

			$inputConstantArrayType = $inputArgType->getConstantArrays()[0] ?? null;
		} elseif ($functionName === 'filter_input_array') {
			$supportedTypes = TypeCombinator::union(
				$this->reflectionProvider->getConstant(new Node\Name('INPUT_GET'), null)->getValueType(),
				$this->reflectionProvider->getConstant(new Node\Name('INPUT_POST'), null)->getValueType(),
				$this->reflectionProvider->getConstant(new Node\Name('INPUT_COOKIE'), null)->getValueType(),
				$this->reflectionProvider->getConstant(new Node\Name('INPUT_SERVER'), null)->getValueType(),
				$this->reflectionProvider->getConstant(new Node\Name('INPUT_ENV'), null)->getValueType(),
			);

			if (!$inputArgType->isInteger()->yes() || $supportedTypes->isSuperTypeOf($inputArgType)->no()) {
				return null;
			}

			// Pragmatical solution since global expressions are not passed through the scope for performance reasons
			// See https://github.com/phpstan/phpstan-src/pull/2012 for details
			$inputArgType = new ArrayType(new StringType(), new MixedType());
		}

		$filterArgType = $scope->getType($functionCall->getArgs()[1]->value);
		$filterConstantArrayType = $filterArgType->getConstantArrays()[0] ?? null;
		$addEmptyType = isset($functionCall->getArgs()[2]) ? $scope->getType($functionCall->getArgs()[2]->value) : null;
		$addEmpty = $addEmptyType === null || $addEmptyType->isTrue()->yes();

		$valueTypesBuilder = ConstantArrayTypeBuilder::createEmpty();

		if ($filterArgType instanceof ConstantIntegerType) {
			if ($inputConstantArrayType === null) {
				$isList = $inputArgType->isList()->yes();
				$valueType = $this->filterFunctionReturnTypeHelper->getType(
					$inputArgType->getIterableValueType(),
					$filterArgType,
					null,
				);
				$arrayType = new ArrayType($inputArgType->getIterableKeyType(), $valueType);

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
				$valueType = $this->filterFunctionReturnTypeHelper->getType($inputArgType, $filterArgType, null);

				$arrayType = new ArrayType(
					$inputArgType->getIterableKeyType(),
					$addEmpty ? TypeCombinator::addNull($valueType) : $valueType,
				);

				return $isList ? AccessoryArrayListType::intersectWith($arrayType) : $arrayType;
			}

			return null;
		} else {
			$keysType = $filterConstantArrayType;
			$filterKeyTypes = $filterConstantArrayType->getKeyTypes();
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
				$inputTypesMap = array_fill_keys($optionalKeys, $inputArgType->getIterableValueType());
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

			[$filterType, $flagsType] = $this->fetchFilter($filterTypesMap[$key] ?? new MixedType());
			$valueType = $this->filterFunctionReturnTypeHelper->getType($inputType, $filterType, $flagsType);

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
	public function fetchFilter(Type $type): array
	{
		if (!$type->isArray()->yes()) {
			return [$type, null];
		}

		$filterKey = new ConstantStringType('filter');
		if (!$type->hasOffsetValueType($filterKey)->yes()) {
			return [$type, null];
		}

		$filterOffsetType = $type->getOffsetValueType($filterKey);
		$filterType = null;

		if (count($filterOffsetType->getConstantScalarTypes()) > 0) {
			$filterType = TypeCombinator::union(...$filterOffsetType->getConstantScalarTypes());
		}

		return [$filterType, $type];
	}

}
