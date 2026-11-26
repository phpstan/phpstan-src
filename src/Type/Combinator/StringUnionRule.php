<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\ScalarUnion;
use PHPStan\Type\TypeKind;
use function array_key_first;
use function array_push;
use function array_values;
use function count;
use function spl_object_id;

/**
 * Strings and their refinements - the members whose getStringRefinement() is
 * not null - merge by the cells of StringRefinement they hold, as
 * `non-empty-string|''` becomes `string`, and drop the constant strings they
 * hold. They take the place of the first of them.
 *
 * @internal
 */
final class StringUnionRule implements UnionRule
{

	public function getKindMask(): int
	{
		return TypeKind::STRING;
	}

	public function mergesConstantScalarValues(): bool
	{
		return false;
	}

	public function apply(array $types, array $normalizedGroups): array
	{
		$refinements = [];
		$strings = [];
		foreach ($types as $i => $type) {
			if ($type->getKindMask() !== TypeKind::STRING) {
				continue;
			}
			if ($type->getStringRefinement() !== null) {
				$refinements[$i] = $type;
				continue;
			}
			$strings[] = $type;
		}
		if ($refinements === []) {
			return $types;
		}

		$constantStrings = [];
		foreach ($strings as $type) {
			// a template stands for a type, not for the value of its bound
			if ($type->getConstantStrings() !== [$type] || $type->hasTemplateOrLateResolvableType()) {
				continue;
			}
			$constantStrings[] = $type;
		}
		if (count($refinements) === 1 && $constantStrings === []) {
			return $types;
		}

		[$mergedRefinements, $constantsLeft] = ScalarUnion::unionStrings(array_values($refinements), $constantStrings);
		$firstIndex = array_key_first($refinements);
		// the input keeps the constants alive, so their ids stay unique
		$removed = [];
		foreach ($constantStrings as $constantString) {
			$removed[spl_object_id($constantString)] = true;
		}
		foreach ($constantsLeft as $constantString) {
			unset($removed[spl_object_id($constantString)]);
		}
		$result = [];
		foreach ($types as $i => $type) {
			if ($i === $firstIndex) {
				array_push($result, ...$mergedRefinements);
				continue;
			}
			if (isset($refinements[$i])) {
				continue;
			}
			if (isset($removed[spl_object_id($type)])) {
				continue;
			}
			$result[] = $type;
		}

		return $result;
	}

}
