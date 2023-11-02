<?php

namespace Bug9580;


function test(): int|string|float|null
{
	return $_GET['value'];
}

function onlyNull(null $value): void
{

}

function doFoo() {
	$value = test();
	if (isset($value)) {
		exit;
	}
	onlyNull($value);
}
