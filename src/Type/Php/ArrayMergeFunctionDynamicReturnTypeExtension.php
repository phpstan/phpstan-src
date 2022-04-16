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
use PHPStan\Type\Constant\ConstantIntegerType;
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
		$args = $functionCall->getArgs();

		if (!isset($args[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$argTypes = [];
		$allConstant = true;
		foreach ($args as $i => $arg) {
			$argType = $scope->getType($arg->value);
			$argTypes[$i] = $argType;

			if (!$arg->unpack && $argType instanceof ConstantArrayType) {
				continue;
			}

			$allConstant = false;
		}

		if ($allConstant) {
			$newArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($args as $i => $arg) {
				$argType = $argTypes[$i];

				if (!$argType instanceof ConstantArrayType) {
					throw new ShouldNotHappenException();
				}

				$keyTypes = $argType->getKeyTypes();
				$valueTypes = $argType->getValueTypes();
				$optionalKeys = $argType->getOptionalKeys();

				foreach ($keyTypes as $k => $keyType) {
					$isOptional = in_array($k, $optionalKeys, true);

					$newArrayBuilder->setOffsetValueType(
						$keyType instanceof ConstantIntegerType ? null : $keyType,
						$valueTypes[$k],
						$isOptional,
					);
				}
			}

			return $newArrayBuilder->getArray();
		}

		$keyTypes = [];
		$valueTypes = [];
		$nonEmpty = false;
		foreach ($args as $i => $arg) {
			$argType = $argTypes[$i];

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
