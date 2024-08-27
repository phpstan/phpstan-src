<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use DateTimeImmutable;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

final class DateTimeDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['date_create_from_format', 'date_create_immutable_from_format'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$formats = $scope->getType($functionCall->getArgs()[0]->value)->getConstantStrings();
		$datetimes = $scope->getType($functionCall->getArgs()[1]->value)->getConstantStrings();

		if (count($formats) === 0 || count($datetimes) === 0) {
			return null;
		}

		$types = [];
		$className = $functionReflection->getName() === 'date_create_from_format' ? DateTime::class : DateTimeImmutable::class;
		foreach ($formats as $formatConstantString) {
			foreach ($datetimes as $datetimeConstantString) {
				$isValid = (DateTime::createFromFormat($formatConstantString->getValue(), $datetimeConstantString->getValue()) !== false);
				$types[] = $isValid ? new ObjectType($className) : new ConstantBooleanType(false);
			}
		}

		return TypeCombinator::union(...$types);
	}

}
