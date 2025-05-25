<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

#[AutowiredService]
final class GetDefinedVarsFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'get_defined_vars';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if ($scope->canAnyVariableExist()) {
			return new ArrayType(
				new StringType(),
				new MixedType(),
			);
		}

		$typeBuilder = ConstantArrayTypeBuilder::createEmpty();

		foreach ($scope->getDefinedVariables() as $variable) {
			$typeBuilder->setOffsetValueType(new ConstantStringType($variable), $scope->getVariableType($variable), false);
		}

		foreach ($scope->getMaybeDefinedVariables() as $variable) {
			$typeBuilder->setOffsetValueType(new ConstantStringType($variable), $scope->getVariableType($variable), true);
		}

		return $typeBuilder->getArray();
	}

}
