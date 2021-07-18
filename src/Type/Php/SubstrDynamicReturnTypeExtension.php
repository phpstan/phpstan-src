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
use PHPStan\Type\Type;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Constant\ConstantIntegerType;

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
		
		$negativeOffset = $this->getIntValue($offset) < 0;
		$zeroOffset = $this->getIntValue($offset) === 0;
		$positiveLength = false;
		
		if (count($args) === 3) {
			$length = $scope->getType($args[2]->value);
			$positiveLength = $this->getIntValue($length) > 0;
		}
      
	  	if ($string->isNonEmptyString()->yes() && ($negativeOffset || $zeroOffset && $positiveLength)) {
			  return new IntersectionType([
		  		new StringType(),
			  	new AccessoryNonEmptyStringType(),
		  	]);
	  	}
	}
		

		return new StringType();
	}

private function getIntValue(Type $type): ?int {
				if ($type instanceof IntegerRangeType) {
					return $type->getMin();
				}
				if ($type instanceof ConstantIntegerType) {
					return $type->getValue();
				}
				return null;
			

}

}
