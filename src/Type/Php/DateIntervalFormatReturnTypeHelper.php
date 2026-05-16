<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateInterval;
use DateTime;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function is_numeric;
use function strtolower;
use function strtoupper;

#[AutowiredService]
final class DateIntervalFormatReturnTypeHelper
{

	public function getType(Type $formatType, Type $intervalType, Scope $scope): ?Type
	{
		$constantStrings = $formatType->getConstantStrings();
		if (count($constantStrings) === 0) {
			if ($formatType->isNonEmptyString()->yes()) {
				return new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]);
			}

			return null;
		}

		$daysIsInt = $intervalType->hasInstanceProperty('days')->yes()
			&& (new IntegerType())->isSuperTypeOf($intervalType->getInstanceProperty('days', $scope)->getReadableType())->yes();

		$dateInterval = $daysIsInt
			? (new DateTime('2000-01-01'))->diff(new DateTime('2000-01-01'))
			: new DateInterval('P0D');

		$possibleReturnTypes = [];
		foreach ($constantStrings as $string) {
			$formatString = $string->getValue();
			$value = $dateInterval->format($formatString);

			$accessories = [];
			if (is_numeric($value)) {
				$accessories[] = new AccessoryNumericStringType();
			}
			if ($value !== '0' && $value !== '' && !($formatString === '%a' && !$daysIsInt)) {
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
