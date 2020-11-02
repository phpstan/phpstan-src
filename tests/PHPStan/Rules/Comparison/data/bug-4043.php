<?php

namespace Bug4043;

function number(int $diff, string $s): string
{

}

function (): void {
	$uppedDate = new \DateTime('now -1s');
	$diff = $uppedDate->diff(new \DateTime());
	assert($diff !== false);

	$mdiff = (int)$diff->format('%m');
	$ddiff = (int)$diff->format('%d');
	$hdiff = (int)$diff->format('%H');
	$idiff = (int)$diff->format('%i');

	$m = $mdiff ? number($mdiff, 'месяц|месяца|месяцев') : '';
	$d = $ddiff ? number($ddiff, 'день|дня|дней') : '';
	$h = $hdiff ? number($hdiff, 'час|часа|часов') : '';
	$i = $idiff ? number($idiff, 'минута|минуты|минут') : '';

	$content = array_filter([$m, $d, $h, $i]);
	if ($content) {
		echo 1;
	}
};

function (): void {
	$a = [];
	if (rand(0, 1)) {
		$a[] = 1;
	}
	if ($a) {

	}
};

function (): void {
	$a = [];
	if ($a) {

	}
};

function (): void {
	$a = [1];
	if ($a) {

	}
};
