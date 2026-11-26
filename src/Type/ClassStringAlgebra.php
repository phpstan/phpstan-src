<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Generic\GenericClassStringType;

/**
 * The intersection of two generic class strings: the tryIntersect() of
 * GenericClassStringType.
 *
 * @internal
 */
#[ReferencedByTurboExtension(key: 'classStringAlgebra')]
final class ClassStringAlgebra
{

	/**
	 * `class-string<A> & class-string<B>`, neither holding the other, is
	 * `class-string<A & B>`.
	 *
	 * @see Type::tryIntersect()
	 */
	public static function tryIntersect(GenericClassStringType $type, Type $otherType): ?Type
	{
		if (
			!$otherType instanceof GenericClassStringType
			|| $otherType->isSuperTypeOf($type)->yes()
			|| !$type->isSuperTypeOf($otherType)->maybe()
		) {
			return null;
		}

		return new GenericClassStringType(TypeCombinator::intersect($type->getGenericType(), $otherType->getGenericType()));
	}

}
