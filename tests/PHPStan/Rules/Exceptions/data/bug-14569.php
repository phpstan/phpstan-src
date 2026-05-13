<?php // lint >= 8.0

namespace Bug14569;

use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;

try {
	DateInterval::createFromDateString('FAIL');
} catch (DateMalformedIntervalStringException $e) {
}
