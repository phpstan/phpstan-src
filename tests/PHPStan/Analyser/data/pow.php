<?php

namespace PowFunction;

use function PHPStan\Analyser\assertType;

function ($a, $b): void {
	assertType('(float|int)', pow($a, $b));
};

function (int $a, int $b): void {
	assertType('(float|int)', pow($a, $b));
};

function (\GMP $a, \GMP $b): void {
	assertType('GMP', pow($a, $b));
};

function (\stdClass $a, \GMP $b): void {
	assertType('GMP|stdClass', pow($a, $b));
};
