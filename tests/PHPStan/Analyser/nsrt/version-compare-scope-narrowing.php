<?php

namespace VersionCompareScopeNarrowing;

use function PHPStan\Testing\assertType;
use function version_compare;

// Three-argument form: version_compare(PHP_VERSION, '8.4', operator)
function threeArgForm(): void
{
	if (version_compare(PHP_VERSION, '8.0', '<')) {
		assertType('int<50207, 79999>', PHP_VERSION_ID);
	} else {
		assertType('int<80000, 80599>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0', '>=')) {
		assertType('int<80000, 80599>', PHP_VERSION_ID);
	} else {
		assertType('int<50207, 79999>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0', '>')) {
		assertType('int<80001, 80599>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0', '<=')) {
		assertType('int<50207, 80000>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0', 'lt')) {
		assertType('int<50207, 79999>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0', 'ge')) {
		assertType('int<80000, 80599>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.4.1', '<')) {
		assertType('int<50207, 80400>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.4.1', '>=')) {
		assertType('int<80401, 80599>', PHP_VERSION_ID);
	}
}

// version_compare with PHP_VERSION as second argument
function secondArgPhpVersion(): void
{
	if (version_compare('8.0', PHP_VERSION, '<')) {
		assertType('int<80001, 80599>', PHP_VERSION_ID);
	}

	if (version_compare('8.0', PHP_VERSION, '>=')) {
		assertType('int<50207, 80000>', PHP_VERSION_ID);
	}
}

// Two-argument form: version_compare(PHP_VERSION, '8.4') === 1
function twoArgFormIdentical(): void
{
	if (version_compare(PHP_VERSION, '8.0') === 1) {
		assertType('int<80001, 80599>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0') === -1) {
		assertType('int<50207, 79999>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0') >= 0) {
		assertType('int<80000, 80599>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0') === 0) {
		assertType('80000', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0') !== -1) {
		assertType('int<80000, 80599>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0') !== 1) {
		assertType('int<50207, 80000>', PHP_VERSION_ID);
	}
}

// eq and ne operators (three-argument form)
function eqNeOperators(): void
{
	if (version_compare(PHP_VERSION, '8.0', '==')) {
		assertType('80000', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0', 'eq')) {
		assertType('80000', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0', '!=')) {
		assertType('int<50207, 79999>|int<80001, 80599>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0', 'ne')) {
		assertType('int<50207, 79999>|int<80001, 80599>', PHP_VERSION_ID);
	}
}

// Two-argument form with comparison operators
function twoArgFormComparison(): void
{
	if (version_compare(PHP_VERSION, '8.0') < 0) {
		assertType('int<50207, 79999>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0') > 0) {
		assertType('int<80001, 80599>', PHP_VERSION_ID);
	}

	if (version_compare(PHP_VERSION, '8.0') <= 0) {
		assertType('int<50207, 80000>', PHP_VERSION_ID);
	}
}
