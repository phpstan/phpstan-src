<?php declare(strict_types = 1);

namespace PHPStan\Type;

class TypeTraverserHelper
{

	public static function hasUnresolveableType(Type $baseType): bool
	{
		$hasUnresolveableType = false;

		TypeTraverser::map($baseType, static function (Type $type, callable $traverse) use (&$hasUnresolveableType): Type {
			if (
				$type instanceof ErrorType
				|| ($type instanceof NeverType && !$type->isExplicit())
			) {
				$hasUnresolveableType = true;

				return $type;
			}

			return $traverse($type);
		});

		return $hasUnresolveableType;
	}

}
