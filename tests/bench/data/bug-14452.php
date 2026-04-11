<?php declare(strict_types = 1);

namespace Bug14452;

use function PHPStan\Testing\assertType;

class MyBag
{

	public function getInt(string $key): int
	{
		return 0;
	}

	public function has(string $key): bool
	{
		return false;
	}

	/** @return bool|float|int|string|null */
	public function get(string $key)
	{
		return null;
	}

}

class Shared
{

	public static function calculateThings(
		?int $type,
		float $hours,
		float $minutes,
		float $seconds,
		?float $hourlyRate,
		?float $flatFee,
		?float $minimumCost,
	): float
	{
		return 0.0;
	}

}

/**
 * Performance test: many possibly-impure method calls inside conditional branches
 * should not cause exponential blowup in conditional expression creation.
 */
function test(MyBag $input): void
{
	$prestatie = rand(0, 1) === 0;

	$seconds = null;
	$minimum_seconds = null;
	$seconds_worked = null;
	$minutes = null;
	$minimum_minutes = null;
	$minutes_worked = null;
	$hours = null;

	$minimum_hours = null;
	$hours_worked = null;
	$hourly_rate = null;

	$flat_fee = round($input->getInt('flat_fee'), 2);
	$minimum_cost = round($input->getInt('minimum_cost'), 2);

	if ($prestatie) {
		$seconds = $seconds_worked = $input->getInt('seconds_worked');
		$minutes = $minutes_worked = $input->getInt('minutes_worked');
		$hours = $hours_worked = $input->getInt('hours_worked');

		$minimum_seconds = $input->getInt('minimum_seconds');
		$minimum_minutes = $input->getInt('minimum_minutes');
		$minimum_hours = $input->getInt('minimum_hours');

		if ($input->has('different_billing_time')) {
			$different_billing_time = 1;
			$seconds = $input->getInt('seconds');
			$minutes = $input->getInt('minutes');
			$hours = $input->getInt('hours');
		}

		$hourly_rate = round($input->getInt('hourly_rate'), 2);

		$subtotal = Shared::calculateThings(
			$input->get('prestation_type'),
			$hours,
			$minutes,
			$seconds,
			$hourly_rate,
			$flat_fee,
			$minimum_cost,
		);
	}

	$subtype = $prestatie ? $input->getInt('prestation_type') : $input->getInt('cost_type');
	assertType('int', $subtype);
	assertType('float', $flat_fee);
	assertType('float', $minimum_cost);
}
