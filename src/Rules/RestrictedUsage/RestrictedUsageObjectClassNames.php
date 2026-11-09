<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function array_values;

final class RestrictedUsageObjectClassNames
{

	/**
	 * Class names of the members of the type, so that the object part of a union
	 * is still checked when it is combined with null, false, string, ...
	 * Members without a class name contribute nothing.
	 *
	 * Iterates the members directly instead of intersecting with `object`,
	 * as this runs for every method call and property fetch.
	 *
	 * @return list<string>
	 */
	public static function get(Type $type): array
	{
		return self::collect($type, false);
	}

	/**
	 * Same as get(), for the class expression of a static access (`$expr::foo()`),
	 * where a member can also be a class-string.
	 *
	 * @return list<string>
	 */
	public static function getFromClassExpression(Type $type): array
	{
		return self::collect($type, true);
	}

	/**
	 * @return list<string>
	 */
	private static function collect(Type $type, bool $acceptClassStrings): array
	{
		$members = $type instanceof UnionType ? $type->getTypes() : [$type];

		$classNames = [];
		foreach ($members as $member) {
			if ($acceptClassStrings) {
				$member = $member->getObjectTypeOrClassStringObjectType();
			}

			foreach ($member->getObjectClassNames() as $className) {
				$classNames[$className] = $className;
			}
		}

		return array_values($classNames);
	}

}
