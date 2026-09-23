<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;

#[AutowiredService]
final class DateIntervalDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

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

		$hasFalse = false;
		$hasDateInterval = false;
		foreach ($strings as $string) {
			try {
				$result = @DateInterval::createFromDateString($string->getValue());
			} catch (Throwable) {
				$result = false;
			}

			if ($result === false) {
				$hasFalse = true;
			} else {
				$hasDateInterval = true;
			}
		}

		if ($hasFalse) {
			if (!$hasDateInterval) {
				if ($this->phpVersion->hasDateTimeExceptions()) {
					return new NeverType();
				}

				return new ConstantBooleanType(false);
			}

			return null;
		}
		if ($hasDateInterval) {
			return new ObjectType(DateInterval::class);
		}

		return null;
	}

}
