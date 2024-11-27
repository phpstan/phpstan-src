<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute;
use function is_int;
use function is_string;

final class DeprecatedAttributeHelper
{

	/**
	 * @param list<ReflectionAttribute> $attributes
	 */
	public static function getDeprecatedDescription(array $attributes): ?string
	{
		$deprecated = ReflectionAttributeHelper::filterAttributesByName($attributes, 'Deprecated');
		foreach ($deprecated as $attr) {
			$arguments = $attr->getArguments();
			foreach ($arguments as $i => $arg) {
				if (!is_string($arg)) {
					continue;
				}

				if (is_int($i)) {
					if ($i !== 0) {
						continue;
					}

					return $arg;
				}

				if ($i !== 'message') {
					continue;
				}

				return $arg;
			}
		}

		return null;
	}

}
