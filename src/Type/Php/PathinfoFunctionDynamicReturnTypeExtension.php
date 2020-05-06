<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class PathinfoFunctionDynamicReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'pathinfo';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		\PhpParser\Node\Expr\FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$argsCount = count($functionCall->args);
		if ($argsCount === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		} elseif ($argsCount === 1) {
			$stringType = new StringType();

			$builder = ConstantArrayTypeBuilder::createFromConstantArray(
				new ConstantArrayType(
					[new ConstantStringType('dirname'), new ConstantStringType('basename'), new ConstantStringType('filename')],
					[$stringType, $stringType, $stringType]
				)
			);
			$builder->setOffsetValueType(new ConstantStringType('extension'), $stringType, true);

			return $builder->getArray();
		}

		return new StringType();
	}

}
