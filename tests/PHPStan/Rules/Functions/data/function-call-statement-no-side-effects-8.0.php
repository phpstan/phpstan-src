<?php

namespace FunctionCallStatementNoSideEffectsPhp8;

use PHPStan\TrinaryLogic;

class Foo
{

	/**
	 * @param ?resource $resourceOrNull
	 */
	public function noEffect(string $url, $resourceOrNull)
	{
		file_get_contents(filename: $url, offset: 1, length: 100);
		file_get_contents($url, length: 100, offset: 1);
		file_get_contents($url, false, null, length: 100);
		file_get_contents($url, context: $resourceOrNull);
		var_export([], return: true);
		print_r([], return: true);
		highlight_string($url, return: true);
		array_filter([], callback: 'is_string');
		array_filter([], is_string(...));
		array_map(array: [], callback: 'is_string');
		array_map(is_string(...), []);
		array_reduce([], callback: fn($carry, $item) => $carry + $item);
	}

	/**
	 * @param resource $resource
	 */
	public function hasSideEffect(string $url, $resource)
	{
		file_get_contents($url, false, $resource, length: 100);
		file_get_contents($url, context: $resource);
		var_export(value: []);
		print_r(value: []);
		highlight_string($url);
		$callback = rand() === 0 ? is_string(...) : var_dump(...);
		array_filter([], callback: $callback);
		array_filter([], callback: 'var_dump');
		array_filter([], var_dump(...));
		array_map(array: [], callback: $callback);
		array_map(array: [], callback: 'var_dump');
		array_map(var_dump(...), array: []);
		array_reduce([], callback: $callback);
	}

}
