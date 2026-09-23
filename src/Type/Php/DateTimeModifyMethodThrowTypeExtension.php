<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use DateTimeImmutable;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function count;
use function in_array;

#[AutowiredService]
final class DateTimeModifyMethodThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'modify' && in_array($methodReflection->getDeclaringClass()->getName(), [DateTime::class, DateTimeImmutable::class], true);
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return null;
		}

		if ($scope->getPhpVersion()->hasDateTimeExceptions()->no()) {
			return null;
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$constantStrings = $valueType->getConstantStrings();

		foreach ($constantStrings as $constantString) {
			// modify() only throws since PHP 8.3, before that it warns and returns false.
			// The analysed version can be 8.3+ while this process runs on an older one,
			// so detect the failure through the return value instead of the exception.
			try {
				$dateTime = new DateTime();
				$result = @$dateTime->modify($constantString->getValue());
			} catch (Throwable) {
				$result = false;
			}

			if ($result === false) {
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
