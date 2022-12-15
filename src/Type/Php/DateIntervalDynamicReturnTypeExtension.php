<?php declare( strict_types = 1 );

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
use function array_map;
use function count;
use function gettype;
use function in_array;

class DateIntervalDynamicReturnTypeExtension
implements DynamicStaticMethodReturnTypeExtension
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
		$arguments = $methodCall->getArgs();

		if (!isset($arguments[0])) {
			return null;
		}

		$strings = $scope->getType($arguments[0]->value)->getConstantStrings();

		$possibleReturnTypes = array_map(
			static fn (ConstantStringType $s): string => gettype(@DateInterval::createFromDateString($s->getValue())),
			$strings,
		);

		// the error case, when wrong types are passed
		if (count($possibleReturnTypes) === 0) {
			return null;
		}

		if (in_array('boolean', $possibleReturnTypes, true) && in_array('object', $possibleReturnTypes, true)) {
			return null;
		}

		if (in_array('boolean', $possibleReturnTypes, true)) {
			return new ConstantBooleanType(false);
		}

		return new ObjectType(DateInterval::class);
	}

}
