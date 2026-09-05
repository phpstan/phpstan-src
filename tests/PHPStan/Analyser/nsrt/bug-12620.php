<?php declare(strict_types = 1);

namespace Bug12620;

use function PHPStan\Testing\assertType;

function (): void {
	$data = [];
	if(isset($GLOBALS['data']) && is_string($GLOBALS['data'])){
	$data  = (array)json_decode($GLOBALS['data']);
	}

	$isA = isset($data['a']) && $data['a'] !== '';
	$isB = isset($data['b']) && $data['b'] !== '';



	if($isA){
		assertType("non-empty-array<mixed>&hasOffset('a')", $data);
		assertType("mixed~(''|null)", $data['a']);
		var_dump($data['a']);
	}
	if($isB){
		assertType("non-empty-array<mixed>&hasOffset('b')", $data);
		assertType("mixed~(''|null)", $data['b']);
		var_dump($data['b']);
	}
};

// order-swapped variant: the historical bug was order-dependent
function (): void {
	$data = [];
	if(isset($GLOBALS['data']) && is_string($GLOBALS['data'])){
	$data  = (array)json_decode($GLOBALS['data']);
	}

	$isB = isset($data['b']) && $data['b'] !== '';
	$isA = isset($data['a']) && $data['a'] !== '';


	if($isA){
		assertType("non-empty-array<mixed>&hasOffset('a')", $data);
		assertType("mixed~(''|null)", $data['a']);
		var_dump($data['a']);
	}
	if($isB){
		assertType("non-empty-array<mixed>&hasOffset('b')", $data);
		assertType("mixed~(''|null)", $data['b']);
		var_dump($data['b']);
	}
};
