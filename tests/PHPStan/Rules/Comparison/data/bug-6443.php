<?php

namespace Bug6443;

/**
 * String utilities for frequent tasks
 */
class StringUtils
{
	public static function stringify(
		mixed $anyValue,
		string $arrayConcatChar = ", "
	): string {
		// this cases are always empty strings
		if ($anyValue === null || (is_array($anyValue) && !$anyValue)) {
			return "";
		}
		if (is_array($anyValue)) {
			return implode($arrayConcatChar, $anyValue);
		}
		return "blub";
	}
}
