<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use DateTimeInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use Throwable;
use function count;

final class DateTimeModifyReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/** @param class-string<DateTimeInterface> $dateTimeClass */
	public function __construct(
		private PhpVersion $phpVersion,
		private string $dateTimeClass,
	)
	{
	}

	public function getClass(): string
	{
		return $this->dateTimeClass;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'modify';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$valueType = $scope->getType($args[0]->value);
		$constantStrings = $valueType->getConstantStrings();

		$hasFalse = false;
		$hasDateTime = false;

		foreach ($constantStrings as $constantString) {
			try {
				$result = @(new DateTime())->modify($constantString->getValue());
			} catch (Throwable) {
				$result = false;
			}

			if ($result === false) {
				$hasFalse = true;
			} else {
				$hasDateTime = true;
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if (!$valueType instanceof NeverType) {
			return null;
		}

		if ($hasFalse) {
			if (!$hasDateTime) {
				if ($this->phpVersion->hasDateTimeExceptions()) {
					return new NeverType();
				}

				return new ConstantBooleanType(false);
			}

			return null;
		}
		if ($hasDateTime) {
			$callerType = $scope->getType($methodCall->var);

			$dateTimeInterfaceType = new ObjectType(DateTimeInterface::class);
			if ($dateTimeInterfaceType->isSuperTypeOf($callerType)->yes()) {
				return $callerType;
			}

			return TypeTraverser::map(
				$callerType,
				static function (Type $type, callable $traverse) use ($dateTimeInterfaceType): Type {
					if ($type instanceof UnionType) {
						return $traverse($type);
					}
					if ($dateTimeInterfaceType->isSuperTypeOf($type)->yes()) {
						return $type;
					}
					return new NeverType();
				},
			);
		}

		return null;
	}

}
