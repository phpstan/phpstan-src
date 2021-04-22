<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use DateTimeImmutable;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

class DateTimeDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['date_create_from_format', 'date_create_immutable_from_format'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

		$format = $scope->getType($functionCall->args[0]->value);
		$datetime = $scope->getType($functionCall->args[1]->value);

		if (!$format instanceof ConstantStringType || !$datetime instanceof ConstantStringType) {
			return $defaultReturnType;
		}

		$formatValue = TypeUtils::getConstantStrings($format)[0]->getValue();
		$datetimeValue = TypeUtils::getConstantStrings($datetime)[0]->getValue();
		$isValid = (DateTime::createFromFormat($formatValue, $datetimeValue) !== false);

		$className = $functionReflection->getName() === 'date_create_from_format' ? DateTime::class : DateTimeImmutable::class;
		return $isValid ? new ObjectType($className) : new ConstantBooleanType(false);
	}

}
