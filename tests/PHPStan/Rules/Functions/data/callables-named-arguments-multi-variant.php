<?php // lint >= 8.1

namespace CallablesNamedArgumentsMultiVariant;

function (callable $open, callable $read): void {
	$f = mt_rand(...);
	$f(min: 1);
	$f(min: 1, max: 2);

	$g = session_set_save_handler(...);
	$g($open, read: $read);

	$h = array_udiff(...);
	$h(array: [1]);
};
