<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\ConstantIntegerType;

class SubstrDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'substr';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): \PHPStan\Type\Type
	{
		$args = $functionCall->args;
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

    if (count($args) >= 2) {
      $string = $scope->getType($args[0]->value);
      $offset = $scope->getType($args[1]->value);
		
		$negativeOffset = $offset instanceof ConstantIntegerType && $offset->getValue() < 0;
		$zeroOffset = $offset instanceof ConstantIntegerType && $offset->getValue() === 0;
		$positiveLength = null;
		
		if (count($args) === 3) {
			$length = $scope->getType($args[2]->value);
			$positiveLength = $length instanceof ConstantIntegerType && $length->getValue() > 0;
		}
      
	  	if ($argType->isNonEmptyString()->yes() && ($negativeOffset || $zeroOffset && $positiveLength)) {
			  return new IntersectionType([
		  		new StringType(),
			  	new AccessoryNonEmptyStringType(),
		  	]);
	  	}
	}
		

		return new StringType();
	}

}
