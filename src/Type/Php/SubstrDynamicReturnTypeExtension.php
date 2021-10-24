<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;

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
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		if (count($args) >= 2) {
			$string = $scope->getType($args[0]->value);
			$offset = $scope->getType($args[1]->value);

			$negativeOffset = IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($offset)->yes();
			$zeroOffset = (new ConstantIntegerType(0))->isSuperTypeOf($offset)->yes();
			$positiveLength = false;

			if (count($args) === 3) {
				$length = $scope->getType($args[2]->value);
				$positiveLength = IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($length)->yes();
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

}
