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
	}

}
