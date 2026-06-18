<?php declare(strict_types = 1);

namespace Bug7751;

use function PHPStan\Testing\assertType;

function test(): void
{
	$foo = false;

	$test = function () use (&$foo) {
		assertType('array{}|false', $foo);
		if (is_array($foo)) {
			echo 'array';
		} else {
			echo 'not array';
		}
	};

	$foo = [];

	$test();
}

function shutdown(): void
{
	$tmpdir = '';

	$shutdownFunction = function () use (&$tmpdir) {
		assertType('\'\'|\'/tmp/my/useful/tempdir\'', $tmpdir);
		if ($tmpdir !== '' && file_exists($tmpdir)) {
			echo $tmpdir;
		}
	};

	register_shutdown_function($shutdownFunction);

	$tmpdir = '/tmp/my/useful/tempdir';
}
