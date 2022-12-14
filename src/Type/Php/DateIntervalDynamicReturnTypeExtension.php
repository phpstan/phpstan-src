<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

class DateIntervalDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return DateInterval::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'createFromDateString';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		$strings = $scope->getType($methodCall->getArgs()[0]->value)->getConstantStrings();
		
		if ($strings === []) {
		   return null;
		}
		
		if (count($strings) === 1) {
           if (DateInterval::createFromDateString($dateTimeString->getValue()) === false) {
    		 return new ConstantBooleanType(false);
           }
           return new ObjectType(DateInterval::class);
		}
		
		foreach($strings as $string) {
		  if (DateInterval::createFromDateString($dateTimeString->getValue()) === false) {
		    return null;
		  }
		}

		return new ObjectType(DateInterval::class);
	}

}
