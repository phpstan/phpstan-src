<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use ArithmeticError;
use DivisionByZeroError;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use const PHP_INT_MIN;

final class IntdivThrowTypeExtension implements DynamicFunctionThrowTypeExtension
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

		$containsMin = false;
		$valueType = $scope->getType($funcCall->getArgs()[0]->value);
		foreach ($valueType->getConstantScalarTypes() as $constantScalarType) {
			if ($constantScalarType->getValue() === PHP_INT_MIN) {
				$containsMin = true;
			}

			$valueType = TypeCombinator::remove($valueType, $constantScalarType);
		}

		if (!$valueType instanceof NeverType) {
			$containsMin = true;
		}

		$divisionByZero = false;
		$divisorType = $scope->getType($funcCall->getArgs()[1]->value);
		foreach ($divisorType->getConstantScalarTypes() as $constantScalarType) {
			if ($containsMin && $constantScalarType->getValue() === -1) {
				return new ObjectType(ArithmeticError::class);
			}

			if ($constantScalarType->getValue() === 0) {
				$divisionByZero = true;
			}

			$divisorType = TypeCombinator::remove($divisorType, $constantScalarType);
		}

		if (!$divisorType instanceof NeverType) {
			return new ObjectType($containsMin ? ArithmeticError::class : DivisionByZeroError::class);
		}

		if ($divisionByZero) {
			return new ObjectType(DivisionByZeroError::class);
		}

		return null;
	}

}
