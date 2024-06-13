<?php // onlyif PHP_INT_SIZE == 8

use function PHPStan\Testing\assertType;

function (int $min) {
	\assert($min === 10 || $min === 15);
	assertType('int<10, 20>', random_int($min, 20));
};

function (int $min) {
	\assert($min <= 0);
	assertType('int<min, 20>', random_int($min, 20));
};

function (int $max) {
	\assert($max >= 0);
	assertType('int<0, max>', random_int(0, $max));
};

function (int $i) {
	assertType('int', random_int($i, $i));
};

assertType('0', random_int(0, 0));
assertType('int<-9223372036854775808, 9223372036854775807>', random_int(PHP_INT_MIN, PHP_INT_MAX));
assertType('int<0, 9223372036854775807>', random_int(0, PHP_INT_MAX));
assertType('int<-9223372036854775808, 0>', random_int(PHP_INT_MIN, 0));
assertType('int<-1, 1>', random_int(-1, 1));
assertType('int<0, 30>', random_int(0, random_int(0, 30)));
assertType('int<0, 100>', random_int(random_int(0, 10), 100));

assertType('*NEVER*', random_int(10, 1));
assertType('*NEVER*', random_int(2, random_int(0, 1)));
assertType('int<0, 1>', random_int(0, random_int(0, 1)));
assertType('*NEVER*', random_int(random_int(0, 1), -1));
assertType('int<0, 1>', random_int(random_int(0, 1), 1));

assertType('int<-5, 5>', random_int(random_int(-5, 0), random_int(0, 5)));
assertType('int<-9223372036854775808, 9223372036854775807>', random_int(random_int(PHP_INT_MIN, 0), random_int(0, PHP_INT_MAX)));

assertType('int<-5, 5>', rand(-5, 5));
assertType('int<0, max>', rand());
