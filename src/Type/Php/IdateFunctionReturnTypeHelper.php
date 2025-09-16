<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

#[AutowiredService]
final class IdateFunctionReturnTypeHelper
{

	public function getTypeFromFormatType(Type $formatType): ?Type
	{
		$types = [];
		foreach ($formatType->getConstantStrings() as $formatString) {
			$types[] = $this->buildReturnTypeFromFormat($formatString->getValue());
		}

		if ($types === []) {
			return null;
		}

		return TypeCombinator::union(...$types);
	}

	public function buildReturnTypeFromFormat(string $formatString): Type
	{
		// see https://www.php.net/idate and https://www.php.net/manual/de/datetime.format
		switch ($formatString) {
			case 'd':
			case 'j':
				return IntegerRangeType::fromInterval(1, 31);
			case 'h':
			case 'g':
				return IntegerRangeType::fromInterval(1, 12);
			case 'H':
			case 'G':
				return IntegerRangeType::fromInterval(0, 23);
			case 'i':
				return IntegerRangeType::fromInterval(0, 59);
			case 'I':
				return IntegerRangeType::fromInterval(0, 1);
			case 'L':
				return IntegerRangeType::fromInterval(0, 1);
			case 'm':
			case 'n':
				return IntegerRangeType::fromInterval(1, 12);
			case 'N':
				return IntegerRangeType::fromInterval(1, 7);
			case 's':
				return IntegerRangeType::fromInterval(0, 59);
			case 't':
				return IntegerRangeType::fromInterval(28, 31);
			case 'w':
				return IntegerRangeType::fromInterval(0, 6);
			case 'W':
				return IntegerRangeType::fromInterval(1, 53);
			case 'y':
				return IntegerRangeType::fromInterval(0, 99);
			case 'z':
				return IntegerRangeType::fromInterval(0, 365);
			case 'B':
			case 'o':
			case 'U':
			case 'Y':
			case 'Z':
				return new IntegerType();
			default:
				return new ConstantBooleanType(false);
		}
	}

}
