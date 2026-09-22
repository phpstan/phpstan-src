<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function count;

#[AutowiredService]
final class DateIntervalCreateFromDateStringThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'createFromDateString'
			&& $methodReflection->getDeclaringClass()->getName() === DateInterval::class;
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
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
			try {
				@DateInterval::createFromDateString($constantString->getValue());
			} catch (Throwable) {
				return $methodReflection->getThrowType();
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if (!$valueType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		return null;
	}

}
