<?php

namespace DeadCatchDatePhpVersions;

use DateInterval;
use DateInvalidOperationException;
use DateMalformedStringException;
use DateTime;
use const PHP_VERSION_ID;

class Foo
{

	public function dateTimeSub(DateTime $dateTime, DateInterval $interval): void
	{
		if (PHP_VERSION_ID >= 80300) {
			try {
				$dateTime->sub($interval);
			} catch (DateInvalidOperationException $e) {
			}
		}

		if (PHP_VERSION_ID < 80300) {
			try {
				$dateTime->sub($interval);
			} catch (DateInvalidOperationException $e) {
			}
		}
	}

	public function dateTimeModify(DateTime $dateTime, string $modifier): void
	{
		if (PHP_VERSION_ID >= 80300) {
			try {
				$dateTime->modify($modifier);
			} catch (DateMalformedStringException $e) {
			}
		}

		if (PHP_VERSION_ID < 80300) {
			try {
				$dateTime->modify($modifier);
			} catch (DateMalformedStringException $e) {
			}
		}
	}

}
