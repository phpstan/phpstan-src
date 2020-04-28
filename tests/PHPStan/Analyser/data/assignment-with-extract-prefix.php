<?php

$emptyPrefix = [
	'headline' => 'Test empty prefix',
];

extract($emptyPrefix, EXTR_PREFIX_ALL);

assertType('\'Test empty prefix\'', $_headline);


$prefix = [
	'headline' => 'Test prefix1',
];

extract($prefix, EXTR_PREFIX_ALL, 'prefix1');

assertType('\'Test prefix1\'', $prefix1_headline);
