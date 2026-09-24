<?php

namespace Bug14990;

use Exception;
use function PHPStan\Testing\assertType;

class Thing {}

class Db
{
	// Throws are only INFERRED from the body (no @throws tag).
	public static function getThing(): Thing
	{
		if (random_int(0, 1) === 0) {
			throw new Exception('no data');
		}
		return new Thing();
	}

	public function getThingInstance(): Thing
	{
		if (random_int(0, 1) === 0) {
			throw new Exception('no data');
		}
		return new Thing();
	}
}

class Helper
{
	/** @throws Exception */
	public static function mightThrow(): void
	{
		if (random_int(0, 1)) {
			throw new Exception('boom');
		}
	}

	/** @throws \TypeError */
	public static function mightThrowError(): void
	{
		if (random_int(0, 1)) {
			throw new \TypeError('boom');
		}
	}
}

function getThing(): Thing
{
	if (random_int(0, 1) === 0) {
		throw new Exception('no data');
	}
	return new Thing();
}

/** @throws Exception */
function mightThrow(): void
{
	if (random_int(0, 1)) {
		throw new Exception('boom');
	}
}

function staticCall(): void
{
	$thing = null;

	try {
		$thing = Db::getThing();
		Helper::mightThrow();
	} catch (Exception $e) {
		assertType('Bug14990\Thing|null', $thing);
	}
}

function methodCall(Db $db): void
{
	$thing = null;

	try {
		$thing = $db->getThingInstance();
		Helper::mightThrow();
	} catch (Exception $e) {
		assertType('Bug14990\Thing|null', $thing);
	}
}

function functionCall(): void
{
	$thing = null;

	try {
		$thing = getThing();
		mightThrow();
	} catch (Exception $e) {
		assertType('Bug14990\Thing|null', $thing);
	}
}

function catchThrowable(): void
{
	$thing = null;

	try {
		$thing = Db::getThing();
		Helper::mightThrow();
	} catch (\Throwable $e) {
		assertType('Bug14990\Thing|null', $thing);
	}
}

function catchError(): void
{
	$thing = null;

	try {
		$thing = Db::getThing();
		Helper::mightThrowError();
	} catch (\Error $e) {
		assertType('Bug14990\Thing|null', $thing);
	}
}
