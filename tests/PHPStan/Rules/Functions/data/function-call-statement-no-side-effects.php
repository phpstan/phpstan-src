<?php

namespace FunctionCallStatementNoSideEffects;

use PHPStan\TrinaryLogic;

class Foo
{

	public function doFoo(string $url, $mixed)
	{
		printf('%s', 'test');
		sprintf('%s', 'test');
		file_get_contents($url);
		file_get_contents($url, false, stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => 'Content-Type: application/json',
				'content' => json_encode($data, JSON_THROW_ON_ERROR),
			],
		]));
		file_get_contents($url, false, null);
		var_export([]);
		var_export([], true);
		print_r([]);
		print_r([], true);
		$callback = rand() === 0 ? 'is_string' : 'var_dump';
		array_filter([], 'var_dump');
		array_filter([], 'is_string');
		array_filter([], $mixed);
		array_filter([], $callback);
		array_map('var_dump', []);
		array_map('is_string', []);
		array_map($mixed, []);
		array_map($callback, []);
		array_reduce([], 'var_dump');
		array_reduce([], 'is_string');
		array_reduce([], $mixed);
		array_reduce([], $callback);
		array_reduce([], function ($carry, $item) {
			return $carry + $item;
		});
	}

	public function doBar(string $s)
	{
		\PHPStan\Testing\assertType('string', $s);
		\PHPStan\Testing\assertNativeType('string', $s);
		\PHPStan\Testing\assertVariableCertainty(TrinaryLogic::createYes(), $s);
	}

}
