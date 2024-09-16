<?php declare(strict_types = 1);

namespace Bug11699;

use function PHPStan\Testing\assertType;

function doFoo():void {
	$string = 'Foo.bar';
	$match = [];
	$result = preg_match('~(?<AB>[\~,\?\.])~', $string, $match);
	if ($result === 1) {
		assertType("','|'.'|'?'|'~'", $match['AB']);
	}
}

function doFoo1():void {
	$string = 'Foo.bar';
	$match = [];
	$result = preg_match('~(?<AB>[\~,\?.])~', $string, $match); // dot in character class does not need to be escaped
	if ($result === 1) {
		assertType("','|'.'|'?'|'~'", $match['AB']);
	}
}

function doFoo2():void {
	$string = 'Foo.bar';
	$match = [];
	$result = preg_match('~(?<AB>.)~', $string, $match);
	if ($result === 1) {
		assertType("non-empty-string", $match['AB']);
	}
}


function doFoo3():void {
	$string = 'Foo.bar';
	$match = [];
	$result = preg_match('~(?<AB>\.)~', $string, $match);
	if ($result === 1) {
		assertType("'.'", $match['AB']);
	}
}

function doFoo4():void {
	$string = 'Foo.bar';
	$match = [];
	$result = preg_match('~(?<AB>[^\~,\?\.])~', $string, $match);
	if ($result === 1) {
		assertType("non-empty-string", $match['AB']);
	}
}
