<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function min;

/**
 * The types of the values a call passes, with each unpacked arg (`...$arg`)
 * expanded into one value per item of its array.
 */
#[ReferencedByTurboExtension(key: 'unpackedArgumentTypes')]
final class UnpackedArgumentTypes
{

	/**
	 * Returns the type of each value whose position is known, and the type of
	 * the values after them when their number is not known. That is the case
	 * after an unpacked arg that is not a list shape, or that is a list shape
	 * of more than one size.
	 *
	 * @param list<Type> $argTypes
	 * @param list<bool> $unpacked whether each arg is unpacked
	 * @return array{list<Type>, Type|null}
	 */
	public static function expand(array $argTypes, array $unpacked): array
	{
		$types = [];
		$restTypes = [];
		foreach ($argTypes as $i => $argType) {
			if (!$unpacked[$i]) {
				if ($restTypes !== []) {
					// PHP does not allow a positional arg after an unpacked one
					$restTypes[] = $argType;
					continue;
				}

				$types[] = $argType;
				continue;
			}

			if ($restTypes !== []) {
				$restTypes[] = $argType->getIterableValueType();
				continue;
			}

			$constantArrays = $argType->getConstantArrays();
			$shapesAreLists = true;
			foreach ($constantArrays as $constantArray) {
				if ($constantArray->isList()->yes()) {
					continue;
				}

				// string keys are passed as named args
				$shapesAreLists = false;
				break;
			}

			if (!$shapesAreLists || $constantArrays === []) {
				$restTypes[] = $argType->getIterableValueType();
				continue;
			}

			$requiredCounts = [];
			foreach ($constantArrays as $constantArray) {
				// the optional keys of a list are its last keys
				$requiredCounts[] = count($constantArray->getValueTypes()) - count($constantArray->getOptionalKeys());
			}
			$requiredCount = min($requiredCounts);

			for ($position = 0; $position < $requiredCount; $position++) {
				$positionTypes = [];
				foreach ($constantArrays as $constantArray) {
					$positionTypes[] = $constantArray->getValueTypes()[$position];
				}
				$types[] = TypeCombinator::union(...$positionTypes);
			}

			foreach ($constantArrays as $constantArray) {
				$valueTypes = $constantArray->getValueTypes();
				$valueTypesCount = count($valueTypes);
				for ($position = $requiredCount; $position < $valueTypesCount; $position++) {
					$restTypes[] = $valueTypes[$position];
				}
			}
		}

		return [$types, $restTypes === [] ? null : TypeCombinator::union(...$restTypes)];
	}

}
