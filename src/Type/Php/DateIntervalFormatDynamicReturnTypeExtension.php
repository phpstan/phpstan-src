<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function is_numeric;
use function strtolower;
use function strtoupper;

#[AutowiredService]
final class DateIntervalFormatDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return DateInterval::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'format';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$arguments = $methodCall->getArgs();

		if (!isset($arguments[0])) {
			return null;
		}

		$arg = $scope->getType($arguments[0]->value);

		$constantStrings = $arg->getConstantStrings();
		if (count($constantStrings) === 0) {
			if ($arg->isNonEmptyString()->yes()) {
				return new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]);
			}

			return null;
		}

		// The worst case scenario for the non-falsy-string check is that every number is 0.
		// `%a` format gives `(unknown)` and removes numeric and uppercase accessory but then
		// we'll have to manually check for the non-falsy one.
		$dateInterval = new DateInterval('P0D');

		$possibleReturnTypes = [];
		foreach ($constantStrings as $string) {
			$formatString = $string->getValue();
			$value = $dateInterval->format($formatString);

			$accessories = [];
			if (is_numeric($value)) {
				$accessories[] = new AccessoryNumericStringType();
			}
			if ($value !== '0' && $value !== '' && $formatString !== '%a') {
				$accessories[] = new AccessoryNonFalsyStringType();
			} elseif ($value !== '') {
				$accessories[] = new AccessoryNonEmptyStringType();
			}
			if (strtolower($value) === $value) {
				$accessories[] = new AccessoryLowercaseStringType();
			}
			if (strtoupper($value) === $value) {
				$accessories[] = new AccessoryUppercaseStringType();
			}

			if (count($accessories) === 0) {
				return null;
			}

			$possibleReturnTypes[] = new IntersectionType([new StringType(), ...$accessories]);
		}

		return TypeCombinator::union(...$possibleReturnTypes);
	}

}
