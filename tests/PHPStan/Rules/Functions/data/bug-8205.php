<?php declare(strict_types = 1);

namespace Bug8205;

interface  TakesCallable{
	public function takes(callable $c): void;
}

function test(TakesCallable $tc): void
{
	if(function_exists('test123')) {
		$tc->takes(function () {
			test123();
		});
	}

	$tc->takes(function () {
		if(function_exists('test123')) {
			test123();
		}
	});
}
