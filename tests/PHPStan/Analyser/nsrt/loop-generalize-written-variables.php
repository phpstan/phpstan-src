<?php declare(strict_types = 1);

namespace LoopGeneralizeWrittenVariables;

use function PHPStan\Testing\assertType;

function takesInt(int $i): void
{
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function doWhileNarrowedOnFirstPass(int $x0, int $xn): void
{
	$xi = $x0;
	do {
		$previous = $xi;
		$xi += 0.1;
	} while ($previous < $xn);
	assertType('int<1, max>', $xn);
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function forLoop(int $x0, int $xn): void
{
	for ($xi = $x0; $xi < $xn; $xi += 0.1) {
		assertType('int<1, max>', $xn);
	}
	assertType('int<1, max>', $xn);
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 * @param list<int> $items
 */
function foreachLoop(int $x0, int $xn, array $items): void
{
	$xi = $x0;
	foreach ($items as $item) {
		if ($xi >= $xn) {
			break;
		}
		$xi += 0.1;
	}
	assertType('int<1, max>', $xn);
}

/**
 * @param array{1, ...<int>} $items
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function unsealedConstantArrayForeach(array $items, int $x0, int $xn): void
{
	$xi = $x0;
	$previous = $x0;
	foreach ($items as $item) {
		if ($previous >= $xn) {
			break;
		}
		$previous = $xi;
		$xi += 0.1;
	}
	assertType('int<1, max>', $xn);
}

/**
 * @param int<1,max> $x0
 * @param int<1,max> $xn
 */
function passedByValueInBody(int $x0, int $xn): void
{
	$xi = $x0;
	while ($xi < $xn) {
		takesInt($xn);
		$xi += 0.1;
	}
	assertType('int<1, max>', $xn);
}

/**
 * @param int<0,max> $i
 */
function incrementedVariableIsWidened(int $i): void
{
	while ($i < 10) {
		$i++;
	}
	assertType('int<10, max>', $i);
}

function destructuredVariableIsWidened(): void
{
	$xn = 1;
	while (rand(0, 1)) {
		[$xn] = [$xn + 1];
	}
	assertType('int<1, max>', $xn);
}

function variableWrittenThroughReferenceIsWidened(): void
{
	$xn = 1;
	$ref = &$xn;
	while (rand(0, 1)) {
		$ref = $xn + 1;
	}
	assertType('int<1, max>', $xn);
}

function variablePassedByReferenceIsWidened(): void
{
	$arr = [];
	$i = 0;
	while ($i < 5) {
		array_push($arr, $i);
		$i++;
	}
	assertType('non-empty-list<int<0, 4>>', $arr);
}

function variablePassedByReferenceInForUpdateIsWidened(): void
{
	$arr = [];
	for ($i = 0; $i < 5; $i++, array_push($arr, $i)) {
	}
	assertType('non-empty-list<int<1, 5>>', $arr);
}
