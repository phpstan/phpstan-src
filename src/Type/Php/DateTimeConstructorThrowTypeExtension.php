<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use DateTimeImmutable;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

class DateTimeConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct' && in_array($methodReflection->getDeclaringClass()->getName(), [DateTime::class, DateTimeImmutable::class], true);
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->args) === 0) {
			return null;
		}

		$arg = $methodCall->args[0]->value;
		$constantStrings = TypeUtils::getConstantStrings($scope->getType($arg));
		if (count($constantStrings) === 0) {
			return $methodReflection->getThrowType();
		}

		foreach ($constantStrings as $constantString) {
			try {
				new \DateTime($constantString->getValue());
			} catch (\Exception $e) { // phpcs:ignore
				return $methodReflection->getThrowType();
			}
		}

		return null;
	}

}
