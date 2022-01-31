<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use function floor;
use function round;

class TimeHelper
{

	public static function humaniseFractionalSeconds(float $fractionalSeconds): string
	{
		$hoursAsString = $minutesAsString = $secondsAsString = '';
		if ($fractionalSeconds < 5) {
			// milliseconds as seconds
			$secondsAsString = round($fractionalSeconds, 3) . 's';
		} else {
			$hours = floor($fractionalSeconds / 3600);
			$minutes = floor((int) ($fractionalSeconds / 60) % 60);
			$seconds = (int) $fractionalSeconds % 60;

			if ($hours > 0) {
				$hoursAsString = $hours . 'h';
			}
			if ($minutes > 0) {
				$minutesAsString = $minutes . 'm';
			}
			if ($seconds > 0) {
				$secondsAsString = $seconds . 's';
			}
		}

		return $hoursAsString . $minutesAsString . $secondsAsString;
	}

}
