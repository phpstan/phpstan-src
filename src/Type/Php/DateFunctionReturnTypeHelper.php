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
use function preg_match;
use const STR_PAD_LEFT;

class DateFunctionReturnTypeHelper
{

	public function getTypeFromFormatType(Type $formatType, bool $useMicrosec): ?Type
	{
		$constantStrings = $formatType->getConstantStrings();

		if (count($constantStrings) === 0) {
			return new StringType();
		}

		if (count($constantStrings) === 1) {
			$constantString = $constantStrings[0]->getValue();

			// see see https://www.php.net/manual/en/datetime.format.php
			switch ($constantString) {
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
			}
		}

		$types = [];
		foreach ($constantStrings as $constantString) {
			$types[] = new ConstantStringType(date($constantString->getValue()));
		}

		$type = TypeCombinator::union(...$types);
		if ($type->isNumericString()->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNumericStringType(),
			]);
		}

		if ($type->isNonFalsyString()->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}

		if ($type->isNonEmptyString()->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]);
		}

		if ($type->isNonEmptyString()->no()) {
			return new ConstantStringType('');
		}

		return new StringType();
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
