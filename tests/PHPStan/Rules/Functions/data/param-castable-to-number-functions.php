<?php declare(strict_types = 1);

namespace ParamCastableToNumberFunctions;

class ClassWithoutToString {}
class ClassWithToString
{
	public function __toString(): string
	{
		return 'foo';
	}
}

function invalidUsages(): void
{
	$curlHandle = curl_init();
	// curl_init returns benevolent union and false is castable to number.
	assert($curlHandle !== false);

	var_dump(array_sum([[0]]));
	var_dump(array_sum([new \stdClass()]));
	var_dump(array_sum(['ttt']));
	var_dump(array_sum([fopen('php://input', 'r')]));
	var_dump(array_sum([$curlHandle]));
	var_dump(array_sum([new ClassWithToString()]));

	var_dump(array_product([[0]]));
	var_dump(array_product([new \stdClass()]));
	var_dump(array_product(['ttt']));
	var_dump(array_product([fopen('php://input', 'r')]));
	var_dump(array_product([$curlHandle]));
	var_dump(array_product([new ClassWithToString()]));
}

function wrongNumberOfArguments(): void
{
	array_sum();
	array_product();
}

function validUsages(): void
{
	var_dump(array_sum(['5.5', false, true, 5, 5.5, null]));
	var_dump(array_product(['5.5', false, true, 5, 5.5, null]));
}
