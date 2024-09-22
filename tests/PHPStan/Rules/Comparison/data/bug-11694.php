<?php

namespace Bug11694;

/**
 * @param int $value
 * @return int<10, 20>
 */
function test(int $value) : int {
	if ($value > 5) {
		return 10;
	}

	return 20;
}

if (3 == test(3)) {}
if (test(3) == 3) {}

if (13 == test(3)) {}
if (test(3) == 13) {}

if (23 == test(3)) {}
if (test(3) == 23) {}

if (null == test(3)) {}
if (test(3) == null) {}

if ('13foo' == test(3)) {}
if (test(3) == '13foo') {}

if (' 3' == test(3)) {}
if (test(3) == ' 3') {}

if (' 13' == test(3)) {}
if (test(3) == ' 13') {}

if (' 23' == test(3)) {}
if (test(3) == ' 23') {}

if (true == test(3)) {}
if (test(3) == true) {}

if (false == test(3)) {}
if (test(3) == false) {}
