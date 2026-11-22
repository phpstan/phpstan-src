<?php declare(strict_types = 1);

namespace FunctionCallReadingState;

function doFoo(string $file): void
{
	time();
	getenv('HOME');
	date('Y');

	printf('a');
	file_get_contents($file);
	mt_rand();
	uniqid();
	constant('FOO');

	// a function with effects other than its flagged by-ref parameters
	system('ls');
	exec('ls');

	// the by-ref parameters are written
	headers_sent($headersFile, $headersLine);
	echo $headersFile . $headersLine;

	// a callable that is not known
	call_user_func('not_loaded_function');
	call_user_func('strlen', 'a');
}
