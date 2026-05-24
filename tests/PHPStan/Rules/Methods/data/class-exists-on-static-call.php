<?php

namespace ClassExistsOnStaticCall;

use PluralizationRules;

function doFoo() {
	if (class_exists(PluralizationRules::class)) {
		PluralizationRules::set(static function ($number) {
			return PluralizationRules::get($number, 'sr');
		}, 'sr_Latn_BA');
	}
}
