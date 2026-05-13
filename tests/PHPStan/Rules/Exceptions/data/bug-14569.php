<?php

namespace Bug14569;

use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;

try {
	DateInterval::createFromDateString('FAIL');
} catch (DateMalformedIntervalStringException $e) {
	var_dump($e::class);
	var_dump($e instanceof DateMalformedStringException);
}
