<?php

namespace Bug5751;

/**
 * @template paramType
 * @param paramType $var
 * @return paramType
 */
function sensitive_mask_var($var)
{
	if (is_array($var)) {

	} elseif (is_object($var)) {

	}

	return $var;
}
