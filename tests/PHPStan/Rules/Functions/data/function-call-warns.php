<?php // lint >= 8.0

declare(strict_types = 1);

namespace FunctionCallWarns;

function doFoo(string $pattern, mixed $value, bool $return): void
{
	// warns when the pattern does not compile
	preg_match($pattern, '');
	preg_grep($pattern, ['a']);
	preg_split($pattern, 'a');

	// a constant pattern that compiles cannot warn
	preg_match('/^a$/', '');
	preg_grep('/^a$/', ['a']);

	// print only when $return is false
	print_r($value);
	var_export($value);
	print_r($value, $return);
	print_r($value, true);
	var_export($value, true);
	var_export(return: true, value: $value);
	highlight_string('<?php', true);
}
