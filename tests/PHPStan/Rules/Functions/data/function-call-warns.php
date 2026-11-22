<?php // lint >= 8.0

declare(strict_types = 1);

namespace FunctionCallWarns;

function doFoo(string $pattern, mixed $value, bool $return): void
{
	// warns when the pattern does not compile
	preg_match($pattern, '');
	preg_grep($pattern, ['a']);
	preg_split($pattern, 'a');

	// a constant pattern that compiles cannot warn, preg_grep() still leaves an error for preg_last_error()
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

	preg_replace($pattern, '', 'a');
	preg_filter($pattern, '', 'a');
	preg_replace('/^a$/', '', 'a');
	preg_filter('/^a$/', '', 'a');

	// written arguments still count
	preg_match('/^a$/', '', $matches);
	preg_replace('/^a$/', '', 'a', -1, $count);

	// leave an error for json_last_error()
	json_decode('[');

	// writes global state, not only the last error
	pcntl_sigprocmask(0, [17]);

	// a pattern with the u modifier warns where PCRE does not support UTF-8
	preg_match('//u', '');
	preg_match('~^a$~iu', '');
	preg_match('(^a$)u', '');
}
