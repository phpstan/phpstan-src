<?php

namespace Bug7140;

/**
 * @param mixed[] $arr
 */
function foo(array $arr): void
{
	for ($i = 1; $i <= 2; $i++) {
		if (!isset($arr['a_' . $i])) {
			continue;
		}

		if (isset($arr['b_' . $i])) {
		}

		if (isset($arr['c_' . $i])) {
		}

		if (isset($arr['d_' . $i])) {
		}

		if (isset($arr['e_' . $i])) {
		}

		if (isset($arr['f_' . $i])) {
		}

		if (isset($arr['g_' . $i])) {
		}

		if (isset($arr['h_' . $i])) {
		}

		if (isset($arr['i_' . $i])) {
		}

		if (isset($arr['j_' . $i])) {
		}

		if (isset($arr['k_' . $i])) {
		}
		echo 'test';
	}
}
