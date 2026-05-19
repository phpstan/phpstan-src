<?php declare(strict_types = 1);

namespace GotoLabelStabilization;

use function PHPStan\Testing\assertType;

function forwardGotoNarrowing(): void
{
	$a = rand(0, 1) ? 'hello' : false;
	if ($a === false)
		goto end;

	assertType("'hello'", $a);

	end:
	assertType("'hello'|false", $a);
}

function backwardGotoLoop(): void
{
	$i = 0;
	start:
	assertType('int<0, max>', $i);
	$i++;
	if ($i < 10) {
		goto start;
	}
	assertType('int<10, max>', $i);
}

function backwardGotoNullCheck(): void
{
	retry:
	$result = rand(0, 1) ? 'value' : null;
	if ($result === null) {
		goto retry;
	}
	assertType("'value'", $result);
}

function forwardGotoSkipsCode(): void
{
	/** @var int|string $x */
	$x = doSomething();
	if (is_int($x)) {
		goto skip;
	}
	assertType('string', $x);
	skip:
	assertType('int|string', $x);
}

/** @return int|string */
function doSomething()
{
	return rand(0, 1) ? 1 : 'a';
}

function gotoOutOfIf(): void
{
	/** @var int|null $val */
	$val = rand(0, 1) ? 42 : null;
	if ($val === null) {
		goto handleNull;
	}

	assertType('int', $val);
	echo $val * 2;
	goto done;

	handleNull:
	assertType('null', $val);
	echo "null value";

	done:
	assertType('int|null', $val);
}

function multipleGotosToSameLabel(): void
{
	/** @var int|string|null $x */
	$x = doSomething2();

	if ($x === null) {
		goto end;
	}
	if (is_string($x)) {
		goto end;
	}

	assertType('int', $x);

	end:
	assertType('int|string|null', $x);
}

/** @return int|string|null */
function doSomething2()
{
	return rand(0, 2) === 0 ? null : (rand(0, 1) ? 1 : 'a');
}

function retryPatternWithCounter(): void
{
	$attempt = 0;

	retry:
	$attempt++;
	assertType('int<1, max>', $attempt);

	if (rand(0, 1) === 1 && $attempt < 3) {
		goto retry;
	}

	assertType('int<1, max>', $attempt);
}

function closureGotoDoesNotAffectOuterLabel(): void
{
	$x = 0;
	start:
	$x++;

	$fn = function () {
		start:
		$inner = rand(0, 1) ? 'world' : null;
		if ($inner === null) {
			goto start;
		}
	};

	assertType('1', $x);
}

function anonymousClassGotoDoesNotAffectOuterLabel(): void
{
	$x = 0;
	start:
	$x++;

	$obj = new class {
		public function doSomething(): void
		{
			start:
			$inner = rand(0, 1) ? 'world' : null;
			if ($inner === null) {
				goto start;
			}
		}
	};

	assertType('1', $x);
}

function nestedFunctionGotoDoesNotAffectOuterLabel(): void
{
	$x = 0;
	start:
	$x++;

	function innerFunction(): void
	{
		start:
		$inner = rand(0, 1) ? 'world' : null;
		if ($inner === null) {
			goto start;
		}
	}

	assertType('1', $x);
}
