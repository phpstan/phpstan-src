<?php declare(strict_types = 1);

namespace Bug12847;

function doBar():void {
	/**
	 * @var array<mixed> $array
	 */
	$array = [
		'abc' => 'def'
	];

	if (isset($array['def'])) {
		doSomething($array);
	}
}

function doFoo(array $array):void {
	if (isset($array['def'])) {
		doSomething($array);
	}
}

function doFooBar(array $array):void {
	if (array_key_exists('foo', $array) && $array['foo'] === 17) {
		doSomething($array);
	}
}

function doImplicitMixed($mixed):void {
	if (isset($mixed['def'])) {
		doSomething($mixed);
	}
}

function doExplicitMixed(mixed $mixed): void
{
	if (isset($mixed['def'])) {
		doSomething($mixed);
	}
}

/**
 * @param non-empty-array<mixed> $array
 */
function doSomething(array $array): void
{

}

/**
 * @param non-empty-array<int> $array
 */
function doSomethingWithInt(array $array): void
{

}

function doFooBarInt(array $array):void {
	if (array_key_exists('foo', $array) && $array['foo'] === 17) {
		doSomethingWithInt($array); // expect error, because our array is not sealed
	}
}

function doFooBarString(array $array):void {
	if (array_key_exists('foo', $array) && $array['foo'] === "hello") {
		doSomethingWithInt($array); // expect error, because our array is not sealed
	}
}
