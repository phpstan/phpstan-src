<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use DateTimeImmutable;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function count;
use function in_array;

#[AutowiredService]
final class DateTimeSubMethodThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'sub'
			&& in_array($methodReflection->getDeclaringClass()->getName(), [DateTime::class, DateTimeImmutable::class], true);
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return null;
		}

		if (!$this->phpVersion->hasDateTimeExceptions()) {
			return null;
		}

		return new ObjectType('DateInvalidOperationException');
	}

}
