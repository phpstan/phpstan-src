<?php

namespace Bug14990Rule;

use Exception;

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
}

$thing = null;

try {
	$thing = Db::getThing();
	// Because this call has a DECLARED @throws tag, PHPStan treats only it as the throw point
	// and ignores that the getThing() assignment above can also throw (its throws are merely
	// inferred). So in the catch it types $thing as Thing instead of Thing|null, even though
	// reaching the catch via getThing() throwing would leave $thing === null.
	Helper::mightThrow();
} catch (Exception $e) {
	if ($thing === null) {
		echo 'thing not set';
	}
}
