<?php

namespace Bug3801;

/**
 * @param int $int
 * @return array{bool, null}|array{null, bool}
 */
function do_foo($int)
{
	if ($int < 1)
	{
		return [true, null];
	}
	elseif ($int % 2)
	{
		return [false, true];
	}
	else
	{
		return [false, false];
	}
}
