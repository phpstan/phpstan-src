<?php

namespace Bug7077;

function date_add(string $pd, ?int $nr, ?string $date): ?string {
	if ($date === null || $nr === null) {
		return null;
	}

	$interval = new \DateInterval('P' . abs($nr) . $pd);

	$dt = new \DateTime($date);
	if ($nr >= 0) {
		$dt->add($interval);
	} else {
		$dt->sub($interval);
	}

	return $dt->format('Y-m-d H:i:s');
}

function date_add_day(?string $date, ?int $days): ?string {
	return date_add('D', $days, $date);
}

function date_add_month(?string $date, ?int $months): ?string {
	return date_add('M', $months, $date);
}
