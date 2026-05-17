<?php

function broken(string $x): void {
	$tmp = json_decode($x, false);
	if (!isset($tmp->foo) || !isset($tmp->bar)) {
	}
}

function works(string $x): void {
	$tmp = json_decode($x, false);
	if (!isset($tmp->foo, $tmp->bar)) {
	}
}

function works_too(string $x): void {
	/** @var stdClass $tmp */
	$tmp = json_decode($x, false);
	if (!isset($tmp->foo) || !isset($tmp->bar)) {
	}
}

/**
 * @param mixed $tmp
 */
function also_ok($tmp): void {
	if (isset($tmp->foo) && isset($tmp->bar)) {
		echo $tmp->foo;
		echo $tmp->bar;
		echo $tmp->baz; // intentional: baz not checked by isset
	}
}
