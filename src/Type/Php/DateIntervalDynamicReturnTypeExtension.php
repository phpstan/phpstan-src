<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;
use function count;
use function in_array;

final class DateIntervalDynamicReturnTypeExtension
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

		$possibleReturnTypes = [];
		foreach ($strings as $string) {
			try {
				$result = @DateInterval::createFromDateString($string->getValue());
			} catch (Throwable) {
				$possibleReturnTypes[] = false;
				continue;
			}
			$possibleReturnTypes[] = $result instanceof DateInterval ? DateInterval::class : false;
		}

		// the error case, when wrong types are passed
		if (count($possibleReturnTypes) === 0) {
			return null;
		}

		if (in_array(false, $possibleReturnTypes, true) && in_array(DateInterval::class, $possibleReturnTypes, true)) {
			return null;
		}

		if (in_array(false, $possibleReturnTypes, true)) {
			return new ConstantBooleanType(false);
		}

		return new ObjectType(DateInterval::class);
	}

}
