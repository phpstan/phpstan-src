<?php declare(strict_types = 1);

namespace Bug14990;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class Thing {}

class Db
{
	// Throws are only INFERRED from the body (no @throws tag).
	public static function getThing(): Thing
	{
		if (rand(0, 1) === 0) {
			throw new \Exception('no data');
		}
		return new Thing();
	}
}

class Helper
{
	/** @throws \Exception */
	public static function mightThrow(): void
	{
		if (rand(0, 1)) {
			throw new \Exception('boom');
		}
	}
}

function doFoo(): void
{
	$thing = null;

	try {
		$thing = Db::getThing();
		Helper::mightThrow();
	} catch (\Exception $e) {
		assertType('Bug14990\Thing|null', $thing);
		if ($thing === null) {
			echo 'thing not set';
		}
	}
}

function doBar(): void
{
	try {
		$thing = Db::getThing();
		Helper::mightThrow();
	} catch (\Exception $e) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $thing);
	}
}
