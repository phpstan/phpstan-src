<?php

declare(strict_types = 1);

namespace Bug7230;

use function PHPStan\Testing\assertType;

function preIncWhile(): void
{
	$counter = 0;
	while (++$counter < 100) {
		assertType('int<1, 99>', $counter);
	}
	assertType('int<100, max>', $counter);

	if ($counter === 100) {
		assertType('100', $counter);
	}
}

function preIncWhileWithBody(): void
{
	$tmp = '';
	$counter = 0;
	while (++$counter < 100) {
		$tmp = 'tmp_' . (string) $counter;
	}
	assertType('int<100, max>', $counter);

	if ($counter === 100) {
		$tmp = 'tmp_hardcoded';
	}

	assertType("''|(literal-string&lowercase-string&non-falsy-string)", $tmp);
}

function postIncWhile(): void
{
	$counter = 0;
	while ($counter++ < 100) {
		assertType('int<1, 100>', $counter);
	}
	assertType('int<101, max>', $counter);

	if ($counter === 101) {
		assertType('101', $counter);
	}
}

function preDecWhile(): void
{
	$counter = 100;
	while (--$counter > 0) {
		assertType('int<1, 99>', $counter);
	}
	assertType('int<min, 0>', $counter);
}

function preIncFor(): void
{
	$counter = 0;
	for (; ++$counter < 5; ) {
	}
	assertType('int<5, max>', $counter);
}

function postIncFor(): void
{
	$counter = 0;
	for (; $counter++ < 5; ) {
	}
	assertType('int<6, max>', $counter);
}

function preDecFor(): void
{
	$counter = 10;
	for (; --$counter > 0; ) {
	}
	assertType('int<min, 0>', $counter);
}
