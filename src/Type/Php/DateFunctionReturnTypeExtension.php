<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

class DateFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'date';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return new StringType();
		}
		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$constantStrings = TypeUtils::getConstantStrings($argType);
		if (count($constantStrings) === 0) {
			return new StringType();
		}

		foreach ($constantStrings as $constantString) {
			$formattedDate = date($constantString->getValue());
			if (!is_numeric($formattedDate)) {
				return new StringType();
			}
		}

		return new IntersectionType([
			new StringType(),
			new AccessoryNumericStringType(),
		]);
	}

}
