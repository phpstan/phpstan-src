<?php declare(strict_types = 1);

namespace FinallyExitPointScope;

use function PHPStan\Testing\assertType;

function breakInTry(): void
{
	$x = 0;
	while (true) {
		try {
			break;
		} finally {
			$x = 'a';
		}
	}

	assertType("'a'", $x);
}

function continueInTry(): void
{
	$x = 0;
	foreach ([1, 2] as $i) {
		try {
			continue;
		} finally {
			$x = 'b';
		}
	}

	assertType("'b'", $x);
}

function breakInNestedTry(): void
{
	$x = 0;
	$y = 0;
	while (true) {
		try {
			try {
				break;
			} finally {
				$x = 'a';
			}
		} finally {
			$y = 'b';
		}
	}

	assertType("'a'", $x);
	assertType("'b'", $y);
}

function returnInTry(bool $c): string
{
	$x = 0;
	while (true) {
		try {
			if ($c) {
				break;
			}
			$x = 1;
		} finally {
			$x = 'a';
		}
	}

	assertType("'a'", $x);

	return $x;
}
