<?php

random_int(0, 0);
random_int(0, 1);
random_int(-1, 0);
random_int(-1, 1);

random_int(1, 0);
random_int(0, -1);

random_int(0, random_int(-10, -1));
random_int(0, random_int(-10, 10));
random_int(0, random_int(0, 10)); // ok

random_int(random_int(1, 10), 0);
random_int(random_int(-10, 10), 0);
random_int(random_int(-10, 0), 0); // ok

random_int(random_int(-5, 1), random_int(0, 5));
random_int(random_int(-5, 0), random_int(-1, 5));

/** @var int */
$x = foo();
/** @var int */
$y = bar();

random_int($x, $y);
random_int(0, $x);
random_int($x, random_int(0, PHP_INT_MAX));
random_int(random_int(PHP_INT_MIN, 0), $x);

random_int(PHP_INT_MAX, PHP_INT_MIN); // @todo this should error
