<?php

namespace Bug11119;

use DateTime;

function (): void {
	$earliest = array_reduce(
		[
			new DateTime('+1 day'),
			new DateTime('+2 day'),
			new DateTime('+3 day'),
		],
		static fn (?DateTime $carry, DateTime $time): DateTime => ($carry instanceof DateTime && $carry < $time) ? $carry : $time,
		null
	);
};
