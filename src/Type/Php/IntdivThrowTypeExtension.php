<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use ArithmeticError;
use DivisionByZeroError;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function count;
use const PHP_INT_MIN;

class IntdivThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'intdiv';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		if (count($funcCall->getArgs()) < 2) {
			return $functionReflection->getThrowType();
		}

		$valueType = $scope->getType($funcCall->getArgs()[0]->value)->toInteger();
		$containsMin = $valueType->acceptsWithReason(new ConstantIntegerType(PHP_INT_MIN), true);

		$divisorType = $scope->getType($funcCall->getArgs()[1]->value)->toInteger();
		if (!$containsMin->no()) {
			$divisionByMinusOne = $divisorType->acceptsWithReason(new ConstantIntegerType(-1), true);
			if (!$divisionByMinusOne->no()) {
				return new ObjectType(ArithmeticError::class);
			}
		}

		$divisionByZero = $divisorType->acceptsWithReason(new ConstantIntegerType(0), false);
		if (!$divisionByZero->no()) {
			return new ObjectType(DivisionByZeroError::class);
		}

		return null;
	}

}
