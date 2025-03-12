<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use function array_slice;
use function count;

final class ArrayHelper
{

	/**
	 * @param mixed[] $array
	 * @param non-empty-list<string> $path
	 */
	public static function unsetKeyAtPath(array &$array, array $path): void
	{
		[$head, $tail] = [$path[0], array_slice($path, 1)];

		if (count($tail) === 0) {
			unset($array[$head]);

		} elseif (isset($array[$head])) {
			self::unsetKeyAtPath($array[$head], $tail);
		}
	}

}
