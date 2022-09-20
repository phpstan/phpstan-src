<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;

class PathinfoFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'pathinfo';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		Node\Expr\FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$argsCount = count($functionCall->getArgs());
		if ($argsCount === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		} elseif ($argsCount === 1) {
			$builder = ConstantArrayTypeBuilder::createEmpty();

			$builder->setOffsetValueType(new ConstantStringType('dirname'), new StringType(), true);
			$builder->setOffsetValueType(new ConstantStringType('basename'), new StringType());
			$builder->setOffsetValueType(new ConstantStringType('extension'), new StringType(), true);
			$builder->setOffsetValueType(new ConstantStringType('filename'), new StringType());

			return $builder->getArray();
		}

		return new StringType();
	}

}
