<?php

namespace Bug2313;

function safe_inc(&$variable, $increment=1) {
	if (isset($variable)) {
		$variable += $increment;
	} else {
		$variable = $increment;
	}
	return true;
}

function (): void {
	$data = array('apples' => array());

	safe_inc($data['apples']['count']);
	print_r($data);

	safe_inc($data['apples']['count']);
	print_r($data);
};
