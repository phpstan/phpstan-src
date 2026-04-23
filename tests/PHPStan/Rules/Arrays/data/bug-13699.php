<?php declare(strict_types = 1);

namespace Bug13699;

$array = [1,5,4,8];

$records = [];
foreach ($array as $value) {
	$records[$value]['abc'] = true;
	$records[$value]['def'][$value] = true;
	if (! isset($records[$value]['FFF'])) {
		$records[$value]['FFF'] = true;
	}
}
