<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Type\Type;

/**
 * The out type of a variadic by-ref parameter describes a single argument: that is how it is applied
 * at the call site, where NodeScopeResolver writes the out type - or the declared type when there is
 * no @param-out - back to each argument individually. Inside the body the variable holds the packed
 * array of those arguments instead, so the element type is the side to compare against the out type.
 *
 * @internal
 */
final class VariadicByRefParameterOutType
{

	/**
	 * Returns the type to compare against the out type, or null when there is nothing to compare.
	 *
	 * Null means the variable no longer holds an array. Rebinding the packed variable discards the
	 * references it held, so PHP writes nothing back to any caller and no out value is left to check.
	 * An array is still compared, because a write through an offset - `$refs[0] = ...`, which does
	 * reach the caller - leaves the variable as an array too, and the two are indistinguishable here.
	 */
	public static function elementType(Type $packedType): ?Type
	{
		if (!$packedType->isArray()->yes()) {
			return null;
		}

		return $packedType->getIterableValueType();
	}

}
