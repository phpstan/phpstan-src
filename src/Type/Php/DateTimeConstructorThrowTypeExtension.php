<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use DateTimeImmutable;
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
use function in_array;

#[AutowiredService]
final class DateTimeConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct' && in_array($methodReflection->getDeclaringClass()->getName(), [DateTime::class, DateTimeImmutable::class], true);
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return null;
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$constantStrings = $valueType->getConstantStrings();

		foreach ($constantStrings as $constantString) {
			try {
				new DateTime($constantString->getValue());
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
			return new ObjectType('DateMalformedStringException');
		}

		return new ObjectType('Exception');
	}

}
