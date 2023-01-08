<?php

namespace FunctionCallStatementNoSideEffects;

use PHPStan\TrinaryLogic;

class Foo
{

	public function doFoo(string $url)
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
	}

	public function doBar(string $s)
	{
		\PHPStan\Testing\assertType('string', $s);
		\PHPStan\Testing\assertNativeType('string', $s);
		\PHPStan\Testing\assertVariableCertainty(TrinaryLogic::createYes(), $s);
	}

}
