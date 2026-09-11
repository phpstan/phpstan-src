<?php declare(strict_types = 1);

namespace UnusedVariableFlowMessages;

/** @return mixed */
function source()
{
	return rand();
}

/** @param mixed $v */
function sink($v): void
{
}

function assign(): void
{
	$a = source();
	while (rand(0, 1)) {
		$a = $a + 1;
	}
}

function readModifyWrite(): void
{
	$s = 'a';
	while (rand(0, 1)) {
		$s .= 'x';
	}
}

function dimWrite(): void
{
	$a = ['k' => 0];
	while (rand(0, 1)) {
		$a['k'] = $a['k'] + 1;
	}
}

function listItem(): void
{
	[$a] = [source()];
	while (rand(0, 1)) {
		$a = $a + 1;
	}
}

function preInc(): void
{
	$i = 0;
	while (rand(0, 1)) {
		++$i;
	}
}

function postInc(): void
{
	$i = 0;
	while (rand(0, 1)) {
		$i++;
	}
}

function preDec(): void
{
	$i = 0;
	while (rand(0, 1)) {
		--$i;
	}
}

function postDec(): void
{
	$i = 0;
	while (rand(0, 1)) {
		$i--;
	}
}

/** @param array<string, int> $items */
function foreachValueAndKey(array $items): void
{
	foreach ($items as $k => $v) {
		while (rand(0, 1)) {
			$v = $v + 1;
			$k = $k . 'x';
		}
	}
}

function literalOffset(): void
{
	$a = ['x' => 1, 'y' => 2];
	while (rand(0, 1)) {
		$a['x'] = $a['x'] + 1;
	}
	sink($a['y']);
}

function coveredByNeverReadWrite(): void
{
	$a = source();
	$b = $a + 1;
}

function coveredThroughChain(): void
{
	$a = source();
	$a = $a + 1;
	$a = $a + 1;
}

function coveredThroughNestedAssignment(): void
{
	$c = source();
	$a = $b = $c + 1;
}

function coveredThroughLiteralItem(): void
{
	$v = source();
	$a = ['x' => $v, 'y' => 2];
	sink($a['y']);
}
