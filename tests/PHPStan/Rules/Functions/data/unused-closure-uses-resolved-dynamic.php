<?php

$container = new stdClass();
$other = new stdClass();

function () use ($container, $other) {
	// the dynamic name resolves to 'container' during the walk; the syntactic
	// check could not see it (the assignment happens inside the closure) and
	// treated every use as used
	$name = 'container';
	echo $$name;
};

function (array $keys) use ($container) {
	// a compact() argument that is not statically known can name any variable -
	// the syntactic check reported $container here
	return compact($keys);
};

$overwritten = new stdClass();

function () use ($overwritten) {
	// the imported value is overwritten before it is ever read - the use is
	// unused even though the name appears in the body
	$overwritten = new stdClass();
	echo get_class($overwritten);
};

$readThenOverwritten = new stdClass();

function () use ($readThenOverwritten) {
	echo get_class($readThenOverwritten);
	$readThenOverwritten = new stdClass();
	echo get_class($readThenOverwritten);
};

$maybeOverwritten = new stdClass();

function () use ($maybeOverwritten) {
	if (rand(0, 1) === 0) {
		$maybeOverwritten = new stdClass();
	}
	echo get_class($maybeOverwritten);
};

$byRefWritten = null;

function () use (&$byRefWritten) {
	// writing through a by-ref use mentions it - that is its purpose
	$byRefWritten = new stdClass();
};
