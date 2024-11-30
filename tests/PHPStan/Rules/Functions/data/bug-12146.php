<?php declare(strict_types = 1);

namespace Bug12146;

/**
 * @param mixed $mixed invalid, but don't report because it's reported by CallToFunctionParametersRule
 * @param array<int>|array<float> $validArrayUnion valid
 * @param array<int>|array<\stdClass> $invalidArrayUnion invalid, report
 * @param ?array<\stdClass> $nullableInvalidArray invalid, but don't report because it's reported by CallToFunctionParametersRule
 * @param array<\stdClass>|\SplFixedArray<int> $arrayOrSplArray invalid, but don't report because it's reported by CallToFunctionParametersRule
 * @return void
 */
function foo($mixed, $validArrayUnion, $invalidArrayUnion, $nullableInvalidArray, $arrayOrSplArray) {
	var_dump(array_sum($mixed));
	var_dump(array_sum($validArrayUnion));
	var_dump(array_sum($invalidArrayUnion));
	var_dump(array_sum($nullableInvalidArray));
	var_dump(array_sum($arrayOrSplArray));

	var_dump(array_product($mixed));
	var_dump(array_product($validArrayUnion));
	var_dump(array_product($invalidArrayUnion));
	var_dump(array_product($nullableInvalidArray));
	var_dump(array_product($arrayOrSplArray));

	var_dump(implode(',', $mixed));
	var_dump(implode(',', $validArrayUnion));
	var_dump(implode(',', $invalidArrayUnion));
	var_dump(implode(',', $nullableInvalidArray));
	var_dump(implode(',', $arrayOrSplArray));

	var_dump(array_intersect($mixed, [5]));
	var_dump(array_intersect($validArrayUnion, [5]));
	var_dump(array_intersect($invalidArrayUnion, [5]));
	var_dump(array_intersect($nullableInvalidArray, [5]));
	var_dump(array_intersect($arrayOrSplArray, [5]));

	var_dump(array_fill_keys($mixed, 1));
	var_dump(array_fill_keys($validArrayUnion, 1));
	var_dump(array_fill_keys($invalidArrayUnion, 1));
	var_dump(array_fill_keys($nullableInvalidArray, 1));
	var_dump(array_fill_keys($arrayOrSplArray, 1));

	var_dump(array_unique($mixed));
	var_dump(array_unique($validArrayUnion));
	var_dump(array_unique($invalidArrayUnion));
	var_dump(array_unique($nullableInvalidArray));
	var_dump(array_unique($arrayOrSplArray));
}
