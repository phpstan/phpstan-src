<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function count;
use function date;
use function sprintf;

class DateFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'date';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return new StringType();
		}
		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$constantStrings = TypeUtils::getConstantStrings($argType);

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
			$types[] = ConstantTypeHelper::getTypeFromValue(date($constantString->getValue()));
		}

		$type = TypeCombinator::union(...$types);
		if ($type->isNumericString()->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNumericStringType(),
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
				$string = sprintf('%02s', $string);
			}

			$types[] = new ConstantStringType($string);
		}

		return new UnionType($types);
	}

}
