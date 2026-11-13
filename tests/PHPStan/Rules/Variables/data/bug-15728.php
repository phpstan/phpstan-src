<?php declare(strict_types = 1);

namespace Bug15728;

/** @return int */
function getInt(string $key)
{
	return (int) $key;
}

function viaTernary(): void
{
	$type = getInt('type');
	echo isset($type) ? 'set' : 'null';
	echo $type;
}

/** @param float $x */
function viaTernaryOnParameter($x = 0): void
{
	echo isset($x) ? 'set' : 'null';
	echo $x;
}

function viaIfElse(): void
{
	$type = getInt('type');
	if (isset($type)) {
		echo 'set';
	} else {
		echo $type;
	}
	echo $type;
}

function maybeDefined(bool $b): void
{
	if ($b) {
		$type = getInt('type');
	}
	if (!isset($type)) {
		echo $type;
	}
}
