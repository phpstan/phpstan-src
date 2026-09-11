<?php

declare(strict_types=1);

namespace Bug12666;

use function PHPStan\Testing\assertType;


/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function add(int $x0, int $xn): float
{
	$xi = $x0;

	assertType('int<1, max>', $xn);

	while ($xi < $xn) {
		$xi += 1;
	}

	assertType('int<1, max>', $xn);

	return $xi;
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function addFrac(int $x0, int $xn): float
{
	$xi = $x0;

	assertType('int<1, max>', $xn);

	while ($xi < $xn) {
		$xi += 0.1;
	}

	assertType('int<1, max>', $xn);

	return $xi;
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function sub(int $x0, int $xn): float
{
	$xi = $x0;

	assertType('int<1, max>', $xn);

	while ($xi < $xn) {
		$xi -= 1;
	}

	assertType('int<1, max>', $xn);

	return $xi;
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function subFrac(int $x0, int $xn): float
{
	$xi = $x0;

	assertType('int<1, max>', $xn);

	while ($xi < $xn) {
		$xi -= 0.1;
	}

	assertType('int<1, max>', $xn);

	return $xi;
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function mul(int $x0, int $xn): float
{
	$xi = $x0;

	assertType('int<1, max>', $xn);

	while ($xi < $xn) {
		$xi *= 2;
	}

	assertType('int<1, max>', $xn);

	return $xi;
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function mulFrac(int $x0, int $xn): float
{
	$xi = $x0;

	assertType('int<1, max>', $xn);

	while ($xi < $xn) {
		$xi *= 1.1;
	}

	assertType('int<1, max>', $xn);

	return $xi;
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function div(int $x0, int $xn): float
{
	$xi = $x0;

	assertType('int<1, max>', $xn);

	while ($xi < $xn) {
		$xi /= 2;
	}

	assertType('int<1, max>', $xn);

	return $xi;
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function divFrac(int $x0, int $xn): float
{
	$xi = $x0;

	assertType('int<1, max>', $xn);

	while ($xi < $xn) {
		$xi /= 1.1;
	}

	assertType('int<1, max>', $xn);

	return $xi;
}
