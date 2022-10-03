<?php

namespace Bug8097;

function snooze($seconds)
{
	$t = time_nanosleep($seconds, 0);
	while (is_array($t)) {
		$t = time_nanosleep($t['seconds'], $t['nanoseconds']);
	}
}
