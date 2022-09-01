<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class ArrayFillKeysFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_fill_keys';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$valueType = $scope->getType($functionCall->getArgs()[1]->value);
		$keysType = $scope->getType($functionCall->getArgs()[0]->value);
		$constantArrays = $keysType->getConstantArrays();
		if (count($constantArrays) === 0) {
			if ($keysType->isArray()->yes()) {
				$itemType = $keysType->getIterableValueType();

				if ((new IntegerType())->isSuperTypeOf($itemType)->no()) {
					if ($itemType->toString() instanceof ErrorType) {
						return new ArrayType($itemType, $valueType);
					}

					return new ArrayType($itemType->toString(), $valueType);
				}
			}

			return new ArrayType($keysType->getIterableValueType(), $valueType);
		}

		$arrayTypes = [];
		foreach ($constantArrays as $constantArray) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($constantArray->getValueTypes() as $i => $keyType) {
				if ((new IntegerType())->isSuperTypeOf($keyType)->no()) {
					if ($keyType->toString() instanceof ErrorType) {
						return new NeverType();
					}

					$arrayBuilder->setOffsetValueType($keyType->toString(), $valueType, $constantArray->isOptionalKey($i));
				} else {
					$arrayBuilder->setOffsetValueType($keyType, $valueType, $constantArray->isOptionalKey($i));
				}
			}
			$arrayTypes[] = $arrayBuilder->getArray();
		}

		return TypeCombinator::union(...$arrayTypes);
	}

}
