<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;

final class ArrayCombineFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_combine';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$firstArg = $functionCall->getArgs()[0]->value;
		$secondArg = $functionCall->getArgs()[1]->value;

		$keysParamType = $scope->getType($firstArg);
		$valuesParamType = $scope->getType($secondArg);

		if (
			$keysParamType instanceof ConstantArrayType
			&& $valuesParamType instanceof ConstantArrayType
		) {
			$keyTypes = $keysParamType->getValueTypes();
			$valueTypes = $valuesParamType->getValueTypes();

			if (count($keyTypes) !== count($valueTypes)) {
				if ($this->phpVersion->throwsTypeErrorForInternalFunctions()) {
					return new NeverType();
				}
				return new ConstantBooleanType(false);
			}

			$keyTypes = $this->sanitizeConstantArrayKeyTypes($keyTypes);
			if ($keyTypes !== null) {
				return new ConstantArrayType(
					$keyTypes,
					$valueTypes,
					$keysParamType->getNextAutoIndexes(),
				);
			}
		}

		if ($keysParamType->isArray()->yes()) {
			$itemType = $keysParamType->getIterableValueType();

			if ($itemType->isInteger()->no()) {
				if ($itemType->toString() instanceof ErrorType) {
					return new NeverType();
				}

				$keyType = $itemType->toString();
			} else {
				$keyType = $itemType;
			}
		} else {
			$keyType = new MixedType();
		}

		$arrayType = new ArrayType(
			$keyType,
			$valuesParamType->isArray()->yes() ? $valuesParamType->getIterableValueType() : new MixedType(),
		);

		if ($keysParamType->isIterableAtLeastOnce()->yes() && $valuesParamType->isIterableAtLeastOnce()->yes()) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
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
			if ($type->isInteger()->no() && ! $type->toString() instanceof ErrorType) {
				$type = $type->toString();
			}

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
