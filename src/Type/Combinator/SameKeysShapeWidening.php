<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_keys;
use function count;
use function implode;
use function is_int;

/**
 * Shapes with the same keys, each required or optional alike, merge into one
 * shape whose value at each key is the union of theirs, widened. Unlike the
 * steps after it, this keeps which keys go together, so a list of similar
 * records keeps its shape.
 *
 * @internal
 */
final class SameKeysShapeWidening implements WideningStep
{

	public function apply(array $types): array
	{
		$groups = [];
		$others = [];
		foreach ($types as $type) {
			$shape = ArrayWidening::asShape($type);
			if ($shape === null) {
				$others[] = $type;
				continue;
			}
			$signature = [$shape->isList()->yes() ? 'L' : 'A'];
			foreach ($shape->getKeyTypes() as $i => $keyType) {
				$key = $keyType->getValue();
				$signature[] = ($shape->isOptionalKey($i) ? '?' : '!') . (is_int($key) ? 'i' : 's') . $key;
			}
			$groups[implode(',', $signature)][] = $shape;
		}

		$result = $others;
		$merged = false;
		foreach ($groups as $group) {
			if (count($group) === 1) {
				$result[] = $group[0];
				continue;
			}
			$result[] = ArrayWidening::widenValueTypes(self::merge($group));
			$merged = true;
		}

		return $merged ? $result : $types;
	}

	/**
	 * Merges the arrays like a chain of mergeWith() calls. They have the same
	 * keys in the same order, so the chain unions the values at each key. At a
	 * key where every array holds only constant scalars, one union of all the
	 * values is the same as the chain of unions, which was quadratic in the size
	 * of its growing result - the chain only merges the rest of the shape, with a
	 * placeholder at that key.
	 *
	 * @param non-empty-list<ConstantArrayType> $arrays
	 */
	private static function merge(array $arrays): ConstantArrayType
	{
		$constantScalarValueTypes = [];
		if (!$arrays[0] instanceof TemplateType) {
			foreach (array_keys($arrays[0]->getValueTypes()) as $i) {
				$constantScalarValueTypes[$i] = [];
			}
		}
		foreach ($arrays as $array) {
			foreach ($array->getValueTypes() as $i => $valueType) {
				if (!array_key_exists($i, $constantScalarValueTypes)) {
					continue;
				}
				// the classes whose union is a plain concatenation of their
				// members, deduplicated - not ones merely holding a constant scalar
				$members = $valueType instanceof UnionType && !$valueType instanceof BenevolentUnionType && !$valueType instanceof TemplateType
					? $valueType->getTypes()
					: [$valueType];
				foreach ($members as $member) {
					if (!$member instanceof ConstantScalarType || $member instanceof TemplateType) { // @phpstan-ignore phpstanApi.instanceofType
						unset($constantScalarValueTypes[$i]);
						continue 2;
					}
				}
				$constantScalarValueTypes[$i][] = $valueType;
			}
		}

		$placeholder = new NeverType(true);
		$merged = null;
		foreach ($arrays as $array) {
			if ($constantScalarValueTypes !== []) {
				$valueTypes = $array->getValueTypes();
				foreach (array_keys($constantScalarValueTypes) as $i) {
					$valueTypes[$i] = $placeholder;
				}
				$array = new ConstantArrayType($array->getKeyTypes(), $valueTypes, $array->getNextAutoIndexes(), $array->getOptionalKeys(), $array->isList(), $array->getUnsealedTypes());
			}
			$merged = $merged === null ? $array : $merged->mergeWith($array);
		}

		if ($constantScalarValueTypes === []) {
			return $merged;
		}

		$valueTypes = $merged->getValueTypes();
		foreach ($constantScalarValueTypes as $i => $types) {
			$valueTypes[$i] = TypeCombinator::union(...$types);
		}

		return new ConstantArrayType($merged->getKeyTypes(), $valueTypes, $merged->getNextAutoIndexes(), $merged->getOptionalKeys(), $merged->isList(), $merged->getUnsealedTypes());
	}

}
