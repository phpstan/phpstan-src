<?php // lint >= 8.1

declare(strict_types = 1);

namespace GenericBackedEnumTypehints;

use BackedEnum;

function bareIsNotMissingTypes(BackedEnum $e): BackedEnum
{
	return $e;
}

/**
 * @param BackedEnum<string> $e
 * @return BackedEnum<string>
 */
function withTypes(BackedEnum $e): BackedEnum
{
	return $e;
}
