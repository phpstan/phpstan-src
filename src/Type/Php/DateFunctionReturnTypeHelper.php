<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
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

class DateFunctionReturnTypeHelper
{

	public function getTypeFromFormatType(Type $formatType, bool $useMicrosec): ?Type
	{
		$types = [];
		foreach ($formatType->getConstantStrings() as $formatString) {
			$types[] = $this->buildReturnTypeFromFormat($formatString->getValue(), $useMicrosec);
		}

		if (count($types) === 0) {
			$types[] = $formatType->isNonEmptyString()->yes()
				? new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()])
				: new StringType();
		}

		$type = TypeCombinator::union(...$types);

		if ($type->isNumericString()->no() && $formatType->isNonEmptyString()->yes()) {
			$type = TypeCombinator::union($type, new IntersectionType([
				new StringType(), new AccessoryNonEmptyStringType()
			]));
		}

		return $type;
	}

	public function buildReturnTypeFromFormat(string $formatString, bool $useMicrosec): Type
	{
		// see see https://www.php.net/manual/en/datetime.format.php
		switch ($formatString) {
			case 'd':
				return $this->buildNumericRangeType(1, 31, zeroPad: true);
			case 'j':
				return $this->buildNumericRangeType(1, 31, zeroPad: false);
			case 'N':
				return $this->buildNumericRangeType(1, 7, zeroPad: false);
			case 'w':
				return $this->buildNumericRangeType(0, 6, zeroPad: false);
			case 'm':
				return $this->buildNumericRangeType(1, 12, zeroPad: true);
			case 'n':
				return $this->buildNumericRangeType(1, 12, zeroPad: false);
			case 't':
				return $this->buildNumericRangeType(28, 31, zeroPad: false);
			case 'L':
				return $this->buildNumericRangeType(0, 1, zeroPad: false);
			case 'g':
				return $this->buildNumericRangeType(1, 12, zeroPad: false);
			case 'G':
				return $this->buildNumericRangeType(0, 23, zeroPad: false);
			case 'h':
				return $this->buildNumericRangeType(1, 12, zeroPad: true);
			case 'H':
				return $this->buildNumericRangeType(0, 23, zeroPad: true);
			case 'I':
				return $this->buildNumericRangeType(0, 1, zeroPad: false);
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

}
