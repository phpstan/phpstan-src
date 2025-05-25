<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function in_array;

#[AutowiredService]
final class NumberFormatFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'number_format';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$stringType = new StringType();
		if (!isset($functionCall->getArgs()[3])) {
			return $stringType;
		}

		$thousandsType = $scope->getType($functionCall->getArgs()[3]->value);
		$decimalType = $scope->getType($functionCall->getArgs()[2]->value);

		if (!$thousandsType instanceof ConstantStringType || $thousandsType->getValue() !== '') {
			return $stringType;
		}

		if (!$decimalType instanceof ConstantScalarType || !in_array($decimalType->getValue(), [null, '.', ''], true)) {
			return $stringType;
		}

		return new IntersectionType([
			$stringType,
			new AccessoryNumericStringType(),
		]);
	}

}
