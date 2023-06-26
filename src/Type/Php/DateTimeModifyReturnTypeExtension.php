<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use DateTimeInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function count;

class DateTimeModifyReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/** @var class-string<DateTimeInterface> */
	private $dateTimeClass;

	/** @param class-string<DateTimeInterface> $dateTimeClass */
	public function __construct(string $dateTimeClass = DateTime::class)
	{
		$this->dateTimeClass = $dateTimeClass;
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
		if (count($methodCall->getArgs()) < 1) {
			return null;
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$constantStrings = $valueType->getConstantStrings();

		$hasFalse = false;
		$hasDateTime = false;

		foreach ($constantStrings as $constantString) {
			try {
				$result = @(new DateTime())->modify($constantString->getValue());
			} catch (Throwable) {
				$hasFalse = true;
				$valueType = TypeCombinator::remove($valueType, $constantString);
				continue;
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

		if ($hasFalse && !$hasDateTime) {
			return new ConstantBooleanType(false);
		}
		if ($hasDateTime && !$hasFalse) {
			return $scope->getType($methodCall->var);
		}

		return null;
	}

}
