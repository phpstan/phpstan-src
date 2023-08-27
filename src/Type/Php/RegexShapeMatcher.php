<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function array_key_last;
use function array_keys;
use function is_string;
use function preg_match;
use function preg_replace;
use function str_contains;
use function strrpos;
use function substr;
use const PREG_UNMATCHED_AS_NULL;

final class RegexShapeMatcher
{

	public function matchType(string $regex, ?ConstantIntegerType $flagsType, TypeSpecifierContext $context): Type
	{
		$flags = PREG_UNMATCHED_AS_NULL;
		if ($flagsType !== null) {
			$flags = $flags | $flagsType->getValue();
		}
		// add one capturing group to the end so all capture group keys
		// are present in the $matches
		// see https://3v4l.org/sOXbn, https://3v4l.org/3SdDM
		$regex = preg_replace('~^(.)(.*)\K(\1\w*$)~', '|(?<phpstan_named_capture_group_last>)$3', $regex);

		if (
			$regex === null
			|| @preg_match($regex, '', $matches, $flags) === false
		) {
			return new ArrayType(new MixedType(), new StringType());
		}
		unset($matches[array_key_last($matches)]);
		unset($matches['phpstan_named_capture_group_last']);

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($matches as $key => $value) {
			// atm we can't differentiate optional from mandatory groups based on the pattern.
			// So we assume all are optional
			$optional = true;

			$keyType = $this->getKeyType($key);
			$valueType = $this->getValueType($value, $flags);

			if ($context->true() && $key === 0) {
				$optional = false;
			}

			$builder->setOffsetValueType(
				$keyType,
				$valueType,
				$optional,
			);
		}

		return $builder->getArray();
	}

	private function getKeyType(int|string $key): Type
	{
		if (is_string($key)) {
			return new ConstantStringType($key);
		}

		return new ConstantIntegerType($key);
	}

	private function getValueType(mixed $value, int $flags): Type {
		if (($flags & PREG_OFFSET_CAPTURE) !== 0) {
			$builder = ConstantArrayTypeBuilder::createEmpty();

			$builder->setOffsetValueType(
				new ConstantIntegerType(0),
				new StringType(),
			);
			$builder->setOffsetValueType(
				new ConstantIntegerType(1),
				IntegerRangeType::fromInterval(0, null)
			);

			return $builder->getArray();
		}

		return new StringType();
	}

}
