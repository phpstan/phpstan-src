<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function count;

#[AutowiredService]
final class DateIntervalConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct' && $methodReflection->getDeclaringClass()->getName() === DateInterval::class;
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return $methodReflection->getThrowType();
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$constantStrings = $valueType->getConstantStrings();

		foreach ($constantStrings as $constantString) {
			try {
				new DateInterval($constantString->getValue());
			} catch (Throwable) {
				return $this->exceptionType($scope);
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if (!$valueType instanceof NeverType) {
			return $this->exceptionType($scope);
		}

		return null;
	}

	private function exceptionType(Scope $scope): Type
	{
		if ($scope->getPhpVersion()->hasDateTimeExceptions()->yes()) {
			return new ObjectType('DateMalformedIntervalStringException');
		}

		return new ObjectType('Exception');
	}

}
