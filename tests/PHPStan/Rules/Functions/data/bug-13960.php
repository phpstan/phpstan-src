<?php declare(strict_types = 1);

namespace Bug13960;

$aap = 684;

$a = function() use ($aap) {
	require __DIR__ . '/echo_the_value_of_aap.php';
};

$b = function() use ($aap) {
	include __DIR__ . '/echo_the_value_of_aap.php';
};

$c = function() use ($aap) {
	require_once __DIR__ . '/echo_the_value_of_aap.php';
};

$d = function() use ($aap) {
	include_once __DIR__ . '/echo_the_value_of_aap.php';
};
