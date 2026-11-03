<?php

namespace LooseConstComparisonPhp8;

use function PHPStan\Testing\assertType;

function doFoo() {
	assertType('true', 0 == "0");
	assertType('true', 0 == "0.0");
	assertType('false', 0 == "foo");
	assertType('false', 0 == "");
	assertType('true', 42 == " 42");
	assertType('false', 42 == "42foo");

	assertType('false', 0.0 == "");
	assertType('false', 42.0 == "42foo");
	assertType('false', 42 == "42.0foo");
	assertType('false', 42.1 == "42.0foo");
	assertType('false', 42.0 == "42.0foo");
}

function comparisons(): void
{
	assertType('false', 'abc' < 1);
	assertType('false', 'abc' <= 0);
	assertType('true', 'abc' > 1);
	assertType('true', 'abc' >= 0);
	assertType('1', 'abc' <=> 1);
	assertType('-1', 1 <=> 'abc');
	assertType('1', '1abc' <=> 1);
	assertType('-1', 1.5 <=> '1.5abc');
	assertType('true', '1 ' == '1');
	assertType('true', '1 ' == 1);
	assertType('true', "1\n" == '1');
	assertType('true', ' 1' == '1');
	assertType('false', '10 ' < '9');
	assertType('1', '10 ' <=> '9');
	assertType('false', 1 == '1.5abc');
	assertType('false', 1000 == '1e3abc');
	assertType('true', '1e3' == '1000');
	assertType('true', '0.1' == '0.10');
	assertType('false', 'abc' == 0);
	assertType('false', null == '0');
	assertType('true', null < -1);
	assertType('-1', null <=> -1);
	assertType('-1', null <=> NAN);
	assertType('false', null == NAN);
	assertType('0', true <=> 'abc');
	assertType('false', NAN == NAN);
	assertType('true', NAN != NAN);
	assertType('false', NAN < 1);
	assertType('false', 1 < NAN);
	assertType('false', NAN <= NAN);
	assertType('1', NAN <=> 1);
	assertType('1', 1 <=> NAN);
	assertType('1', NAN <=> NAN);
	assertType('false', NAN == 'NAN');
	assertType('1', NAN <=> 'abc');
	assertType('true', NAN == true);
	assertType('true', INF == 'INF');
	assertType('0', INF <=> 'INF');
	assertType('true', INF == INF);
	assertType('true', -INF < PHP_INT_MIN);
	assertType('true', INF > PHP_INT_MAX);
	assertType('true', 9223372036854775807 == 9223372036854775807.0);
	assertType('false', '9223372036854775808' == '9223372036854775809');
	assertType('-1', '9223372036854775808' <=> '9223372036854775809');
	assertType('false', '1e1000' == '1e1001');
	assertType('1', '9223372036854775808' <=> 1);
	assertType('true', -0.0 == 0);
	assertType('0', -0.0 <=> 0.0);
	assertType('true', '-0' == '0');
}
