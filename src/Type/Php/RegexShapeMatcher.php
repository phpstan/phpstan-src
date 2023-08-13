<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
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

	public function matchType(string $regex, TypeSpecifierContext $context): Type
	{
		$modifiers = $this->getModifiers($regex);

		if (str_contains($modifiers, 'n')) {
			return new ArrayType(new MixedType(), new StringType());
		}

		// add one capturing group to the end so all capture group keys
		// are present in the $matches
		// see https://3v4l.org/sOXbn
		$regex = preg_replace('~^(.)(.*)\K(\1\w*$)~', '|()$3', $regex);

		if (
			$regex === null
			|| @preg_match($regex, '', $matches, PREG_UNMATCHED_AS_NULL) === false
		) {
			return new ArrayType(new MixedType(), new StringType());
		}
		unset($matches[array_key_last($matches)]);

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach (array_keys($matches) as $key) {
			// atm we can't differentiate optional from mandatory groups based on the pattern.
			// So we assume all are optional
			$optional = true;

			if (is_string($key)) {
				$builder->setOffsetValueType(
					new ConstantStringType($key),
					new StringType(),
					$optional,
				);

				continue;
			}

			if ($context->true() && $key === 0) {
				$optional = false;
			}

			$builder->setOffsetValueType(
				new ConstantIntegerType($key),
				new StringType(),
				$optional,
			);
		}

		return $builder->getArray();
	}

	private function getModifiers(string $regex): string
	{
		$delimiter = substr($regex, 0, 1);
		$endDelimiterPosition = strrpos($regex, $delimiter);
		if ($endDelimiterPosition === false) {
			throw new ShouldNotHappenException();
		}

		return substr($regex, $endDelimiterPosition + 1);
	}

}
