<?php

namespace Bug13539;

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
	/** @var \stdClass $tmp */
	$tmp = json_decode($x, false);

	if (!isset($tmp->foo) || !isset($tmp->bar)) {
    }
}

function threeProperties(string $x): void {
	$tmp = json_decode($x, false);

	if (!isset($tmp->foo) || !isset($tmp->bar) || !isset($tmp->baz)) {
    }
}

function coalesceAfterIsset(string $x): void {
	$tmp = json_decode($x, false);

	if (isset($tmp->foo)) {
		$bar = $tmp->bar ?? null;
	}
}

function issetInAndChain(string $x): void {
	$tmp = json_decode($x, false);

	if (isset($tmp->foo) && isset($tmp->bar)) {
    }
}

function emptyAfterIsset(string $x): void {
	$tmp = json_decode($x, false);

	if (isset($tmp->foo)) {
		if (empty($tmp->bar)) {
		}
	}
}
