<?php declare(strict_types = 1);

namespace ReadonlySuperglobals;

function writes(array $array): void
{
	$_GET = [];
	$_POST['foo'] = 'bar';
	$_COOKIE['foo']['bar'] = 'baz';
	$_REQUEST[] = 'foo';
	$_GET['count'] .= 'x';
	$_SERVER['argc']++;
	unset($_ENV['FOO']);
	[$_GET['a'], $_GET['b']] = $array;
	foreach ($array as $_POST) {
	}
	parse_str('a=b', $_GET);
	sort($_POST);
	$GLOBALS['foo'] = 1;
	$_SESSION['user'] = 1;
	$_FILES = [];
}

function reads(): void
{
	$id = $_GET['id'] ?? null;
	$all = $_POST;
	if (isset($_COOKIE['foo'])) {
	}
	$server = $_SERVER['REQUEST_URI'];
}

function localVariables(): void
{
	$get = [];
	$get['foo'] = 'bar';
	$GET = [];
}
