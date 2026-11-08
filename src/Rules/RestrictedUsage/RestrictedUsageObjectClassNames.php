<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class RestrictedUsageObjectClassNames
{

	/**
	 * Unlike Type::getObjectClassNames(), this also returns the class names
	 * of the object parts of a union with non-object types like Foo|null.
	 *
	 * @return list<string>
	 */
	public static function get(Type $type): array
	{
		$classNames = $type->getObjectClassNames();
		if ($classNames !== [] || $type->isObject()->no()) {
			return $classNames;
		}

		return TypeCombinator::intersect($type, new ObjectWithoutClassType())->getObjectClassNames();
	}

	/**
	 * For the left side of `::`, which can also be a class-string.
	 *
	 * @return list<string>
	 */
	public static function getForStaticAccess(Type $type): array
	{
		return self::get(TypeCombinator::removeNull($type)->getObjectTypeOrClassStringObjectType());
	}

}
