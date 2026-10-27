<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function date;
use function is_numeric;
use function str_pad;
use const STR_PAD_LEFT;

#[AutowiredService]
final class DateFunctionReturnTypeHelper
{

	public function getTypeFromFormatType(Type $formatType, bool $useMicrosec): Type
	{
		$types = [];
		foreach ($formatType->getConstantStrings() as $formatString) {
			$types[] = $this->buildReturnTypeFromFormat($formatString->getValue(), $useMicrosec);
		}

		if (count($types) === 0) {
			$type = $formatType->isNonEmptyString()->yes()
				? new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()])
				: new StringType();
		} else {
			$type = TypeCombinator::union(...$types);
		}

		return $type;
	}

	private function buildReturnTypeFromFormat(string $formatString, bool $useMicrosec): Type
	{
		// see see https://www.php.net/manual/en/datetime.format.php
		switch ($formatString) {
			case 'd':
				return $this->buildNumericRangeType(1, 31, true);
			case 'j':
				return $this->buildNumericRangeType(1, 31, false);
			case 'N':
				return $this->buildNumericRangeType(1, 7, false);
			case 'w':
				return $this->buildNumericRangeType(0, 6, false);
			case 'm':
				return $this->buildNumericRangeType(1, 12, true);
			case 'n':
				return $this->buildNumericRangeType(1, 12, false);
			case 't':
				return $this->buildNumericRangeType(28, 31, false);
			case 'L':
				return $this->buildNumericRangeType(0, 1, false);
			case 'g':
				return $this->buildNumericRangeType(1, 12, false);
			case 'G':
				return $this->buildNumericRangeType(0, 23, false);
			case 'h':
				return $this->buildNumericRangeType(1, 12, true);
			case 'H':
				return $this->buildNumericRangeType(0, 23, true);
			case 'I':
				return $this->buildNumericRangeType(0, 1, false);
			case 'u':
				return $useMicrosec
					? new IntersectionType([new StringType(), new AccessoryNonFalsyStringType(), new AccessoryNumericStringType()])
					: new ConstantStringType('000000');
			case 'v':
				return $useMicrosec
					? new IntersectionType([new StringType(), new AccessoryNonFalsyStringType(), new AccessoryNumericStringType()])
					: new ConstantStringType('000');
			// values which do not depend on the locale
			case 'a':
				return $this->buildConstantStringsType(['am', 'pm']);
			case 'A':
				return $this->buildConstantStringsType(['AM', 'PM']);
			case 'S':
				return $this->buildConstantStringsType(['st', 'nd', 'rd', 'th']);
			case 'D':
				return $this->buildConstantStringsType(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']);
			case 'l':
				return $this->buildConstantStringsType(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
			case 'M':
				return $this->buildConstantStringsType(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
			case 'F':
				return $this->buildConstantStringsType(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);
		}

		$date = date($formatString);

		// If parameter string is not included, returned as ConstantStringType
		if ($date === $formatString) {
			return new ConstantStringType($date);
		}

		if (is_numeric($date)) {
			return new IntersectionType([new StringType(), new AccessoryNumericStringType()]);
		}

		return new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()]);
	}

	private function buildNumericRangeType(int $min, int $max, bool $zeroPad): Type
	{
		$types = [];

		for ($i = $min; $i <= $max; $i++) {
			$string = (string) $i;

			if ($zeroPad) {
				$string = str_pad($string, 2, '0', STR_PAD_LEFT);
			}

			$types[] = new ConstantStringType($string);
		}

		return new UnionType($types);
	}

	/**
	 * @param list<string> $values
	 */
	private function buildConstantStringsType(array $values): Type
	{
		$types = [];

		foreach ($values as $value) {
			$types[] = new ConstantStringType($value);
		}

		return new UnionType($types);
	}

}
