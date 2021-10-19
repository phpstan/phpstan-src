<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
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

		if (count($constantStrings) === 1) {
			$constantString = $constantStrings[0]->getValue();

			// see see https://www.php.net/manual/en/datetime.format.php
			switch ($constantString) {
				case 'j':
					return IntegerRangeType::fromInterval(1, 31);
				case 'N':
					return IntegerRangeType::fromInterval(1, 7);
				case 'w':
					return IntegerRangeType::fromInterval(0, 6);
				case 'z':
					return IntegerRangeType::fromInterval(0, 365);
				case 'W':
					return IntegerRangeType::fromInterval(1, 53);
				case 'n':
					return IntegerRangeType::fromInterval(1, 12);
				case 't':
					return IntegerRangeType::fromInterval(28, 31);
				case 'L':
					return IntegerRangeType::fromInterval(0, 1);
				case 'o':
					return IntegerRangeType::fromInterval(1, 9999);
				case 'Y':
					return IntegerRangeType::fromInterval(1, 9999);
				case 'g':
					return IntegerRangeType::fromInterval(1, 12);
				case 'G':
					return IntegerRangeType::fromInterval(0, 23);
				case 'I':
					return IntegerRangeType::fromInterval(0, 1);
			}
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
