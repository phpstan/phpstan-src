<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function in_array;

class ArrayMergeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_merge';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$allConstant = true;
		foreach ($functionCall->getArgs() as $arg) {
			$argType = $scope->getType($arg->value);
			if ($arg->unpack || !$argType instanceof ConstantArrayType) {
				$allConstant = false;
				break;
			}
		}

		if ($allConstant) {
			$newArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($functionCall->getArgs() as $arg) {
				$argType = $scope->getType($arg->value);
				if (!$argType instanceof ConstantArrayType) {
					throw new ShouldNotHappenException();
				}

				$keyTypes = $argType->getKeyTypes();
				$valueTypes = $argType->getValueTypes();
				$optionalKeys = $argType->getOptionalKeys();

				foreach ($keyTypes as $i => $keyType) {
					$isOptional = in_array($i, $optionalKeys, true);

					$newArrayBuilder->setOffsetValueType(
						$keyType,
						$valueTypes[$i],
						$isOptional,
					);
				}
			}

			return $newArrayBuilder->getArray();
		}

		$keyTypes = [];
		$valueTypes = [];
		$nonEmpty = false;
		foreach ($functionCall->getArgs() as $arg) {
			$argType = $scope->getType($arg->value);
			if ($arg->unpack) {
				$argType = $argType->getIterableValueType();
				if ($argType instanceof UnionType) {
					foreach ($argType->getTypes() as $innerType) {
						$argType = $innerType;
					}
				}
			}

			$keyTypes[] = $argType->getIterableKeyType();
			$valueTypes[] = $argType->getIterableValueType();

			if (!$argType->isIterableAtLeastOnce()->yes()) {
				continue;
			}

			$nonEmpty = true;
		}

		$keyType = TypeCombinator::union(...$keyTypes);
		if ($keyType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		$arrayType = new ArrayType(
			$keyType,
			TypeCombinator::union(...$valueTypes),
		);

		if ($nonEmpty) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		return $arrayType;
	}

}
