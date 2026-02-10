<?php

namespace Bug162;

use function PHPStan\Testing\assertType;
use const PHP_VERSION;
use const PHP_VERSION_ID;

function lower(): void
{
	// add a upper bound, so we don't need to adjust
	// the test when PHPStan adds support for PHP8.6+
	if (PHP_VERSION_ID > 80599) {
		return;
	}

	// lower limit inferred from composer.json
	$x = PHP_VERSION_ID;
	assertType('int<80000, 80599>', $x);

	if (
		version_compare( PHP_VERSION, '8.4', '<' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80000, 80399>', $x);
	}

	if (
		version_compare( PHP_VERSION, '8.4', 'lt' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80000, 80399>', $x);
	}

	if (
		version_compare( PHP_VERSION, '8.4', '<=' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80000, 80400>', $x);
	}

	if (
		version_compare( PHP_VERSION, '8.4', 'le' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80000, 80400>', $x);
	}
}

function greater(): void
{
	if (
		version_compare( PHP_VERSION, '8.4', '>' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80401, max>', $x);
	}

	if (
		version_compare( PHP_VERSION, '8.4', 'gt' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80401, max>', $x);
	}

	if (
		version_compare( PHP_VERSION, '8.4', '>=' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80400, max>', $x);
	}
	if (
		version_compare( PHP_VERSION, '8.4', 'ge' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80400, max>', $x);
	}
}

function equal(): void
{
	if (
		version_compare( PHP_VERSION, '8.4', '=' )
	) {
		$x = PHP_VERSION_ID;
		assertType('80400', $x);
	}

	if (
		version_compare( PHP_VERSION, '8.4', '==' )
	) {
		$x = PHP_VERSION_ID;
		assertType('80400', $x);
	}

	if (
		version_compare( PHP_VERSION, '8.4', 'eq' )
	) {
		$x = PHP_VERSION_ID;
		assertType('80400', $x);
	}
}


function not(): void
{
	if (
		version_compare( PHP_VERSION, '8.4', '!=' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<min, 80399>|int<80401, max>', $x);
	}

	if (
		version_compare( PHP_VERSION, '8.4', '<>' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<min, 80399>|int<80401, max>', $x);
	}

	if (
		version_compare( PHP_VERSION, '8.4', 'ne' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<min, 80399>|int<80401, max>', $x);
	}
}

function inverseOperandLower(): void
{
	if (
		version_compare( '8.3.12', PHP_VERSION, '<' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<min, 80311>', $x);
	}

	if (
		version_compare( '8.3.12', PHP_VERSION, '>=' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80312, max>', $x);
	}

	if (
		version_compare( '8.3.12', PHP_VERSION, '>' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80313, max>', $x);
	}
}

function narrow(): void {
	if (PHP_VERSION_ID < 80000) {
		return;
	}

	if (
		version_compare( PHP_VERSION, '8.4', '<' )
	) {
		$x = PHP_VERSION_ID;
		assertType('int<80000, 80399>', $x);
	}
}
