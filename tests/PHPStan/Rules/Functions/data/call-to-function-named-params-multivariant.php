<?php declare(strict_types=1); // lint >= 8.0

namespace CallToFunctionNamedParamsMultiVariant;

// docs say that it's not compatible with named params, but it actually works
setcookie(name: 'aaa', value: 'bbb', expires_or_options: ['httponly' => true]);
setrawcookie(name: 'aaa1', value: 'bbb', expires_or_options: ['httponly' => true]);
var_dump(abs(num: 5));
var_dump(array_rand(array: [5]));
var_dump(array_rand(array: [5], num: 1));
var_dump(getenv(name: 'aaa', local_only: true));
$cal = new \IntlGregorianCalendar();
var_dump(intlcal_set(calendar: $cal, month: 5, year: 6));
var_dump(join(separator: 'a', array: []));
var_dump(join(separator: ['aaa', 'bbb']));
var_dump(implode(separator: 'a', array: []));
var_dump(implode(separator: ['aaa', 'bbb']));
var_dump(levenshtein(string1: 'aaa', string2: 'bbb', insertion_cost: 1, deletion_cost: 1, replacement_cost: 1));
var_dump(levenshtein(string1: 'aaa', string2: 'bbb'));
// Is it possible to call it with multiple named args?
var_dump(max(value: [5, 6]));
session_set_cookie_params(lifetime_or_options: []);
session_set_cookie_params(lifetime_or_options: 1, path: '/');
session_set_save_handler(open: new class implements \SessionHandlerInterface {
	public function close(): bool
	{
		return true;
	}

	public function destroy(string $id): bool
	{
		return true;
	}

	public function gc(int $max_lifetime): int|false
	{
		return 0;
	}

	public function open(string $path, string $name): bool
	{
		return true;
	}

	public function read(string $id): string|false
	{
		return true;
	}

	public function write(string $id, string $data): bool
	{
		return true;
	}

}, close: true);
setlocale(category: 0, locales: 'aaa');
setlocale(category: 0, locales: []);
sscanf(string: 'aaa', format: 'aaa');
$context = fopen('php://input', 'r');
assert($context !== false);
stream_context_set_option(context: $context, wrapper_or_options: []);
stream_context_set_option(context: $context, wrapper_or_options: 'aaa', option_name: "aaa", value: 'aaa');
var_dump(strtok(string: 'bbb aaa ccc', token: 'a'));
// docs say it's not compatible with named params, but it actually works
var_dump(strtok(string: 'a'));
var_dump(strtr(string: 'aaa', from: 'a', to: 'b'));
var_dump(strtr(string: 'aaa', from: ['a' => 'b']));
