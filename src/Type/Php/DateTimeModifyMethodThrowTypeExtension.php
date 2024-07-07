<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use DateTimeImmutable;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

final class DateTimeModifyMethodThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'modify' && in_array($methodReflection->getDeclaringClass()->getName(), [DateTime::class, DateTimeImmutable::class], true);
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return null;
		}

		if (!$this->phpVersion->hasDateTimeExceptions()) {
			return null;
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$constantStrings = $valueType->getConstantStrings();

		foreach ($constantStrings as $constantString) {
			try {
				$dateTime = new DateTime();
				$dateTime->modify($constantString->getValue());
			} catch (\Exception $e) { // phpcs:ignore
				return $this->exceptionType();
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if (!$valueType instanceof NeverType) {
			return $this->exceptionType();
		}

		return null;
	}

	private function exceptionType(): Type
	{
		if ($this->phpVersion->hasDateTimeExceptions()) {
			return new ObjectType('DateMalformedStringException');
		}

		return new ObjectType('Exception');
	}

}
