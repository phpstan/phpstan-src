<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class DatePeriodGetEndDateMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \DatePeriod::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getEndDate';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		/** @var New_ $new */
		$new = $methodCall->var;
		$argument = $new->getRawArgs()[2] ?? null;

		// initialize with iso parameter
		if ($argument === null) {
			return new NullType();
		}

		$dateTimeType = new ObjectType(\DateTimeInterface::class);
		$argumentType = $scope->getType($argument->value);

		// initialize with end parameter
		if ($dateTimeType->isSuperTypeOf($argumentType)->yes()) {
			return $dateTimeType;
		}

		// initialize with recurrences parameter
		if ((new IntegerType())->isSuperTypeOf($argumentType)->yes()) {
			return new NullType();
		}

		return TypeCombinator::addNull($dateTimeType);
	}

}
