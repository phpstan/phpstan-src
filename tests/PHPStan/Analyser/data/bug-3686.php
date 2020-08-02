<?php

namespace Bug3686;

/**
 * @param mixed $request
 * @return void
 */
function fred($request)
{
	$keys = '';
	foreach ($request as $index)
	{
		foreach ($index as $field)
		{
			if (isset($keys[$field]))
			{
				$keys[$field] = 0;
			}
		}
	}
}

/**
 * @param int[][] $a
 */
function replaceStringWithZero(array $a) : string {
	$keys = 'agfsdafsafdrew1231414';

	foreach ($a as $b) {
		foreach ($b as $c) {
			if (isset($keys[$c])) {
				$keys[$c] = "0";
			}
		}
	}

	return $keys;
}
