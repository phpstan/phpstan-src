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
use function date_create;
use function in_array;

final class DateTimeCreateDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['date_create', 'date_create_immutable'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$datetimes = $scope->getType($functionCall->getArgs()[0]->value)->getConstantStrings();

		if (count($datetimes) === 0) {
			return null;
		}

		$types = [];
		$className = $functionReflection->getName() === 'date_create' ? DateTime::class : DateTimeImmutable::class;
		foreach ($datetimes as $constantString) {
			$isValid = date_create($constantString->getValue()) !== false;
			$types[] = $isValid ? new ObjectType($className) : new ConstantBooleanType(false);
		}

		return TypeCombinator::union(...$types);
	}

}
