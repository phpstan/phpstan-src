<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\OversizedArrayType;
use function count;

/**
 * An array with values known at more offsets than the limit stops tracking
 * them: its offset-value accessories give way to the oversized accessory.
 *
 * @internal
 */
final class OffsetValueLimitIntersectRule implements IntersectRule
{

	private const HAS_OFFSET_VALUE_TYPES_LIMIT = 32;

	public function apply(array $types): array
	{
		$others = [];
		foreach ($types as $type) {
			if ($type instanceof HasOffsetValueType) {
				continue;
			}
			$others[] = $type;
		}
		if (count($types) - count($others) <= self::HAS_OFFSET_VALUE_TYPES_LIMIT) {
			return $types;
		}

		$others[] = new OversizedArrayType();
		return $others;
	}

}
