<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PHPStan\Type\Type;

final class IllegalOffsetTypeHelper
{

	/**
	 * On PHP 8.0+ using an array or an object as an array/string offset throws TypeError
	 * (https://wiki.php.net/rfc/engine_warnings). Float, bool and null keys are coerced
	 * with at most a deprecation, resource keys with a warning - no TypeError.
	 */
	public static function mayOffsetThrowTypeError(Type $offsetType): bool
	{
		return !$offsetType->isArray()->no() || !$offsetType->isObject()->no();
	}

}
