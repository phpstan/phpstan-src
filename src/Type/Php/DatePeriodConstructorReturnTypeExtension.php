<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DatePeriod;
use DateTimeInterface;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class DatePeriodConstructorReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return DatePeriod::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
	{
		$thirdArgType = null;
		if (isset($methodCall->getArgs()[2])) {
			$thirdArgType = $scope->getType($methodCall->getArgs()[2]->value);
		}

		if (
				$thirdArgType instanceof ObjectType
			&& $thirdArgType->isInstanceOf(DateTimeInterface::class)
		) {

			return new GenericObjectType(DatePeriod::class, [
				new ObjectType(DateTimeInterface::class),
				new NullType(),
			]);
		}

		return new GenericObjectType(DatePeriod::class, [
			new NullType(),
			new IntegerType(),
		]);
	}

}
