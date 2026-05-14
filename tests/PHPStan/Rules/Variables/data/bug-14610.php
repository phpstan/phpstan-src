<?php

namespace Bug14610;

function test(): void
{
	$value = 0;

	if (isset($_SESSION['test'])) {
		$value = rand(0,3);
		if ($value == 1) {
		}
	}

	if ($value == 0) {
		$result = isset($_SESSION['test']); // should not be reported as always exists
	}
}

function testWithOtherSuperglobals(): void
{
	$value = 0;

	if (isset($_GET['key'])) {
		$value = rand(0,3);
		if ($value == 1) {
		}
	}

	if ($value == 0) {
		$result = isset($_GET['key']);
	}
}

function testWithStrictComparison(): void
{
	$value = 0;

	if (isset($_SESSION['test'])) {
		$value = rand(0,3);
		if ($value === 1) {
		}
	}

	if ($value === 0) {
		$result = isset($_SESSION['test']);
	}
}

function testWithDifferentKey(): void
{
	$value = 0;

	if (isset($_SESSION['test'])) {
		$value = rand(0,3);
		if ($value == 1) {
		}
	}

	if ($value == 0) {
		$result = isset($_SESSION['other']);
	}
}

/** @param array<mixed> $a */
function testWithParam($a): void
{
	$value = 0;

	if (isset($a['test'])) {
		$value = rand(0,3);
		if ($value == 1) {
		}
	}

	if ($value == 0) {
		$result = isset($a['test']);
	}
}
